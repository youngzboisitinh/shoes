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

if ($action == "buy_now") {
    $product_id = intval($_POST['id'] ?? 0);
    $variant_id = !empty($_POST['variant']) ? intval($_POST['variant']) : null;
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

    if ($product['stock'] <= 0) {
        echo json_encode([
            "status" => "warning",
            "msg" => "Sản phẩm này đã hết hàng",
            "isToast" => true
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "msg" => "Đang chuyển hướng tới trang mua hàng",
            "isToast" => true
        ]);
    }
}
