<?php
include("../configs/db.php");
session_start();

header('Content-Type: application/json');

// CSRF token check

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    echo json_encode([
        "status" => "error",
        "msg" => "CSRF token không hợp lệ!",
        "isToast" => true
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'];

if (!$action) {
    echo json_encode([
        "status" => "error",
        "msg" => "Hành động không hợp lệ",
    ]);
    exit;
}

function ensureCartPriceColumn()
{
    global $conn;
    $result = $conn->query("SHOW COLUMNS FROM cart LIKE 'price'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE cart ADD COLUMN price DECIMAL(10,2) DEFAULT NULL");
    }
}

ensureCartPriceColumn();

function addToCart($product_id, $variant_id = null, $qty = null, $price = null)
{
    global $conn, $user_id;

    $qty = $qty !== null ? intval($qty) : null;
    $price = is_numeric($price) ? floatval($price) : null;

    if ($qty === null && $price === null) {
        $qty = 1;
    }

    // Nếu có variant → kiểm tra tồn kho theo variant
    if ($variant_id) {
        $stmt = $conn->prepare("SELECT stock FROM product_variants WHERE id=? AND product_id=?");
        $stmt->bind_param("ii", $variant_id, $product_id);
    } else {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id=?");
        $stmt->bind_param("i", $product_id);
    }
    $stmt->execute();
    $stockRow = $stmt->get_result()->fetch_assoc();
    $stock = $stockRow['stock'] ?? 0;

    if ($stock <= 0 && $qty > 0) {
        return false; // hết hàng, chỉ chặn khi thêm số lượng dương
    }

    // Kiểm tra cart
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id=? AND product_id=? AND (variant_id <=> ?)");
    $stmt->bind_param("iii", $user_id, $product_id, $variant_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        // Cho phép số lượng âm và có thể override giá nếu client gửi price (giá là tổng dòng hàng)
        if ($qty === null) {
            $new_qty = $row['quantity'];
        } else {
            $new_qty = $row['quantity'] + $qty;
        }

        if ($price !== null) {
            $stored_price = floatval($price);
            $stmt = $conn->prepare("UPDATE cart SET quantity=?, price=? WHERE id=?");
            $stmt->bind_param("idi", $new_qty, $stored_price, $row['id']);
        } else {
            $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE id=?");
            $stmt->bind_param("ii", $new_qty, $row['id']);
        }
        $stmt->execute();
    } else {
        if ($qty === null) {
            $qty = 1;
        }
        $qty = min($qty, $stock);
        if ($price !== null) {
            $stored_price = floatval($price);
            $stmt = $conn->prepare("INSERT INTO cart(user_id, product_id, variant_id, quantity, price) VALUES(?,?,?,?,?)");
            $stmt->bind_param("iiidi", $user_id, $product_id, $variant_id, $qty, $stored_price);
        } else {
            $stmt = $conn->prepare("INSERT INTO cart(user_id, product_id, variant_id, quantity) VALUES(?,?,?,?)");
            $stmt->bind_param("iiii", $user_id, $product_id, $variant_id, $qty);
        }
        $stmt->execute();
    }

    return true;
}

function getCartCount()
{
    global $conn, $user_id;
    $stmt = $conn->prepare("SELECT sum(quantity) as cart_count FROM cart WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['cart_count'] ?? 0;
}

$product_id = intval($_POST['product_id'] ?? $_POST['id'] ?? 0);
$variant_id = !empty($_POST['variant']) ? intval($_POST['variant']) : null;
$qty = null;
if (array_key_exists('qty', $_POST) && !is_array($_POST['qty'])) {
    $qty = intval($_POST['qty']);
}
$direct_cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : (isset($_POST['id']) ? intval($_POST['id']) : null);
$price = null;
if (isset($_POST['price']) && !is_array($_POST['price'])) {
    $price = floatval($_POST['price']);
}

// Chỉ xử lý addToCart khi action là 'add'
if ($action == 'add') {
    if (addToCart($product_id, $variant_id, $qty, $price)) {
        $cartCount = getCartCount();
        echo json_encode([
            "status" => "success",
            "msg" => "Đã thêm vào rỏ hàng",
            "cart_count" => $cartCount,
            "isToast" => true
        ]);
    } else {
        echo json_encode([
            "status" => "warning",
            "msg" => "Sản phẩm này đã hết hàng",
            "isToast" => true
        ]);
    }
    exit;
}

$total = 0;
$items = [];
$discount = 0;
$coupon_code = "";

function updateCart()
{
    global $conn, $user_id, $total, $items, $discount, $coupon_code;
    $stmt = $conn->prepare("
  SELECT c.id as cart_id, c.quantity, c.price as cart_price,
         p.id as product_id, p.name, p.image, p.price as base_price, p.stock as product_stock,
         v.id as variant_id, v.name as variant_name, v.price as variant_price, v.stock as variant_stock
  FROM cart c
  JOIN products p ON p.id = c.product_id
  LEFT JOIN product_variants v ON v.id = c.variant_id
  WHERE c.user_id=?
");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $price = isset($row['cart_price']) ? floatval($row['cart_price']) : ($row['variant_price'] ?? $row['base_price']);
        $row['price'] = $price;
        $row['subtotal'] = $price * $row['quantity'];
        $total += $row['subtotal'];
        $items[] = $row;
    }
    // Nếu đã có coupon thì tính giảm
    if (isset($_SESSION['coupon'])) {
        $coupon = $_SESSION['coupon'];

        if (!empty($coupon['expiry'])) {
            $expiry = strtotime($coupon['expiry']);
            if ($expiry < time()) {
                unset($_SESSION['coupon']);
                echo json_encode([
                    "status" => "error",
                    "msg" => "Mã giảm giá này đã hết hạn",
                    "isToast" => true
                ]);
                exit;
            }
        }

        if (isset($_SESSION['coupon'])) { // nếu vẫn còn hợp lệ
            if ($coupon['type'] === 'percent') {
                $discount = ($total * $coupon['discount']) / 100;
            } else {
                $discount = $coupon['discount'];
            }
            $coupon_code = $coupon['code'];
        }
    }
}

updateCart();

// Áp dụng coupon
if ($action == 'apply_coupon') {
    $coupon_code = trim($_POST['coupon_code']);
    if (empty($coupon_code)) {
        echo json_encode([
            "status" => "error",
            "msg" => "Vui lòng nhập mã giảm giá",
            "isToast" => true
        ]);
        exit;
    }
    $coupon_code = strtoupper($coupon_code);
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code=? LIMIT 1");
    $stmt->bind_param("s", $coupon_code);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();
    if ($coupon) {
        $_SESSION['coupon'] = $coupon;
        $expiry = strtotime($coupon['expiry']);
        if ($expiry < time()) {
            unset($_SESSION['coupon']);
            echo json_encode([
                "status" => "error",
                "msg" => "Mã giảm giá này đã hết hạn",
                "isToast" => true
            ]);
            exit;
        }

        if ($coupon['quantity'] <= 0) {
            unset($_SESSION['coupon']);
            echo json_encode([
                "status" => "error",
                "msg" => "Mã giảm giá này đã hết lượt dùng",
                "isToast" => true
            ]);
            exit;
        }

        $coupon_id = $coupon['id'];
        $stmt = $conn->prepare("SELECT COUNT(*) as used_count FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $coupon_id, $user_id);
        $stmt->execute();
        $coupon_usage = $stmt->get_result()->fetch_assoc();

        if ($coupon_usage['used_count'] > $coupon['usage_limit']) {
            unset($_SESSION['coupon']);
            echo json_encode([
                "status" => "error",
                "msg" => "Bạn đã hết lượt sử dụng mã giảm giá này",
                "isToast" => true
            ]);
            exit;
        }

        if (isset($_SESSION['coupon'])) { // nếu vẫn còn hợp lệ
            if ($coupon['type'] === 'percent') {
                $discount = ($total * $coupon['discount']) / 100;
            } else {
                $discount = $coupon['discount'];
            }
            $coupon_code = $coupon['code'];
            $cartCount = getCartCount();
            echo json_encode([
                "status" => "success",
                "msg" => "Nhập thành công mã giảm giá $coupon_code",
                "total" => $total,
                "discount" => $discount,
                "coupon_code" => $coupon_code,
                "cart_count" => $cartCount,
                "isToast" => true
            ]);
            exit;
        }
    } else {
        echo json_encode([
            "status" => "error",
            "msg" => "Mã giảm giá không hợp lệ",
            "isToast" => true
        ]);
        exit;
    }
}

// Xóa sản phẩm khỏi giỏ
if ($action == 'remove') {
    $id = intval($_POST['cart_id']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=? AND id=?");
    $stmt->bind_param("ii", $user_id, $id);
    $stmt->execute();
    $items = [];
    $total = 0;
    $discount = 0;
    updateCart();
    $cartCount = getCartCount();
    echo json_encode([
        "status" => "success",
        "msg" => "Cập nhật thành công",
        "items" => $items,
        "total" => $total,
        "discount" => $discount,
        "coupon_code" => $coupon_code,
        "cart_count" => $cartCount
    ]);
    exit;
}

// Cập nhật số lượng
if ($action == "update") {
    $directPrice = null;
    $directCartId = null;
    $directQty = null;
    if (isset($_POST['id']) && !isset($_POST['qty'])) {
        $directCartId = intval($_POST['id']);
    }
    if (isset($_POST['cart_id'])) {
        $directCartId = intval($_POST['cart_id']);
    }
    if (isset($_POST['price']) && !is_array($_POST['price'])) {
        $directPrice = floatval($_POST['price']);
    }
    if (isset($_POST['qty']) && !is_array($_POST['qty'])) {
        $directQty = intval($_POST['qty']);
    }

    if ($directCartId !== null) {
        $qty = $directQty !== null ? $directQty : 0;
        $price = $directPrice;
        $_POST['qty'] = [$directCartId => $qty];
        if ($price !== null) {
            $_POST['price'] = [$directCartId => $price];
        }
    }

    foreach ($_POST['qty'] as $cart_id => $qty) {
        $qty = intval($qty);
        $price = isset($_POST['price'][$cart_id]) ? floatval($_POST['price'][$cart_id]) : null;

        $stmt = $conn->prepare(
            "SELECT c.id as cart_id, c.variant_id, p.stock as product_stock, v.stock as variant_stock
             FROM cart c
             JOIN products p ON p.id = c.product_id
             LEFT JOIN product_variants v ON v.id = c.variant_id
             WHERE c.user_id=? AND c.id=? LIMIT 1"
        );
        $stmt->bind_param("ii", $user_id, $cart_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            // Nếu không phải cart_id thì thử lấy theo product_id
            $product_id = intval($_POST['id'] ?? 0);
            if ($product_id > 0) {
                $stmt = $conn->prepare(
                    "SELECT c.id as cart_id, c.variant_id, p.stock as product_stock, v.stock as variant_stock
                     FROM cart c
                     JOIN products p ON p.id = c.product_id
                     LEFT JOIN product_variants v ON v.id = c.variant_id
                     WHERE c.user_id=? AND c.product_id=? LIMIT 1"
                );
                $stmt->bind_param("ii", $user_id, $product_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if ($row) {
                    $cart_id = $row['cart_id'];
                }
            }
        }

        if (!$row) {
            continue;
        }

        $stock = $row['variant_id'] ? intval($row['variant_stock']) : intval($row['product_stock']);
        if ($qty > $stock) {
            $qty = $stock;
        }

        if ($price !== null) {
            $stored_price = floatval($price);
            $stmt = $conn->prepare("UPDATE cart SET quantity=?, price=? WHERE user_id=? AND id=?");
            $stmt->bind_param("didi", $qty, $stored_price, $user_id, $cart_id);
        } else {
            $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND id=?");
            $stmt->bind_param("iii", $qty, $user_id, $cart_id);
        }
        $stmt->execute();
    }
    $items = [];
    $total = 0;
    $discount = 0;
    updateCart();
    $cartCount = getCartCount();
    echo json_encode([
        "status" => "success",
        "msg" => "Cập nhật thành công",
        "items" => $items,
        "total" => $total,
        "discount" => $discount,
        "coupon_code" => $coupon_code,
        "cart_count" => $cartCount
    ]);
    exit;
}
