<?php
session_start();
include("../configs/db.php");

// Chỉ admin mới được vào
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Xóa contact
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM contact_forms WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: contact_forms.php");
    exit;
}


// Lấy danh sách contact_forms
$result = $conn->query("SELECT * FROM contact_forms ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Tin nhắn liên hệ</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .message-preview {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            /* Giới hạn 2 dòng */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            max-height: 3em;
            /* 2 dòng ~ 2 * line-height (1.5em) */
            line-height: 1.5em;
        }

        .read-more {
            /* Ẩn mặc định */
            display: none;
            color: blue;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include("../layout/admin_header.php") ?>
            <main class="col-12 col-md-10 ms-sm-auto px-md-4 dashboard-content">
                <h2 class="mb-4"><i class="fa-solid fa-envelope"></i> Tin nhắn từ Form liên hệ</h2>

                <!-- Danh sách contact -->
                <div class="table-list-manage">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Nội dung tin nhắn</th>
                                <th>Ngày gửi</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="text-start">
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= $row['email'] ?></td>
                                    <td><?= $row['phone'] ?? '(Trống)' ?></td>
                                    <td class="msg-content">
                                        <div class="message-preview">
                                            <?= htmlspecialchars($row['message']) ?>
                                        </div>
                                        <a href="#" class="read-more"
                                            data-message="<?= htmlspecialchars($row['message']) ?>">...đọc thêm</a>
                                    </td>
                                    <td><?= $row['created_at'] ?></td>
                                    <td>
                                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Xóa tin nhắn này?')">
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
    <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nội dung tin nhắn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalMessageContent"></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll(".msg-content").forEach(cell => {
            const preview = cell.querySelector(".message-preview");
            const readMore = cell.querySelector(".read-more");

            if (preview && readMore) {
                if (preview.scrollHeight > preview.clientHeight) {
                    readMore.style.display = "inline";
                }
            }
        });

        document.querySelectorAll(".read-more").forEach(btn => {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                const msg = this.getAttribute("data-message");
                document.getElementById("modalMessageContent").textContent = msg;
                const modal = new bootstrap.Modal(document.getElementById("messageModal"));
                modal.show();
            });
        });
    </script>
</body>

</html>