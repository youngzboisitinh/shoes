<?php
session_start();
include("../configs/db.php");

// Chỉ admin mới được vào
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$msg = NULL;
$error = NULL;

// Xóa user
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $msg = "Xóa người dùng #{$id} thành công";
}

// Đổi role
if (isset($_GET['toggle_role'])) {
    $id = intval($_GET['toggle_role']);
    $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $new_role = ($result['role'] === 'admin') ? 'user' : 'admin';

    $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param("si", $new_role, $id);
    $stmt->execute();
    $msg = "Đổi quyền thành công";
}

// Cập nhật user
if (isset($_POST['update_user'])) {
    $id   = intval($_POST['id']);
    $name = $_POST['full_name'];
    if (trim($name) == '') {
        $error = "Vui lòng nhập tên cho người dùng";
    } else {
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $dob = $_POST['day_of_birth'];
        $role = $_POST['role'] ?? 'user';
        $role = ($role  === 'admin') ? 'admin' : 'user'; // chỉ cho phép 2 giá trị

        $stmt = $conn->prepare("UPDATE users 
        SET full_name=?, phone=?, address=?, day_of_birth=?, role=? 
        WHERE id=?");
        $stmt->bind_param("ssssss", $name, $phone, $address, $dob, $role, $id);
        $stmt->execute();

        $msg = "Cập nhật người dùng <strong>{$name} </strong>thành công";
    }
}

// Tạo mới user
if (isset($_POST['add_user'])) {
    $id   = intval($_POST['id']);
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    if (trim($name) == '' || trim($email) == '' || trim($password) == '') {
        $error = "Vui lòng nhập đầy đủ thông tin";
    } else {
        // check esist
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Email đã tồn tại";
        } else {
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            $dob = $_POST['day_of_birth'];
            $balance = floatval($_POST['balance']);
            $role = $_POST['role'] ?? 'user';
            $role = ($role === 'admin') ? 'admin' : 'user'; // chỉ cho phép 2 giá trị
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address, day_of_birth, balance, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $name, $email, $password, $phone, $address, $dob, $balance, $role);
            $stmt->execute();
            $msg = "Đã thêm người dùng <strong>{$name} </strong>thành công";
        }
    }
}

// Nạp tiền
if (isset($_POST['deposit_money'])) {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $id = $user['id'];
        $amount = floatval($_POST['amount']);
        if ($amount > 0) {
            $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id=?");
            $stmt->bind_param("di", $amount, $id);
            $stmt->execute();
            $msg = "Đã nạp " . number_format($amount, 0, ',', '.') . " VND cho user #{$id}";
        } else {
            $error = "Số tiền nạp không hợp lệ!";
        }
    } else {
        $error = "Không tìm thấy người dùng!";
    }
}

// Trừ tiền
if (isset($_POST['withdraw_money'])) {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $id = $user['id'];
        $amount = floatval($_POST['amount']);
        if ($amount > 0) {
            $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id=?");
            $stmt->bind_param("di", $amount, $id);
            $stmt->execute();
            $msg = "Đã trừ " . number_format($amount, 0, ',', '.') . " VND user #{$id}";
        } else {
            $error = "Số tiền trừ không hợp lệ!";
        }
    } else {
        $error = "Không tìm thấy người dùng!";
    }
}



// Lấy danh sách user
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Quản lý tài khoản</title>
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
                <?php if (isset($msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <span><?= $msg ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <h2 class="mb-4"><i class="fa-solid fa-users-gear"></i> Quản lý người dùng</h2>

                <div class="mb-3 d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fa-solid fa-user-plus"></i> Thêm người dùng
                    </button>
                    <button class="btn btn-sm btn-success btn-deposit" data-bs-toggle="modal"
                        data-bs-target="#depositModal">
                        <i class="fa-solid fa-money-bill-wave"></i> Nạp tiền
                    </button>
                    <button class="btn btn-sm btn-warning btn-withdraw" data-bs-toggle="modal"
                        data-bs-target="#withdrawModal">
                        <i class="fa-solid fa-minus"></i> Trừ tiền
                    </button>

                </div>
                <div class="table-list-manage" style="max-height: 80vh;">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Số dư</th>
                                <th>Ngày sinh</th>
                                <th>Địa chỉ</th>
                                <th>Điện thoại</th>
                                <th>Quyền</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= number_format($row['balance'], 0, ',', '.') . ' VND' ?></td>
                                    <td><?= $row['day_of_birth'] ?></td>
                                    <td><?= htmlspecialchars($row['address']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td>
                                        <?php if ($row['role'] === 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['created_at'] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary btn-edit" data-id="<?= $row['id'] ?>"
                                            data-name="<?= htmlspecialchars($row['full_name']) ?>"
                                            data-phone="<?= htmlspecialchars($row['phone']) ?>"
                                            data-address="<?= htmlspecialchars($row['address']) ?>"
                                            data-dob="<?= $row['day_of_birth'] ?>" data-role="<?= $row['role'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editUserModal">
                                            <i class="fa fa-edit"></i> Sửa
                                        </button>
                                        <a href="?delete=<?= $row['id'] ?>"
                                            class="btn btn-sm btn-danger <?php if ($row['id'] == $_SESSION['user_id']) echo 'disabled'; ?>"
                                            onclick="return confirm('Xóa tài khoản này?')">
                                            <i class="fa fa-trash"></i> Xóa
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
    <!-- Modal Edit User (dùng chung) -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật tài khoản</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-2">
                    <input type="hidden" name="id" id="user_id">
                    <div class="col-md-6">
                        <label class="form-label">Họ tên</label>
                        <input type="text" name="full_name" id="user_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone" id="user_phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ngày sinh</label>
                        <input type="date" name="day_of_birth" id="user_dob" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Địa chỉ</label>
                        <input type="text" name="address" id="user_address" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phân quyền</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role_user" value="user">
                            <label class="form-check-label" for="role_user">User</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role_admin" value="admin">
                            <label class="form-check-label" for="role_admin">Admin</label>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_user" class="btn btn-success">Lưu</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Tạo User  -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm tài khoản</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-2">
                    <input type="hidden" name="id" id="user_id">
                    <div class="col-md-6">
                        <label class="form-label">Họ tên</label>
                        <input type="text" name="full_name" id="user_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="text" name="email" id="user_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mật khẩu</label>
                        <input type="text" name="password" id="user_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone" id="user_phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ngày sinh</label>
                        <input type="date" name="day_of_birth" id="user_dob" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số dư</label>
                        <input type="number" step="0.01" name="balance" id="user_balance" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Địa chỉ</label>
                        <input type="text" name="address" id="user_address" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phân quyền</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role_user1" value="user">
                            <label class="form-check-label" for="role_user1">User</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="role_admin1" value="admin">
                            <label class="form-check-label" for="role_admin1">Admin</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_user" class="btn btn-success">Thêm</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="depositModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">💵 Nạp tiền</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Email người nhận:</label>
                        <input type="text" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số tiền nạp</label>
                        <input type="number" name="amount" class="form-control" required min="1000" max="100000000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="deposit_money" class="btn btn-success">Nạp</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                </div>
            </form>
        </div>
    </div>


    <div class="modal fade" id="withdrawModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">💸 Trừ tiền</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Email người bị trừ:</label>
                        <input type="text" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số tiền trừ</label>
                        <input type="number" name="amount" class="form-control" required min="1000" max="100000000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="withdraw_money" class="btn btn-warning">Trừ</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const editButtons = document.querySelectorAll(".btn-edit");
            editButtons.forEach(btn => {
                btn.addEventListener("click", function() {
                    document.getElementById("user_id").value = this.dataset.id;
                    document.getElementById("user_name").value = this.dataset.name;
                    document.getElementById("user_phone").value = this.dataset.phone;
                    document.getElementById("user_address").value = this.dataset.address;
                    document.getElementById("user_dob").value = this.dataset.dob;

                    // Set role radio
                    if (this.dataset.role === "admin") {
                        document.getElementById("role_admin").checked = true;
                    } else {
                        document.getElementById("role_user").checked = true;
                    }
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>