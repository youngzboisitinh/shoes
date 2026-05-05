<?php
/**
 * FILE TEST: Endpoint này KHÔNG có CSRF protection
 * Dùng để học và demo CSRF vulnerability
 * KHÔNG DÙNG TRONG PRODUCTION
 */

include("../configs/db.php");
session_start();

header('Content-Type: application/json');

// ⚠️ KHÔNG có CSRF token check - điều này là lỗ hổng CSRF
// if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
//     echo json_encode(["status" => "error", "msg" => "CSRF token không hợp lệ!"]);
//     exit;
// }

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "msg" => "Chưa đăng nhập"
    ]);
    exit;
}

$action = $_POST['action'] ?? null;

if (!$action) {
    echo json_encode([
        "status" => "error",
        "msg" => "Hành động không hợp lệ"
    ]);
    exit;
}

// Tạo cột price nếu chưa tồn tại
function ensureCartPriceColumn()
{
    global $conn;
    $result = $conn->query("SHOW COLUMNS FROM cart LIKE 'price'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE cart ADD COLUMN price DECIMAL(10,2) DEFAULT NULL");
    }
}

ensureCartPriceColumn();

// Hàm thêm vào giỏ hàng - KHÔNG kiểm tra CSRF
function addToCart($product_id, $variant_id = null, $qty = null, $price = null)
{
    global $conn, $user_id;

    $qty = $qty !== null ? intval($qty) : 1;
    $price = is_numeric($price) ? floatval($price) : null;

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
        return false;
    }

    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id=? AND product_id=? AND (variant_id <=> ?)");
    $stmt->bind_param("iii", $user_id, $product_id, $variant_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $new_qty = $row['quantity'] + $qty;
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

// Action: add sản phẩm - KHÔNG cần CSRF token
if ($action == 'add') {
    $product_id = intval($_POST['product_id'] ?? $_POST['id'] ?? 0);
    $variant_id = !empty($_POST['variant']) ? intval($_POST['variant']) : null;
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;

    if (addToCart($product_id, $variant_id, $qty, $price)) {
        echo json_encode([
            "status" => "success",
            "msg" => "✅ Thêm vào giỏ hàng THÀNH CÔNG (KHÔNG có CSRF protection)"
        ]);
    } else {
        echo json_encode([
            "status" => "warning",
            "msg" => "Sản phẩm hết hàng"
        ]);
    }
    exit;
}

// Action: update giỏ - KHÔNG cần CSRF token
if ($action == "update") {
    if (isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $cart_id => $qty) {
            $qty = intval($qty);
            $price = isset($_POST['price'][$cart_id]) ? floatval($_POST['price'][$cart_id]) : null;

            $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND id=?");
            $stmt->bind_param("iii", $qty, $user_id, $cart_id);
            $stmt->execute();

            if ($price !== null) {
                $stmt = $conn->prepare("UPDATE cart SET price=? WHERE user_id=? AND id=?");
                $stmt->bind_param("dii", $price, $user_id, $cart_id);
                $stmt->execute();
            }
        }
    }

    echo json_encode([
        "status" => "success",
        "msg" => "✅ Cập nhật giỏ hàng THÀNH CÔNG (KHÔNG có CSRF protection)"
    ]);
    exit;
}

echo json_encode([
    "status" => "error",
    "msg" => "Action không hợp lệ"
]);
?>
