<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include("../configs/db.php");

// Lấy type từ URL
$type = $_GET['type'] ?? '';
$category_id = $_GET['category_id'] ?? 0;

$categoryMap = [
    'balo'      => ['name' => 'Balo'],
    'charm'     => ['name' => 'Charm'],
    'daygiay'   => ['name' => 'Dây giày'],
    'lotde'     => ['name' => 'Lót đế'],
    'gaubong'   => ['name' => 'Gấu bông'],
    'xitkhumui' => ['name' => 'Xịt khử mùi'],
    'tat' => ['name' => 'Tất']
];

if (!array_key_exists($type, $categoryMap)) {
    $keys = array_keys($categoryMap);
    $type = $keys[array_rand($keys)];
}

$catName = '';

$where = "WHERE 1";
if ($category_id) {
    $where .= " AND p.category_id = " . intval($category_id);
} else {
    $catName = $categoryMap[$type]['name'];
    if ($catName) {
        $where .= " AND c.name LIKE '%" . $conn->real_escape_string($catName) . "%'";
    }
}

// Nhận tham số trang
$limit = 8; // số sản phẩm / trang
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(*) as total 
              FROM products p 
              JOIN categories c ON p.category_id = c.id 
              $where";
$count_result = $conn->query($count_sql);
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Truy vấn sản phẩm có phân trang
$sql = "SELECT p.*,
               (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id) as variant_count
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        $where
        ORDER BY p.created_at DESC 
        LIMIT $limit OFFSET $offset";

$products = $conn->query($sql);
// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Phụ kiện - Shoe Store</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">

    <?php include("../layout/header.php"); ?>

    <div class="container" style="padding-top: 80px;">
        <h2 class="mb-4 text-center">
            🛍️ Phụ kiện: <span class="text-primary"><?= htmlspecialchars($catName) ?></span>
        </h2>

        <!-- Danh sách sản phẩm -->
        <?php include("../includes/product_item.php"); ?>

        <!-- Phân trang -->
        <?php include("../includes/pagination.php"); ?>
    </div>

    <?php include("../layout/footer.php"); ?>
    <script src="https://unpkg.com/masonry-layout@4.2.2/dist/masonry.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <?php include("../includes/product_script.php"); ?>
</body>

</html>