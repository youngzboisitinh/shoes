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

$email    = trim($_POST['email']);
$password = trim($_POST['password']);

if (!$password || !$email) {
    echo json_encode([
        "status" => "warning",
        "title" => "",
        "msg" => "Vui lòng điền đầy đủ thông tin bắt buộc.",
        "isToast" => true
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ⚠️ Hiện tại so sánh plain-text. Nên nâng cấp thành password_hash() sau
if ($user && $user['password'] == $password) {
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['email']       = $user['email'];
    $_SESSION['balance']     = $user['balance'];
    $_SESSION['role']        = $user['role'];
    $_SESSION['full_name']   = $user['full_name'];
    $_SESSION['day_of_birth'] = $user['day_of_birth'];
    $_SESSION['phone']       = $user['phone'];
    $_SESSION['address']     = $user['address'];
    $_SESSION['created_at']  = $user['created_at'];

    $token = bin2hex(random_bytes(32));
    setcookie("refresh_token", $token, time() + 60 * 60 * 24 * 7, "/", "", false, true);
    $stmt = $conn->prepare("UPDATE users SET refresh_token=? WHERE id=?");
    $stmt->bind_param("si", $token, $user['id']);
    $stmt->execute();

    $_SESSION['data_msg'] = json_encode([
        "status" => "success",
        "msg" => "Đăng nhập thành công",
        "isToast" => true
    ]);

    echo $_SESSION['data_msg'];
} else {
    echo json_encode([
        "status" => "error",
        "title" => "Đăng nhập thất bại",
        "msg" => "Email hoặc mật khẩu không chính xác!",
        "isToast" => true
    ]);
}
