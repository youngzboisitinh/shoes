<?php
session_start();
include("../configs/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../index.php");
  exit;
}

$uploadDir = __DIR__ . "/../uploads/products/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$edit_mode = false;
$edit_product = null;
$product_images = [];
$product_variants = [];

/* ================== HÀM QUẢN LÝ ẢNH ================== */
function addImage($productId, $file, $isFirst = false)
{
  global $conn, $uploadDir;

  // kiểm tra file hợp lệ
  if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;

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
    $meta['ref_count'] = ($meta['ref_count'] ?? 0) + 1;
    file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
  }

  // build url
  $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
  $baseUrl .= "://" . $_SERVER['HTTP_HOST'];
  $projectRoot = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
  $url = $baseUrl . $projectRoot . "/uploads/products/" . $fileName;

  $stmt = $conn->prepare("INSERT INTO product_images (product_id, url) VALUES (?, ?)");
  $stmt->bind_param("is", $productId, $url);
  $stmt->execute();

  if ($isFirst) {
    $stmt2 = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
    $stmt2->bind_param("si", $url, $productId);
    $stmt2->execute();
  }

  return $url;
}

function removeImage($imageId, $productId)
{
  global $conn, $uploadDir;

  $stmt = $conn->prepare("SELECT * FROM product_images WHERE id = ? AND product_id = ?");
  $stmt->bind_param("ii", $imageId, $productId);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  if (!$row) return;

  $parsedPath = parse_url($row['url'], PHP_URL_PATH);
  $filePath = $_SERVER['DOCUMENT_ROOT'] . $parsedPath;
  $metaPath = $filePath . ".meta";

  if (file_exists($metaPath)) {
    $meta = json_decode(file_get_contents($metaPath), true);
    $meta['ref_count'] = ($meta['ref_count'] ?? 1) - 1;
    if ($meta['ref_count'] <= 0) {
      if (file_exists($filePath)) @unlink($filePath);
      @unlink($metaPath);
    } else {
      file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT));
    }
  }

  $stmt2 = $conn->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?");
  $stmt2->bind_param("ii", $imageId, $productId);
  $stmt2->execute();

  // Nếu xóa ảnh đang là ảnh chính trên products.image, ta có thể set null hoặc set ảnh khác (ở đây set NULL)
  $stmt3 = $conn->prepare("SELECT image FROM products WHERE id = ?");
  $stmt3->bind_param("i", $productId);
  $stmt3->execute();
  $r = $stmt3->get_result()->fetch_assoc();
  if ($r && $r['image'] === $row['url']) {
    $conn->query("UPDATE products SET image = NULL WHERE id = " . intval($productId));
    // Optionally set another image as main:
    $other = $conn->query("SELECT url FROM product_images WHERE product_id=" . intval($productId) . " LIMIT 1")->fetch_assoc();
    if ($other) {
      $stmt4 = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
      $stmt4->bind_param("si", $other['url'], $productId);
      $stmt4->execute();
    }
  }
}

/* ================== XỬ LÝ HÀNH ĐỘNG ================== */

// Xóa sản phẩm
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  // xóa ảnh liên quan (DB + file) trước khi xóa sản phẩm
  $imgs = $conn->query("SELECT id FROM product_images WHERE product_id=" . $id);
  while ($img = $imgs->fetch_assoc()) {
    removeImage($img['id'], $id);
  }
  $conn->query("DELETE FROM product_variants WHERE product_id=$id");
  $conn->query("DELETE FROM products WHERE id=$id");
  header("Location: manage_products.php");
  exit;
}

// Xóa ảnh riêng
if (isset($_GET['del_img']) && isset($_GET['product'])) {
  removeImage(intval($_GET['del_img']), intval($_GET['product']));
  header("Location: manage_products.php?edit=" . intval($_GET['product']));
  exit;
}

// Xóa biến thể riêng
if (isset($_GET['del_variant']) && isset($_GET['product'])) {
  $vId = intval($_GET['del_variant']);
  $conn->query("DELETE FROM product_variants WHERE id=$vId");
  header("Location: manage_products.php?edit=" . intval($_GET['product']));
  exit;
}

// Chọn sản phẩm để sửa
if (isset($_GET['edit'])) {
  $edit_mode = true;
  $id = intval($_GET['edit']);
  $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $edit_product = $stmt->get_result()->fetch_assoc();
  $product_images = $conn->query("SELECT * FROM product_images WHERE product_id=$id")->fetch_all(MYSQLI_ASSOC);
  $product_variants = $conn->query("SELECT * FROM product_variants WHERE product_id=$id")->fetch_all(MYSQLI_ASSOC);
}

// Thêm sản phẩm
if (isset($_POST['add'])) {
  $name = $_POST['name'] ?? '';
  $price = floatval($_POST['price'] ?? 0);
  $stock = intval($_POST['stock'] ?? 0);
  $desc = $_POST['description'] ?? '';
  $catId = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

  $stmt = $conn->prepare("INSERT INTO products (name, price, stock, description, category_id, created_at) VALUES (?,?,?,?,?,NOW())");
  $stmt->bind_param("sdisi", $name, $price, $stock, $desc, $catId);
  $stmt->execute();
  $productId = $stmt->insert_id;

  // Ảnh
  $firstImageUrl = null;
  if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
    $first = true;
    foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {
      if (!empty($_FILES['images']['name'][$k])) {
        $url = addImage($productId, [
          "name" => $_FILES['images']['name'][$k],
          "tmp_name" => $tmp
        ], $first);
        if ($first && $url) $firstImageUrl = $url;
        $first = false;
      }
    }
    if ($firstImageUrl) {
      $stmt2 = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
      $stmt2->bind_param("si", $firstImageUrl, $productId);
      $stmt2->execute();
    }
  }

  // Biến thể
  if (!empty($_POST['variants']) && is_array($_POST['variants'])) {
    foreach ($_POST['variants'] as $var) {
      if (!empty($var['name'])) {
        $vname = $conn->real_escape_string($var['name']);
        $vstock = intval($var['stock']);
        $vprice = floatval($var['price']);
        $conn->query("INSERT INTO product_variants (product_id, name, stock, price) VALUES ($productId, '$vname', $vstock, $vprice)");
      }
    }
  }

  header("Location: manage_products.php");
  exit;
}

// Cập nhật sản phẩm
if (isset($_POST['update'])) {
  $id = intval($_POST['id']);
  $name = $_POST['name'] ?? '';
  $price = floatval($_POST['price'] ?? 0);
  $stock = intval($_POST['stock'] ?? 0);
  $desc = $_POST['description'] ?? '';
  $catId = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

  $stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=?, description=?, category_id=? WHERE id=?");
  $stmt->bind_param("sdisii", $name, $price, $stock, $desc, $catId, $id);
  $stmt->execute();

  // lấy thông tin ảnh hiện tại để biết có cần gán ảnh mới là ảnh chính
  $cur = $conn->query("SELECT image FROM products WHERE id=" . $id)->fetch_assoc();
  $first = empty($cur['image']);

  // Ảnh
  $firstImageUrl = null;
  if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
    $first = true;
    foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {
      if (!empty($_FILES['images']['name'][$k])) {
        $url = addImage($id, [
          "name" => $_FILES['images']['name'][$k],
          "tmp_name" => $tmp
        ], $first);
        if ($first && $url) $firstImageUrl = $url;
        $first = false;
      }
    }
    if ($firstImageUrl) {
      $stmt2 = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
      $stmt2->bind_param("si", $firstImageUrl, $id);
      $stmt2->execute();
    }
  }

  // Biến thể (giữ cũ + thêm/cập nhật)
  if (!empty($_POST['variants']) && is_array($_POST['variants'])) {
    foreach ($_POST['variants'] as $var) {
      $vname  = $conn->real_escape_string($var['name'] ?? '');
      $vstock = intval($var['stock'] ?? 0);
      $vprice = floatval($var['price'] ?? 0);

      if (!empty($var['id'])) {
        $vid = intval($var['id']);
        $conn->query("UPDATE product_variants SET name='$vname', stock=$vstock, price=$vprice WHERE id=$vid AND product_id=$id");
      } else {
        if (!empty($vname)) {
          $conn->query("INSERT INTO product_variants (product_id, name, stock, price) VALUES ($id, '$vname', $vstock, $vprice)");
        }
      }
    }
  }

  header("Location: manage_products.php?edit=$id");
  exit;
}

// Danh sách danh mục để chọn
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

// Danh sách sản phẩm
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Quản lý sản phẩm</title>
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
        <h2 class="mb-4"><i class="fa-solid fa-boxes-stacked"></i> Quản lý sản phẩm</h2>
        <!-- Form -->
        <div class="card mb-4">
          <div class="card-header bg-dark text-white">
            <?= $edit_mode ? "✏️ Chỉnh sửa sản phẩm" : "➕ Thêm sản phẩm" ?>
          </div>
          <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="id" value="<?= htmlspecialchars($edit_product['id'] ?? '') ?>">
              <div class="row mb-2">
                <div class="col-md-4">
                  <input type="text" name="name" class="form-control" placeholder="Tên sản phẩm"
                    value="<?= htmlspecialchars($edit_product['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                  <input type="number" step="0.01" name="price" class="form-control" placeholder="Giá"
                    value="<?= htmlspecialchars($edit_product['price'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                  <input type="number" name="stock" class="form-control" placeholder="Số lượng"
                    value="<?= htmlspecialchars($edit_product['stock'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                  <select name="category_id" class="form-control" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                      <option value="<?= $cat['id'] ?>"
                        <?= isset($edit_product['category_id']) && $edit_product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>
              </div>

              <div class="row mb-2">
                <div class="col-md-12">
                  <!-- Vùng kéo thả -->
                  <div id="drop-area" class="border rounded p-3 text-center bg-light">
                    <input type="file" id="fileElem" name="images[]" multiple accept="image/*"
                      class="d-none" <?= $edit_mode ? '' : 'required' ?>>
                    <label class="btn btn-outline-primary mt-2" for="fileElem">Chọn ảnh</label>
                    <!-- Preview ảnh mới -->
                    <div id="preview" class="mt-3 d-flex flex-wrap gap-2"></div>
                  </div>

                  <!-- Ảnh đã có trong DB -->
                  <div class="mt-2">
                    <?php foreach ($product_images as $img): ?>
                      <div class="d-inline-block position-relative me-1">
                        <img src="<?= htmlspecialchars($img['url']) ?>" width="70"
                          class="mt-1 border">
                        <a href="?del_img=<?= $img['id'] ?>&product=<?= $edit_product['id'] ?? '' ?>"
                          class="btn btn-sm btn-danger position-absolute top-0 end-0 py-0 px-1">x</a>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>


              <textarea name="description" class="form-control mb-2"
                placeholder="Mô tả"><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>

              <!-- Biến thể -->
              <h6>Phân loại sản phẩm</h6>
              <div id="variant-list">
                <?php if ($edit_mode && $product_variants): ?>
                  <?php foreach ($product_variants as $i => $v): ?>
                    <div class="row mb-2 align-items-center">
                      <input type="hidden" name="variants[<?= $i ?>][id]" value="<?= $v['id'] ?>">
                      <div class="col"><input type="text" name="variants[<?= $i ?>][name]"
                          value="<?= htmlspecialchars($v['name']) ?>" class="form-control"></div>
                      <div class="col"><input type="number" step="0.01" name="variants[<?= $i ?>][price]"
                          value="<?= htmlspecialchars($v['price']) ?>" class="form-control"></div>
                      <div class="col"><input type="number" name="variants[<?= $i ?>][stock]"
                          value="<?= htmlspecialchars($v['stock']) ?>" class="form-control"></div>
                      <div class="col-auto">
                        <a href="?del_variant=<?= $v['id'] ?>&product=<?= $edit_product['id'] ?>"
                          class="btn btn-sm btn-danger">X</a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="row mb-2">
                    <div class="col"><input type="text" name="variants[0][name]"
                        placeholder="Tên biến thể" class="form-control"></div>
                    <div class="col"><input type="number" step="0.01" name="variants[0][price]"
                        placeholder="Giá" class="form-control"></div>
                    <div class="col"><input type="number" name="variants[0][stock]"
                        placeholder="Số lượng" class="form-control"></div>
                  </div>
                <?php endif; ?>
              </div>
              <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addVariant()">+ Thêm
                biến thể</button>

              <?php if ($edit_mode): ?>
                <button type="submit" name="update" class="btn btn-primary">Cập nhật</button>
                <a href="manage_products.php" class="btn btn-secondary">Hủy</a>
              <?php else: ?>
                <button type="submit" name="add" class="btn btn-success">Thêm sản phẩm</button>
              <?php endif; ?>
            </form>
          </div>
        </div>

        <!-- Danh sách -->
        <div class="table-list-manage" style="max-height: 80vh;">
          <table class="table table-bordered text-center align-middle">
            <thead class="table-dark">
              <tr>
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tên</th>
                <th>Giá</th>
                <th>Số lượng</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td>
                    <?php
                    $imgs = $conn->query("SELECT url FROM product_images WHERE product_id=" . intval($row['id']) . " LIMIT 1");
                    if ($img = $imgs->fetch_assoc()) echo "<img src='" . htmlspecialchars($img['url']) . "' width='70'>";
                    ?>
                  </td>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td><?= number_format($row['price'], 0, ',', '.') ?> VND</td>
                  <td><?= $row['stock'] ?></td>
                  <td><?= $row['created_at'] ?></td>
                  <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Sửa</a>
                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                      onclick="return confirm('Xóa sản phẩm này?')">Xóa</a>
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
  <script>
    let variantIndex = <?= $edit_mode ? count($product_variants) : 1 ?>;

    function addVariant() {
      const list = document.getElementById('variant-list');
      const html = `
    <div class="row mb-2">
      <div class="col"><input type="text" name="variants[${variantIndex}][name]" placeholder="Tên biến thể" class="form-control"></div>
      <div class="col"><input type="number" step="0.01" name="variants[${variantIndex}][price]" placeholder="Giá" class="form-control"></div>
      <div class="col"><input type="number" name="variants[${variantIndex}][stock]" placeholder="Số lượng" class="form-control"></div>
    </div>`;
      list.insertAdjacentHTML('beforeend', html);
      variantIndex++;
    }
  </script>
</body>

</html>