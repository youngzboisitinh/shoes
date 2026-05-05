<?php
session_start();
include("../configs/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM coupons WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_categories.php");
    exit;
}

// Biến lưu trạng thái sửa
$edit_mode = false;
$edit_coupon = null;

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_coupon = $stmt->get_result()->fetch_assoc();
}

if (isset($_POST['add'])) {
    $code = $_POST['code'];
    $discount = $_POST['discount'];
    $type = $_POST['type'];
    $expiry = $_POST['expiry'];
    $quantity = empty($_POST['quantity']) ? 1000 : $_POST['quantity'];
    $usage_limit = empty($_POST['usage_limit']) ? 10 : $_POST['usage_limit'];
    $stmt = $conn->prepare("INSERT INTO coupons (code, discount, type, expiry, quantity, usage_limit) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssii", $code, $discount, $type, $expiry, $quantity, $usage_limit);
    $stmt->execute();
    header("Location: manage_coupons.php");
    exit;
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $code = $_POST['code'];
    $discount = $_POST['discount'];
    $type = $_POST['type'];
    $expiry = $_POST['expiry'];
    $quantity = empty($_POST['quantity']) ? 1000 : $_POST['quantity'];
    $usage_limit = empty($_POST['usage_limit']) ? 10 : $_POST['usage_limit'];
    $stmt = $conn->prepare("UPDATE coupons SET code=?, discount=?, type=?, expiry=?, quantity=?, usage_limit=? WHERE id=?");
    $stmt->bind_param("sdssiii", $code, $discount, $type, $expiry, $quantity, $usage_limit, $id);
    $stmt->execute();
    header("Location: manage_coupons.php");
    exit;
}

$result = $conn->query("SELECT * FROM coupons ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Quản lý phiếu giảm giá</title>
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
                <h2 class="mb-4"><i class="fa-solid fa-ticket"></i> Quản lý phiếu giảm giá</h2>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <?= $edit_mode ? "✏️ Chỉnh sửa phiếu giảm giá" : "➕ Thêm phiếu giảm giá" ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="id" value="<?= $edit_coupon['id'] ?? '' ?>">
                            <div class="col-md-3">
                                <input type="text" name="code" class="form-control" placeholder="Mã giảm giá"
                                    value="<?= $edit_coupon['code'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="discount" class="form-control" placeholder="Giá trị giảm giá"
                                    value="<?= $edit_coupon['discount'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="expiry" class="form-control"
                                    value="<?= $edit_coupon['expiry'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="quantity" class="form-control"
                                    placeholder="Số lượng (mặc định 1000)"
                                    value="<?= $edit_coupon['quantity'] ?? '' ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="usage_limit" class="form-control"
                                    placeholder="Lượt dùng (mặc định 10)"
                                    value="<?= $edit_coupon['usage_limit'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <span>Kiểu giảm giá</span>
                                <div>
                                    <input type="radio" id="fixed" name="type" value="fixed"
                                        <?= $edit_coupon['type'] ?? '' == 'fixed' ? 'checked' : '' ?> required>
                                    <label for="fixed">Giảm trực tiếp</label>
                                </div>
                                <div>
                                    <input type="radio" id="percent" name="type" value="percent"
                                        <?= $edit_coupon['type'] ?? '' == 'percent' ? 'checked' : '' ?> required>
                                    <label for="percent">Giảm Phần trăm</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php if ($edit_mode): ?>
                                    <button type="submit" name="update" class="btn btn-primary">Cập nhật</button>
                                    <a href="manage_categories.php" class="btn btn-secondary">Hủy</a>
                                <?php else: ?>
                                    <button type="submit" name="add" class="btn btn-success">Thêm</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-list-manage">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Mã</th>
                                <th>Giảm</th>
                                <th>Kiểu giảm</th>
                                <th>Hạn sử dụng</th>
                                <th>Số lượng</th>
                                <th>Lượt dùng</th>
                                <td>Thao tác</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['code'] ?></td>
                                    <td><?= number_format($row['discount'], 0, ',', '.') ?></td>
                                    <td><?= $row['type'] == 'fixed' ? 'Giảm phần trăm' : 'Giảm trực tiếp' ?></td>
                                    <td><?= $row['expiry'] ?></td>
                                    <td><?= $row['quantity'] ?></td>
                                    <td><?= $row['usage_limit'] ?></td>
                                    <td>
                                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>
                                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Xóa danh mục này?')">
                                            <i class="bi bi-trash"></i> Xóa
                                        </a>
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