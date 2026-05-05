<?php
session_start();
include("../configs/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../account/login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$email     = $_SESSION['email'] ?? '';
$balance   = floatval($_SESSION['balance'] ?? 0);
$full_name = $_SESSION['full_name'] ?? '';
$phone     = $_SESSION['phone'] ?? '';
$address   = $_SESSION['address'] ?? '';

$referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
$msg = null;
$error = null;

// Lấy product_id, variant_id, qty từ URL
$product_id = intval($_GET['id'] ?? 0);
$variant_id = !empty($_GET['variant']) ? intval($_GET['variant']) : null;
$qty        = max(1, intval($_GET['qty'] ?? 1));

if ($product_id <= 0) {
    $_SESSION['data_msg'] = json_encode([
        "status" => "error",
        "msg" => "Thiếu ID sản phẩm",
        "isToast" => true
    ]);
    header("Location: $referer");
    exit;
}

// Lấy thông tin sản phẩm
if ($variant_id) {
    $stmt = $conn->prepare("SELECT p.id as product_id, p.name, p.image,
                                v.id as variant_id, v.name as variant_name, v.price, v.stock
                        FROM products p
                        JOIN product_variants v ON v.product_id = p.id
                        WHERE p.id=? AND v.id=? LIMIT 1");
    $stmt->bind_param("ii", $product_id, $variant_id);
} else {
    $stmt = $conn->prepare("SELECT p.id as product_id, p.name, p.image,
                                NULL as variant_id, NULL as variant_name, p.price, p.stock
                        FROM products p
                        WHERE p.id=? LIMIT 1");
    $stmt->bind_param("i", $product_id);
}
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) {
    $_SESSION['data_msg'] = json_encode([
        "status" => "error",
        "msg" => "Sản phẩm không tồn tại hoặc biến thể không hợp lệ",
        "isToast" => true
    ]);
    header("Location: $referer");
    exit;
}

if ($product['stock'] <= 0) {
    $_SESSION['data_msg'] = json_encode([
        "status" => "error",
        "msg" => "Sản phẩm này đã hết hàng",
        "isToast" => true
    ]);
    header("Location: $referer");
    exit;
}

// Xử lý áp dụng coupon
if (isset($_POST['apply_coupon'])) {
    $code = trim($_POST['coupon']);
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code=? LIMIT 1");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();
    if ($coupon) {
        $_SESSION['coupon'] = $coupon;
    } else {
        $_SESSION['coupon'] = null;
    }
}

// Xử lý đặt hàng
if (isset($_POST['checkout'])) {
    $qty        = max(1, intval($_POST['quantity']));
    $variant_id = !empty($_POST['variant_id']) ? intval($_POST['variant_id']) : $variant_id;
    $address    = trim($_POST['address']);
    $phone      = trim($_POST['phone']);
    $payment    = $_POST['payment'];

    // Lấy lại thông tin sản phẩm theo variant đã chọn để tránh tin tưởng dữ liệu client
    if ($variant_id) {
        $stmt = $conn->prepare("SELECT p.id as product_id, p.name, p.image, v.id as variant_id, v.name as variant_name, v.price, v.stock FROM products p JOIN product_variants v ON v.product_id = p.id WHERE p.id = ? AND v.id = ? LIMIT 1");
        $stmt->bind_param("ii", $product_id, $variant_id);
    } else {
        $stmt = $conn->prepare("SELECT p.id as product_id, p.name, p.image, NULL as variant_id, NULL as variant_name, p.price, p.stock FROM products p WHERE p.id = ? LIMIT 1");
        $stmt->bind_param("i", $product_id);
    }
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        $error = "❌ Sản phẩm không tồn tại hoặc biến thể không hợp lệ.";
    } elseif ($qty > intval($product['stock'])) {
        $error = "❌ Sản phẩm không đủ hàng.";
    } else {
        $total = floatval($product['price']) * $qty;

        $discount = 0;
        if (isset($_SESSION['coupon'])) {
            $coupon = $_SESSION['coupon'];
            if (!empty($coupon['expiry']) && strtotime($coupon['expiry']) <= time()) {
                unset($_SESSION['coupon']);
            } else {
                if ($coupon['type'] === 'percent') {
                    $discount = ($total * floatval($coupon['discount'])) / 100.0;
                } else {
                    $discount = floatval($coupon['discount']);
                }
            }
        }
        $final_total = max(0.0, $total - $discount);

        $conn->begin_transaction();
        try {
            if ($payment === "BALANCE" && $final_total > $balance) {
                throw new Exception("Số dư không đủ.");
            }

            $stmt = $conn->prepare("INSERT INTO orders (user_id, payment_method, total_price, status, created_at)
                            VALUES (?,?,?, 'pending', NOW())");
            $stmt->bind_param("isd", $user_id, $payment, $final_total);
            $stmt->execute();
            $order_id = $conn->insert_id;

            $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price)
                            VALUES (?,?,?,?,?)");
            $stmt2->bind_param("iiiid", $order_id, $product['product_id'], $variant_id, $qty, $product['price']);
            $stmt2->execute();

            if ($variant_id) {
                $stmt3 = $conn->prepare("UPDATE product_variants SET stock = stock - ? WHERE id=?");
                $stmt3->bind_param("ii", $qty, $variant_id);
            } else {
                $stmt3 = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id=?");
                $stmt3->bind_param("ii", $qty, $product['product_id']);
            }
            $stmt3->execute();

            if ($payment === "BALANCE") {
                $stmtBal = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id=?");
                $stmtBal->bind_param("di", $final_total, $user_id);
                $stmtBal->execute();
                $_SESSION['balance'] = floatval($balance - $final_total);
            }

            $conn->commit();
            unset($_SESSION['coupon']);

            $msg = "<div class='card shadow-lg border-success'>
            <div class='card-body text-center'>
                <h3 class='text-success'>✅ Đặt hàng thành công!</h3>
                <p class='lead'>Cảm ơn bạn <b>" . htmlspecialchars($full_name) . "</b> đã mua sắm tại cửa hàng.</p>
                <p>Mã đơn hàng của bạn: <b>#{$order_id}</b></p>
                <a href='products.php' class='btn btn-primary mt-3'>Tiếp tục mua sắm</a>
                <a href='orders.php' class='btn btn-outline-success mt-3'>Xem đơn hàng của tôi</a>
            </div>
            </div>";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "<div class='alert alert-danger text-center'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Mua hàng</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include("../layout/header.php"); ?>

    <div class="container" style="padding-top: 80px;">
        <?php if (!empty($msg)): ?>
            <?= $msg ?>
        <?php else: ?>
            <h2>🛒 Xác nhận thanh toán</h2>
            <?php if (!empty($error)) echo $error; ?>

            <form method="post">
                <table class="table table-bordered text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Ảnh</th>
                            <th>Tên</th>
                            <th>Phân loại</th>
                            <th>Số lượng</th>
                            <th>Giá</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><img src="<?= htmlspecialchars($product['image']) ?>" width="80"></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td>
                                <?php
                                $vs = $conn->query("SELECT id,name FROM product_variants WHERE product_id=" . (int)$product['product_id']);
                                if ($vs->num_rows > 0): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <select name="variant_id" class="form-select">
                                            <?php while ($v = $vs->fetch_assoc()): ?>
                                                <option value="<?= $v['id'] ?>" <?= ($variant_id == $v['id'] ? 'selected' : '') ?>>
                                                    <?= htmlspecialchars($v['name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <!-- Nút mở bảng size -->
                                        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#sizeChartModal">
                                            Bảng size
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="variant_id" value="">
                                    Không có
                                <?php endif; ?>
                            </td>
                            <td><span><?= $qty ?> </span></td>
                            <td><?= number_format($product['price'], 0, ',', '.') ?> VND</td>
                        </tr>
                    </tbody>
                </table>
                <input type="hidden" name="quantity" value="<?= $qty ?>" class="form-control w-50 mx-auto">
                <div class="mb-3">
                    <label>Địa chỉ giao hàng</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($address) ?>" class="form-control"
                        required>
                </div>
                <div class="mb-3">
                    <label>SĐT</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Thanh toán</label>
                    <select name="payment" class="form-select">
                        <option value="COD">Thanh toán khi nhận hàng</option>
                        <option value="BALANCE">Số dư tài khoản</option>
                    </select>
                </div>
                <button type="submit" name="checkout" class="btn btn-success w-100">Xác nhận mua hàng</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Modal bảng size -->
    <div class="modal fade" id="sizeChartModal" tabindex="-1" aria-labelledby="sizeChartLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sizeChartLabel">Bảng size giày tham khảo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Size (VN)</th>
                                <th>Size (US)</th>
                                <th>Size (EU)</th>
                                <th>Chiều dài chân (cm)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>38</td>
                                <td>6</td>
                                <td>39</td>
                                <td>24.0</td>
                            </tr>
                            <tr>
                                <td>39</td>
                                <td>7</td>
                                <td>40</td>
                                <td>24.5</td>
                            </tr>
                            <tr>
                                <td>40</td>
                                <td>7.5</td>
                                <td>41</td>
                                <td>25.0</td>
                            </tr>
                            <tr>
                                <td>41</td>
                                <td>8</td>
                                <td>42</td>
                                <td>25.5</td>
                            </tr>
                            <tr>
                                <td>42</td>
                                <td>9</td>
                                <td>43</td>
                                <td>26.0</td>
                            </tr>
                            <tr>
                                <td>43</td>
                                <td>10</td>
                                <td>44</td>
                                <td>27.0</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="text-muted text-center">⚠️ Lưu ý: Bảng size chỉ mang tính chất tham khảo, có thể chênh
                        lệch tuỳ mẫu giày.</p>
                </div>
            </div>
        </div>
    </div>

    <?php include("../layout/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>