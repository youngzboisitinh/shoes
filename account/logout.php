<?php
session_start();
include("../configs/db.php");

// Xóa cookie rf nếu có
setcookie("refresh_token", "", -1, "/", "", false, true);
$stmt = $conn->prepare("UPDATE users SET refresh_token = NULL WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

// Xóa toàn bộ session
$_SESSION = [];
session_unset();
session_destroy();
// Chuyển về trang chủ
header("Location: ./login.php");
exit;
