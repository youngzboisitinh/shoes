<a class="sidebar-link <?= (in_array($current_page, ['statistics.php'])) ? 'active' : '' ?>" href="statistics.php">
    <i class="fa fa-chart-line"></i>
    <span>Thống kê</span>
</a>
<a class="sidebar-link <?= (in_array($current_page, ['manage_users.php'])) ? 'active' : '' ?>" href="manage_users.php">
    <i class="fa fa-users"></i>
    <span>Người dùng</span>
</a>
<a class="sidebar-link <?= (in_array($current_page, ['approve_deposit.php'])) ? 'active' : '' ?>"
    href="approve_deposit.php">
    <i class="fa fa-users"></i>
    <span>Duyệt nạp</span>
    <?php if ($naptien_result['status_count'] > 0): ?>
    <span class="badge rounded-pill bg-danger">
        <?= $naptien_result['status_count'] ?>
    </span>
    <?php endif ?>
</a>
<a class="sidebar-link <?= (in_array($current_page, ['manage_products.php'])) ? 'active' : '' ?>"
    href="manage_products.php">
    <i class="fa fa-box"></i>
    <span>Sản phẩm</span>
</a>
<a class="sidebar-link <?= (in_array($current_page, ['manage_orders.php'])) ? 'active' : '' ?>"
    href="manage_orders.php">
    <i class="fa fa-cart-shopping"></i>
    <span>Đơn đặt hàng</span>
    <?php if ($orders_result['pending_status_count'] > 0): ?>
    <span class="badge rounded-pill bg-danger">
        <?= $orders_result['pending_status_count'] ?>
    </span>
    <?php endif ?>
</a>
<a class="sidebar-link <?= (in_array($current_page, ['manage_coupons.php'])) ? 'active' : '' ?>"
    href="manage_coupons.php">
    <i class="fa fa-ticket"></i>
    <span>Phiếu giảm giá</span>
</a>
<a class="sidebar-link <?= (in_array($current_page, ['manage_categories.php'])) ? 'active' : '' ?>"
    href="manage_categories.php">
    <i class="fa fa-list"></i>
    <span>Danh mục</span>
</a>
<a class="sidebar-link <?= (in_array($current_page, ['contact_forms.php'])) ? 'active' : '' ?>"
    href="contact_forms.php">
    <i class="fa-solid fa-message"></i>
    <span>Tin nhắn</span>
    <?php if ($contact_result['contact_count'] > 0): ?>
    <span class="badge rounded-pill bg-danger">
        <?= $contact_result['contact_count'] ?>
    </span>
    <?php endif ?>
</a>
<a class="sidebar-link <?= (in_array($current_page, ['manage_carousel.php'])) ? 'active' : '' ?>"
    href="manage_carousel.php">
    <i class="fa-solid fa-panorama"></i>
    <span>Slide Banner</span>
</a>
<a class="sidebar-link" href="../index.php">
    <i class="fa fa-home"></i>
    <span class="text-primary">Trang chủ</span>
</a>
<a class="sidebar-link" href="../account/logout.php">
    <i class="fa fa-door-closed"></i>
    <span class="text-danger">Đăng xuất</span>
</a>