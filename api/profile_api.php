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

/* ------------------- Cập nhật thông tin cá nhân ------------------- */
if ($action == "update_profile") {
    if (empty($_POST['full_name']) || trim($_POST['full_name']) == "") {
        echo json_encode([
            "status" => "warning",
            "msg" => "Vui lòng điền đầy đủ thông tin bắt buộc.",
        ]);
        exit;
    }

    $full_name    = trim($_POST['full_name']);
    $day_of_birth = !empty($_POST['day_of_birth']) ? $_POST['day_of_birth'] : NULL;
    $phone        = trim($_POST['phone']);
    $address      = trim($_POST['address']);

    $sql = "UPDATE users SET full_name=?, day_of_birth=?, phone=?, address=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $full_name, $day_of_birth, $phone, $address, $user_id);
    $stmt->execute();

    $_SESSION['full_name']    = $full_name;
    $_SESSION['day_of_birth'] = $day_of_birth;
    $_SESSION['phone']        = $phone;
    $_SESSION['address']      = $address;

    echo json_encode([
        "status" => "success",
        "msg" => "Đã cập nhật thông tin!",
        "isToast" => true,
        "full_name" => $full_name,
        "day_of_birth" => $day_of_birth,
        "phone" => $phone,
        "address" => $address
    ]);
}

/* ------------------- Đổi mật khẩu ------------------- */
if ($action == "change_password") {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    if (
        empty($current_password) || trim($current_password) == ""
        || empty($new_password) || trim($new_password) == ""
    ) {
        echo json_encode([
            "status" => "warning",
            "msg" => "Vui lòng nhập mật khẩu.",
        ]);
        exit;
    }

    $sql = "SELECT password FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || $current_password != $user['password']) {
        echo json_encode([
            "status" => "error",
            "title" => "Thất bại",
            "msg" => "Mật khẩu hiện tại không chính xác.",
        ]);
    } elseif ($new_password !== $confirm_password) {
        echo json_encode([
            "status" => "warning",
            "title" => "Thất bại",
            "msg" => "Mật mới không khớp nhau.",
        ]);
    } elseif ($new_password == $user['password']) {
        echo json_encode([
            "status" => "info",
            "title" => "Thông báo",
            "msg" => "Mật khẩu không thay đổi.",
        ]);
    } else {
        //  $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_password, $user_id);
        $stmt->execute();
        echo json_encode([
            "status" => "success",
            "msg" => "Đổi mật khẩu thành công",
            "isToast" => true
        ]);
    }
}

/* ------------------- Nạp tiền ------------------- */
if ($action == "deposit_money") {

    $amount = intval($_POST['amount']);
    if ($amount <= 0) {
        echo json_encode([
            "status" => "warning",
            "title" => "Thông báo",
            "msg" => "Số tiền nạp phải lớn hơn 0",
        ]);
    } else {
        $sql = "INSERT INTO deposit_requests (user_id, amount, status) VALUES (?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $amount);
        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "title" => "Thành công",
                "msg" => "Đã tạo yêu cầu nạp " . number_format($amount, 0, ',', '.') . " VND",
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "msg" => "Có lỗi xảy ra. Vui lòng thử lại sau!",
            ]);
        }
    }
}
