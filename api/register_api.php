<?php
include("../configs/db.php");
session_start();

header('Content-Type: application/json');

$full_name    = trim($_POST['full_name']);
$day_of_birth = !empty($_POST['day_of_birth']) ? $_POST['day_of_birth'] : NULL;
$email        = trim($_POST['email']);
$password     = trim($_POST['password']);
$confirm      = trim($_POST['confirm']);

if (!$full_name || !$password || !$email) {
    echo json_encode([
        "status" => "warning",
        "title" => "",
        "msg" => "Vui lòng điền đầy đủ thông tin bắt buộc.",
    ]);
    exit;
}

if ($password !== $confirm) {
    echo json_encode([
        "status" => "error",
        "title" => "",
        "msg" => "Mật khẩu không khớp.",
    ]);
    exit;
}
// kiểm tra email tồn tại chưa
$stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$check = $stmt->get_result()->fetch_assoc();

if ($check) {
    echo json_encode([
        "status" => "warning",
        "title" => "",
        "msg" => "Email đã tồn tại.",
    ]);
    exit;
}
// hash mật khẩu để bảo mật
//$hashPassword = password_hash($password, PASSWORD_BCRYPT);

// thêm user mới
$stmt = $conn->prepare("INSERT INTO users (full_name, day_of_birth, email, password, role) VALUES (?, ?, ?, ?, 'user')");
$stmt->bind_param("ssss", $full_name, $day_of_birth, $email, $password);

if ($stmt->execute()) {
    $_SESSION['username']     = $email;
    $_SESSION['role']         = "user";
    $_SESSION['full_name']    = $full_name;
    $_SESSION['day_of_birth'] = $day_of_birth;
    echo json_encode([
        "status" => "success",
        "msg" => "Đăng ký tài khoản thành công",
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "msg" => "Có lỗi xảy ra. Vui lòng thử lại sau!",
    ]);
}
