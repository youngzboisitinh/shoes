<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include(__DIR__ . "/../configs/db.php");

// Determine base URL dynamically based on current path
$script_path = $_SERVER['PHP_SELF'];
if (strpos($script_path, '/pages/') !== false || strpos($script_path, '/admin/') !== false || strpos($script_path, '/account/') !== false) {
    // Running from pages/, admin/, or account/ subdirectory
    $base_url = '';
} else if (strpos($script_path, '/shoes/') !== false) {
    // Traditional path /shoes/
    $base_url = '/shoes';
} else {
    // Default to root
    $base_url = '';
}

// Function to fix URLs for Docker/Render compatibility
function fix_image_url($url) {
    global $base_url;
    if (empty($url)) return $url;
    
    // If URL contains /shoes/ path, rewrite it
    if (strpos($url, '/shoes/') !== false) {
        $url = str_replace('/shoes/', '/', $url);
    }
    
    // If URL is absolute HTTP, extract just the path
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        $parsed = parse_url($url);
        $url = $parsed['path'] ?? $url;
    }
    
    return $url;
}

// Hiển thị tên user
$display_name = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== ''
    ? $_SESSION['full_name'] . ' (' . number_format($_SESSION['balance'], 0, ',', '.') . 'đ)'
    : 'Tài khoản';
// Đếm giỏ hàng
// Tsst
$cart_count = 0;
$user_id = $_SESSION['user_id'] ?? null;
if (!empty($user_id)) {
    $stmt = $conn->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($total_quantity);
    $stmt->fetch();
    $stmt->close();
    $cart_count = $total_quantity ?? 0;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm" style="z-index:3000;">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold" href="<?= $base_url ?>/index.php">
            <i class="fas fa-shoe-prints"></i> Shoe Store
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <!-- Menu -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link <?= (in_array($current_page, ['products.php'])) ? 'active' : '' ?>"
                        href="<?= $base_url ?>/pages/products.php">
                        <span>Sản phẩm</span>
                    </a>
                </li>

                <!-- Nam -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (in_array($current_page, ['men.php'])) ? 'active' : '' ?>"
                        href="#" role="button" data-bs-toggle="dropdown">
                        <span>Nam</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/men.php?type=giaytay">Giầy tây</a>
                        </li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/men.php?type=giaythethao">Giầy thể
                                thao</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/men.php?type=dep">Dép</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/phukien.php?type=balo">Balo</a>
                        </li>
                    </ul>
                </li>

                <!-- Nữ -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (in_array($current_page, ['women.php'])) ? 'active' : '' ?>"
                        href="#" role="button" data-bs-toggle="dropdown">
                        <span>Nữ</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/women.php?type=giaycaogot">Giày
                                cao gót</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/women.php?type=giaythethao">Giày
                                thể thao</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/women.php?type=giaychaybodibo">Giày
                                chạy bộ % đi bộ</a>
                        </li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/women.php?type=giaybupbe">Giày búp
                                bê</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/phukien.php?type=balo">Balo</a>
                        </li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/women.php?type=tuixachvi">Túi xách
                                - Ví</a></li>
                    </ul>
                </li>

                <!-- Phụ kiện -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (in_array($current_page, ['phukien.php'])) ? 'active' : '' ?>"
                        href="#" role="button" data-bs-toggle="dropdown">
                        <span>Phụ kiện</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/phukien.php?type=balo">Balo</a>
                        </li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/phukien.php?type=charm">Charm</a>
                        </li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/phukien.php?type=lotde">Lót đế</a>
                        </li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/phukien.php?type=daygiay">Dây
                                giày</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/phukien.php?type=gaubong">Gấu
                                bông</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/phukien.php?type=xitkhumui">Xịt
                                khử mùi</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/pages/phukien.php?type=tat">Tất
                            </a></li>
                    </ul>
                </li>

                <!-- Giới thiệu + Liên hệ -->
                <li class="nav-item">
                    <a class="nav-link <?= (in_array($current_page, ['about.php'])) ? 'active' : '' ?>"
                        href="<?= $base_url ?>/pages/about.php">
                        <span>Giới thiệu</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (in_array($current_page, ['contact.php'])) ? 'active' : '' ?>"
                        href="<?= $base_url ?>/pages/contact.php">
                        <span>Liên hệ</span>
                    </a>
                </li>
            </ul>

            <!-- Bộ lọc sản phẩm -->
            <form id="filterForm" class="d-flex align-items-center ms-lg-3" method="GET"
                action="<?= $base_url ?>/pages/products.php">
                <div class="dropdown me-3">
                    <button class="btn btn-sm btn-outline-warning dropdown-toggle" type="button" id="filterDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel"></i> Lọc
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-3 shadow" style="min-width: 250px;">

                        <div class="mb-2">
                            <label class="form-label small mb-1">Danh mục</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="0">Tất cả</option>
                                <?php
                                $cate = $conn->query("SELECT * FROM categories");
                                while ($c = $cate->fetch_assoc()):
                                ?>
                                    <option value="<?= $c['id'] ?>"
                                        <?= (isset($_GET['category_id']) && $_GET['category_id'] == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1">Sắp xếp</label>
                            <select name="sort" class="form-select form-select-sm">
                                <option value="">Mặc định</option>
                                <option value="price_asc"
                                    <?= (isset($_GET['sort']) && $_GET['sort'] == "price_asc") ? "selected" : "" ?>>Giá
                                    ↑</option>
                                <option value="price_desc"
                                    <?= (isset($_GET['sort']) && $_GET['sort'] == "price_desc") ? "selected" : "" ?>>Giá
                                    ↓</option>
                                <option value="newest"
                                    <?= (isset($_GET['sort']) && $_GET['sort'] == "newest") ? "selected" : "" ?>>Mới
                                    nhất</option>
                                <option value="oldest"
                                    <?= (isset($_GET['sort']) && $_GET['sort'] == "oldest") ? "selected" : "" ?>>Cũ nhất
                                </option>
                            </select>
                        </div>
                        <div class="mb-2 d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-warning w-100">
                                <i class="fas fa-filter"></i> Áp dụng
                            </button>
                            <button type="button" id="resetFormBtn" class="btn btn-sm btn-secondary w-100">
                                <i class="fas fa-rotate-left"></i> Đặt lại
                            </button>
                            <script>
                                document.getElementById('resetFormBtn').addEventListener('click', () => {
                                    window.history.replaceState({}, document.title, window.location.pathname);
                                    document.getElementById('filterForm').reset();
                                    document.querySelector("[name='category_id']").selectedIndex = 0;
                                    document.querySelector("[name='sort']").selectedIndex = 0;
                                });
                            </script>
                        </div>
                    </div>
                </div>

                <!-- Tìm kiếm -->
                <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Bạn cần tìm gì..."
                    aria-label="Search" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">

                <button class="btn btn-sm btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
            </form>

            <!-- Giỏ hàng -->
            <div class="d-flex align-items-center px-2 py-2">
                <a href="<?= $base_url ?>/pages/cart.php" class="text-light position-relative me-3 fs-5"
                    title="Giỏ hàng">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cartCount"
                        class="<?php if ($cart_count <= 0) echo 'd-none'; ?> position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $cart_count ?>
                    </span>
                </a>
            </div>

            <!-- Dropdown tài khoản -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="accountDropdown" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($display_name) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li>
                                <a class="dropdown-item" href="<?= $base_url ?>/account/profile.php">
                                    Thông tin cá nhân
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $base_url ?>/pages/orders.php">
                                    Đơn hàng
                                </a>
                            </li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item active" href="<?= $base_url ?>/admin/statistics.php">
                                        Bảng điều khiển
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="<?= $base_url ?>/account/logout.php">
                                    Đăng xuất
                                </a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a class="dropdown-item" href="<?= $base_url ?>/account/login.php">
                                    Đăng nhập
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $base_url ?>/account/register.php">Đăng ký

                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>