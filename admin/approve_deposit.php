<?php
session_start();
include("../configs/db.php");

// Chỉ cho admin truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit;
}

// Duyệt nạp
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);

    // Lấy thông tin yêu cầu
    $sql = "SELECT * FROM deposit_requests WHERE id=? AND status='pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $deposit = $stmt->get_result()->fetch_assoc();

    if ($deposit) {
        $conn->begin_transaction();
        try {
            // Cộng tiền cho user
            $sql = "UPDATE users SET balance = balance + ? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $deposit['amount'], $deposit['user_id']);
            $stmt->execute();

            // Đánh dấu đã duyệt
            $sql = "UPDATE deposit_requests SET status='success' WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $conn->commit();
            $success = "Đã nạp";
        } catch (Exception $e) {
            $conn->rollback();
            die("Lỗi khi duyệt nạp: " . $e->getMessage());
        }
    }
}

// Hủy nạp
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $sql = "UPDATE deposit_requests SET status='failed' WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $error = "Đã hủy đơn nạp";
}

// Lấy danh sách yêu cầu
$sql = "SELECT d.*, u.full_name, u.email 
        FROM deposit_requests d
        JOIN users u ON d.user_id = u.id 
        ORDER BY
        (d.status = 'pending') DESC,
        d.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Duyệt đơn nạp </title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include("../layout/admin_header.php") ?>
            <main class="col-12 col-md-10 ms-sm-auto px-md-4 dashboard-content">
                <h2 class="mb-4"><i class="fa-solid fa-credit-card"></i> Duyệt yêu cầu nạp tiền</h2>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <div class="table-list-manage" style="max-height: 85vh;">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Tên khách hàng</th>
                                <th>Email</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= number_format($row['amount'], 0, ',', '.') ?> VND</td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Chờ duyệt</span>
                                        <?php elseif ($row['status'] == 'success'): ?>
                                            <span class="badge bg-success">Thành công</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Thất bại</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['created_at'] ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <a href="?approve=<?= $row['id'] ?>" class="btn btn-sm btn-success">✔ Duyệt</a>
                                            <a href="?reject=<?= $row['id'] ?>" class="btn btn-sm btn-danger">✘ Hủy</a>
                                        <?php else: ?>
                                            <em>Đã xử lý</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>