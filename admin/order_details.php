<?php
session_start();
include("../configs/db.php");

// Kiểm tra admin đăng nhập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$order_id = intval($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header("Location: orders.php");
    exit;
}

// Cập nhật trạng thái đơn hàng (nếu admin gửi form)

if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $msg = "<div class='alert alert-success'>✅ Cập nhật trạng thái thành công!</div>";
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT o.*, u.email, u.full_name, u.phone, u.address
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.id=? LIMIT 1");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "<div class='container my-5'><div class='alert alert-danger'>❌ Không tìm thấy đơn hàng.</div></div>";
    exit;
}

// Lấy sản phẩm trong đơn
$stmt = $conn->prepare("SELECT oi.*, p.name, p.image 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id=?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= $order_id ?> (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>
    <div class="container my-5">
        <div class="d-flex justify-content-between">
            <h2 class="mb-4">Chi tiết đơn hàng #<?= $order['id'] ?></h2>
            <div>
                <a href="manage_orders.php" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Quản lý đơn hàng
                </a>
            </div>
        </div>
        <?php if (isset($msg)) echo $msg; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <p><b>User ID:</b> <?= $order['user_id'] ?></p>
                <p><b>Email:</b> <?= htmlspecialchars($order['email']) ?></p>
                <p><b>Người nhận:</b> <?= htmlspecialchars($order['full_name']) ?></p>
                <p><b>SĐT:</b> <?= htmlspecialchars($order['phone']) ?></p>
                <p><b>Địa chỉ:</b> <?= htmlspecialchars($order['address']) ?></p>
                <p><b>Ngày đặt:</b> <?= $order['created_at'] ?></p>
                <p><b>Thanh toán:</b> <?= htmlspecialchars($order['payment_method']) ?></p>
            </div>
            <div class="col-md-6">
                <form method="POST">
                    <label class="form-label"><b>Trạng thái:</b></label>
                    <select name="status" class="form-select w-50 d-inline-block">
                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>
                            Chờ xử lý
                        </option>
                        <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>
                            Đang xử lý</option>
                        <option value="shipping" <?= $order['status'] == 'shipping' ? 'selected' : '' ?>>
                            Đang giao</option>
                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>
                            Đã giao
                        </option>
                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>
                            Đã hủy
                        </option>
                        <option value="returned" <?= $order['status'] == 'returned' ? 'selected' : '' ?>>
                            Trả hàng
                        </option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-primary">Cập nhật</button>
                </form>
            </div>
        </div>

        <h4>🛒 Sản phẩm trong đơn</h4>
        <table class="table table-bordered text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><img src="<?= $item['image'] ?>" width="80"></td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= number_format($item['price'], 0, ',', '.') ?> VND</td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> VND</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="text-end">
            <h4>Thành tiền: <span class="text-danger"><?= number_format($order['total_price'], 0, ',', '.') ?>
                    VND</span></h4>
        </div>
    </div>
</body>

</html>