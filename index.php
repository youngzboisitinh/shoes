<?php
session_start();
include("configs/db.php");

$sql = "SELECT * FROM products 
        WHERE category_id
        ORDER BY created_at DESC
        LIMIT 12";
$result = $conn->query($sql);

// Lấy tất cả slide từ bảng carousel_home
$slides = $conn->query("SELECT * FROM carousel_home ORDER BY id DESC");

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
    <title>Shoe Store</title>
    <link rel="icon" type="image/x-icon" href="./favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .typing {
            border-right: 2px solid black;
            white-space: nowrap;
            overflow: hidden;
            vertical-align: bottom;
        }

        .carousel-item img {
            display: block;
            width: 100%;
            max-height: 450px;
            object-fit: cover;
        }
    </style>
</head>

<body>

    <?php include("layout/header.php") ?>
    <div class="container" style="padding-top: 80px;">
        <!-- Banner Carousel -->
        <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">

            <!-- Indicators -->
            <div class="carousel-indicators">
                <?php $i = 0;
                foreach ($slides as $s): ?>
                    <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="<?= $i ?>"
                        class="<?= $i == 0 ? 'active' : '' ?>" aria-current="<?= $i == 0 ? 'true' : 'false' ?>"></button>
                <?php $i++;
                endforeach; ?>
            </div>

            <!-- Slides -->
            <div class="carousel-inner">
                <?php
                $i = 0;
                $slides->data_seek(0); // reset pointer nếu đã foreach ở trên
                while ($s = $slides->fetch_assoc()): ?>
                    <div class="carousel-item <?= $i == 0 ? 'active' : '' ?>" data-bs-interval="4000">
                        <img src="<?= $s['image'] ?>" class="d-block w-100" style="max-height:70vh; object-fit:cover;">
                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3">
                            <h1><?= htmlspecialchars($s['title']) ?></h1>
                            <p><?= htmlspecialchars($s['text']) ?></p>
                        </div>
                    </div>
                <?php $i++;
                endwhile; ?>
            </div>

            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>

        <!-- Sản phẩm HOT -->
        <div class="text-center mt-5 mb-2">
            <h2>
                <span> SẢN PHẨM </span><span id="dynamic" class="text-danger typing"></span>
            </h2>
            <div class="decor-line">
                <i class="fas fa-diamond mx-1"></i>
                <i class="fas fa-diamond mx-0"></i>
                <i class="fas fa-diamond mx-1"></i>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <a href="./pages/products.php" class="btn btn-outline-primary">
                Xem tất cả
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="row g-2" data-masonry='{"itemSelector": ".grid-item", "percentPosition": true }'>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="grid-item col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <a href="./pages/product_detail.php?id=<?= $row['id'] ?>">
                            <img src="<?= htmlspecialchars($row['image']) ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($row['name']) ?>" style="height:200px; object-fit:cover;"
                                onerror="this.src='./uploads/default-shoe.jpg';">
                        </a>

                        <div class=" card-body d-flex flex-column">
                            <a href="./pages/product_detail.php?id=<?= $row['id'] ?>"
                                class="card-title text-link"><?= htmlspecialchars($row['name']) ?></a>
                            <?php if (isset($row['description'])): ?>
                                <p class="card-text product-description">
                                    <?= htmlspecialchars($row['description']) ?>
                                </p>
                            <?php endif; ?>

                            <p class="card-text text-danger fw-bold">
                                <?= number_format($row['price'], 0, ',', '.') ?> VND
                            </p>
                            <div class="mt-auto d-flex justify-content-between">
                                <button data-id="<?= $row['id'] ?>" class="btn btn-outline-primary buy-now">
                                    <span><i class="fa-solid fa-bag-shopping"></i></span>
                                    Mua ngay
                                </button>
                                <button data-id="<?= $row['id'] ?>" class="btn btn-outline-success add-to-cart">
                                    <span><i class="fas fa-cart-plus"></i></span>
                                    Thêm giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php include("./layout/footer.php") ?>
    <script src="https://unpkg.com/masonry-layout@4.2.2/dist/masonry.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/script.js"></script>
    <?php if (isset($_SESSION['data_msg'])): ?>
        <script>
            let data = <?= $_SESSION['data_msg'] ?>;
            showMessage(data);
        </script>
        <?php unset($_SESSION['data_msg']); ?>
    <?php endif; ?>
    <script>
        $(document).ready(function() {
            let titles = ["DEAL NGON", "BÁN CHẠY", "MỚI VỀ", "GIÁ SỐC"];
            let index = 0;
            let speed = 100; // tốc độ gõ
            let eraseSpeed = 50; // tốc độ xóa
            let delay = 1000; // thời gian dừng giữa các từ

            function typeText(text, i) {
                if (i < text.length) {
                    $('#dynamic').html(text.substring(0, i + 1));
                    setTimeout(() => typeText(text, i + 1), speed);
                } else {
                    setTimeout(() => eraseText(text, text.length - 1), delay);
                }
            }

            function eraseText(text, i) {
                if (i >= 0) {
                    $('#dynamic').html(text.substring(0, i));
                    setTimeout(() => eraseText(text, i - 1), eraseSpeed);
                } else {
                    index = (index + 1) % titles.length;
                    typeText(titles[index], 0);
                }
            }

            typeText(titles[index], 0);

            const csrfToken = '<?= $csrf_token ?>';

            $('.add-to-cart').on('click', function() {
                const data = {
                    action: 'add',
                    id: $(this).data('id'),
                    csrf_token: csrfToken
                };
                const btnLoader = makeButtonLoader($(this));
                $.ajax({
                    url: "api/cart_api.php",
                    type: "POST",
                    data: data,
                    dataType: "json",
                    beforeSend: () => {
                        btnLoader.showLoading();
                    },
                    complete: () => {
                        btnLoader.showDefault();
                    },
                    success: (data) => {
                        showMessage(data);
                        if (data.status === "success") {
                            $('#cartCount').removeClass('d-none');
                            $('#cartCount').text(data.cart_count);
                        }
                    },
                    error: (xhr, status, error) => {
                        Swal.fire({
                            icon: "error",
                            title: "Lỗi server",
                            text: "Không thể gửi yêu cầu. Vui lòng thử lại!",
                        });
                        console.error(error);
                    }
                });
            });

            const btnLoaderBuyNow = makeButtonLoader($("#buyNowBtn"));

            $(".buy-now").on("click", function() {
                const data = {
                    action: 'buy_now',
                    id: $(this).data('id'),
                    csrf_token: csrfToken
                }
                const btnLoader = makeButtonLoader($(this));
                $.ajax({
                    url: "api/product_api.php",
                    type: "POST",
                    data: data,
                    dataType: "json",
                    beforeSend: () => {
                        btnLoader.showLoading();
                    },
                    complete: () => {
                        btnLoader.showDefault();
                    },
                    success: (data) => {
                        showMessage(data);
                        if (data.status === "success") {
                            location.href = "pages/buy_now.php?id=" + $(this).data('id');
                        }
                    },
                    error: (xhr, status, error) => {
                        Swal.fire({
                            icon: "error",
                            title: "Lỗi server",
                            text: "Không thể gửi yêu cầu. Vui lòng thử lại!",
                        });
                        console.error(error);
                    }
                });
            });
        });
    </script>
</body>

</html>