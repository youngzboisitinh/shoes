<?php
session_start();
include("../configs/db.php");

$user_id = $_SESSION['user_id'] ?? '';
if (!$user_id) {
  header("Location: login.php");
  exit;
}

// Lấy danh sách yêu cầu
$sql = "SELECT d.*, u.full_name, u.email 
        FROM deposit_requests d 
        JOIN users u ON d.user_id = u.id
        WHERE u.id = ?
        ORDER BY d.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id); // chỉ cần kiểu int cho user_id
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>Lịch sử nạp</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
  <?php include("../layout/header.php"); ?>

  <div class="container" style="padding-top: 80px;">
    <div class="d-flex justify-content-between">
      <h2>Lịch sử nạp</h2>
      <a href="profile.php" class="btn btn-outline-primary mb-3"><i class="fa-solid fa-arrow-left"></i>
        Trang cá nhân</a>
    </div>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>Mã giao dịch</th>
          <th>Số tiền</th>
          <th>Trạng thái</th>
          <th>Thời gian</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
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
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>