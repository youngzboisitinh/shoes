<?php
$current_page = basename($_SERVER['PHP_SELF']);

$stmt = $conn->prepare("SELECT COUNT(status) AS pending_status_count FROM orders WHERE status = 'pending'");
$stmt->execute();
$orders_result = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT COUNT(status) AS status_count FROM deposit_requests WHERE status = 'pending'");
$stmt->execute();
$naptien_result = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT COUNT(*) AS contact_count FROM contact_forms WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$contact_result = $stmt->get_result()->fetch_assoc();
?>
<!-- Nút mở sidebar chỉ hiện trên mobile -->
<button class="btn btn-primary d-md-none m-2" data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
    <i class="fa fa-bars"></i> Menu
</button>

<!-- Sidebar cho desktop -->
<nav class="col-md-2 d-none d-md-block bg-dark sidebar">
    <div class="p-3 text-white">
        <h4><i class="fa-solid fa-gauge"></i> Dashboard</h4>
        <p>Xin chào <b><?= $_SESSION['full_name'] ?></b></p>
        <hr>
        <?php include("../includes/sidebar_item_admin.php") ?>
    </div>
</nav>

<!-- Sidebar mobile dạng Offcanvas -->
<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarMobile">
    <div class="offcanvas-header">
        <h4><i class="fa-solid fa-gauge"></i> Dashboard</h4>
        <p>Xin chào <b><?= $_SESSION['full_name'] ?></b></p>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        <hr>
    </div>
    <div class="offcanvas-body">
        <div class="d-md-block bg-dark sidebar">
            <?php include("../includes/sidebar_item_admin.php") ?>
        </div>
    </div>
</div>