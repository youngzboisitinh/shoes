<?php
session_start();
include("../configs/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$uploadDir = __DIR__ . "/../uploads/sliders/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

/* ============ HÀM QUẢN LÝ ẢNH (theo logic manage_products) ============ */
function addSliderImage($file)
{
    global $uploadDir;

    $hash = sha1_file($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = $hash . "." . $ext;
    $filePath = $uploadDir . $fileName;
    $metaPath = $filePath . ".meta";

    if (!file_exists($filePath)) {
        move_uploaded_file($file['tmp_name'], $filePath);
        file_put_contents($metaPath, json_encode([
            "ref_count" => 1,
            "uploaded_at" => date("Y-m-d H:i:s")
        ], JSON_PRETTY_PRINT));
    } else {
        $meta = file_exists($metaPath) ? json_decode(file_get_contents($metaPath), true) : ["ref_count" => 0];
        $meta['ref_count']++;
        file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
    }

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $baseUrl .= "://" . $_SERVER['HTTP_HOST'];
    $projectRoot = dirname(dirname($_SERVER['SCRIPT_NAME']));
    return $baseUrl . $projectRoot . "/uploads/sliders/" . $fileName;
}

function removeSliderImage($url)
{
    $parsedUrl = parse_url($url, PHP_URL_PATH);
    $filePath = $_SERVER['DOCUMENT_ROOT'] . $parsedUrl;
    $metaPath = $filePath . ".meta";

    if (file_exists($metaPath)) {
        $meta = json_decode(file_get_contents($metaPath), true);
        $meta['ref_count']--;
        if ($meta['ref_count'] <= 0) {
            if (file_exists($filePath)) unlink($filePath);
            unlink($metaPath);
        } else {
            file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
        }
    }
}

/* ============ ACTIONS ============ */

// Xóa
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $row = $conn->query("SELECT image FROM carousel_home WHERE id=$id")->fetch_assoc();
    if ($row) {
        removeSliderImage($row['image']);
        $conn->query("DELETE FROM carousel_home WHERE id=$id");
    }
    header("Location: manage_carousel.php");
    exit;
}

// Thêm
if (isset($_POST['add'])) {
    $title = $_POST['title'];
    $text  = $_POST['text'];
    $imageUrl = "";

    if (!empty($_FILES['image']['name'])) {
        $imageUrl = addSliderImage($_FILES['image']);
    }

    $stmt = $conn->prepare("INSERT INTO carousel_home(title,text,image) VALUES(?,?,?)");
    $stmt->bind_param("sss", $title, $text, $imageUrl);
    $stmt->execute();
    header("Location: manage_carousel.php");
    exit;
}

// Update
if (isset($_POST['update'])) {
    $id    = intval($_POST['id']);
    $title = $_POST['title'];
    $text  = $_POST['text'];

    $row = $conn->query("SELECT * FROM carousel_home WHERE id=$id")->fetch_assoc();
    $imageUrl = $row['image'];

    if (!empty($_FILES['image']['name'])) {
        removeSliderImage($row['image']);
        $imageUrl = addSliderImage($_FILES['image']);
    }

    $stmt = $conn->prepare("UPDATE carousel_home SET title=?, text=?, image=? WHERE id=?");
    $stmt->bind_param("sssi", $title, $text, $imageUrl, $id);
    $stmt->execute();
    header("Location: manage_carousel.php");
    exit;
}

// Lấy dữ liệu
$list = $conn->query("SELECT * FROM carousel_home ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Quản lý Carousel</title>
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
                <h2 class="mb-4"><i class="fa-solid fa-images"></i> Quản lý Slider Trang chủ</h2>

                <!-- Form thêm -->
                <form method="post" enctype="multipart/form-data" class="mb-4">
                    <div class="row mb-2">
                        <div class="col"><input name="title" class="form-control" placeholder="Tiêu đề" required></div>
                        <div class="col"><input type="file" name="image" class="form-control" required></div>
                    </div>
                    <textarea name="text" class="form-control mb-2" placeholder="Nội dung"></textarea>
                    <button type="submit" name="add" class="btn btn-success">Thêm Slide</button>
                </form>

                <!-- Danh sách -->
                <div class="table-list-manage" style="max-height: 80vh;">
                    <table class="table table-bordered text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Ảnh</th>
                                <th>Tiêu đề</th>
                                <th>Nội dung</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $list->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><img src="<?= $row['image'] ?>" width="120"></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['text']) ?></td>
                                    <td>
                                        <!-- Nút sửa bật modal -->
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#edit<?= $row['id'] ?>">Sửa</button>
                                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Xóa slide này?')">Xóa</a>

                                        <!-- Modal edit -->
                                        <div class="modal fade" id="edit<?= $row['id'] ?>">
                                            <div class="modal-dialog">
                                                <form method="post" enctype="multipart/form-data" class="modal-content">
                                                    <div class="modal-header">
                                                        <h5>Sửa slide #<?= $row['id'] ?></h5>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <div class="mb-2"><input name="title" class="form-control"
                                                                value="<?= htmlspecialchars($row['title']) ?>"></div>
                                                        <div class="mb-2"><textarea name="text"
                                                                class="form-control"><?= htmlspecialchars($row['text']) ?></textarea>
                                                        </div>
                                                        <div class="mb-2"><input type="file" name="image"
                                                                class="form-control">
                                                        </div>
                                                        <img src="<?= $row['image'] ?>" width="150" class="mt-1">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" name="update"
                                                            class="btn btn-primary">Lưu</button>
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Hủy</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
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