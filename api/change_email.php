<?php
include("../configs/db.php");
session_start();

header('Content-Type: application/json');

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    echo json_encode([
        "status" => "error",
        "msg" => "CSRF token không hợp lệ!",
        "isToast" => true
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "warning",
        "msg" => "Vui lòng nhập email hợp lệ.",
        "isToast" => true
    ]);
    exit;
}

$checkSql = "SELECT id FROM users WHERE email=? AND id != ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("si", $email, $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows > 0) {
    echo json_encode([
        "status" => "warning",
        "msg" => "Email này đã được sử dụng.",
        "isToast" => true
    ]);
    exit;
}

$sql = "UPDATE users SET email=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $email, $user_id);
if ($stmt->execute()) {
    $_SESSION['email'] = $email;
    echo json_encode([
        "status" => "success",
        "msg" => "Email đã được cập nhật.",
        "isToast" => true,
        "email" => $email
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "msg" => "Có lỗi khi cập nhật email. Vui lòng thử lại.",
        "isToast" => true
    ]);
}
