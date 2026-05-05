<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Giới thiệu - Shoe Store</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
    .background-section {
        background: url('../assets/images/shoe_background.jpg') center/cover;
        height: 60vh;
        margin-top: 60px;
    }

    .masked-text {
        font-size: 4rem;
        font-weight: bold;
        color: transparent;
        background-image: url('../assets/images/photo-1732535725600-f805d8b33c9c.avif');
        background-size: 200%;
        /* Enlarged for smooth animation */
        background-position: 0 50%;
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: animate-background 5s infinite alternate linear;
    }

    @keyframes animate-background {
        0% {
            background-position: 0 50%;
        }

        100% {
            background-position: 100% 50%;
        }
    }

    .about-subtitle {
        background-clip: text;
        background-image: linear-gradient(to right, #09f1b8, #ff00d2, #00a2ff, #fed90f);
        color: #ccc;
        font-weight: 700;
        letter-spacing: calc(1em / 8);
        padding: calc(1em / 16 / 2);
        -webkit-text-stroke-color: transparent;
        -webkit-text-stroke-width: calc(1em / 16);
    }

    .card a i {
        transition: all 0.3s ease;
    }

    .card a:hover i {
        font-size: 2rem;
        transform: rotate(15deg);
    }
    </style>
</head>

<body>
    <?php include("../layout/header.php"); ?>

    <div class="container" style="padding-top: 15px;">
        <!-- Hero Section -->
        <section
            class="background-section hero text-white text-center d-flex align-items-center justify-content-center">
            <div data-aos="fade-up">
                <div class="masking-container">
                    <h1 class="masked-text">Chào mừng đến với Shoe Store</h1>
                </div>
                <p class="lead about-subtitle">Thời trang - Phong cách - Đẳng cấp</p>
            </div>
        </section>

        <!-- About -->
        <section class="my-5 text-center" data-aos="fade-up">
            <h2 class="mb-3"> <i class="fas fa-shoe-prints text-danger"></i> Giới thiệu về chúng tôi</h2>
            <p class="text-muted">Shoe Store ra đời với sứ mệnh mang đến những đôi giày chất lượng, giúp khách hàng
                tự tin thể hiện phong cách cá nhân. Chúng tôi luôn đặt <b>chất lượng</b> và <b>khách hàng</b> làm
                trung tâm.</p>
        </section>

        <!-- Vì sao chọn -->
        <section class="mb-5">
            <h2 class="text-center mb-4" data-aos="zoom-in"><i class="fa-solid fa-star text-warning"></i> Vì sao chọn
                Shoe Store?
            </h2>
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="card shadow d-flex align-items-center justify-content-center gap-2 p-3">
                        <i class="fas fa-circle-check fs-1 text-primary"></i>
                        <h5>Chính hãng 100%</h5>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="card shadow d-flex align-items-center justify-content-center gap-2 p-3">
                        <i class="fas fa-truck-fast fs-1 text-success"></i>
                        <h5>Ship nhanh toàn quốc</h5>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="card shadow d-flex align-items-center justify-content-center gap-2 p-3">
                        <i class="fas fa-coins fs-1 text-warning"></i>
                        <h5>Giá hợp lý</h5>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="card shadow d-flex align-items-center justify-content-center gap-2 p-3">
                        <i class="fas fa-headset fs-1 text-danger"></i>
                        <h5>Hỗ trợ 24/7</h5>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team -->
        <section class="mb-5">
            <h2 class="text-center mb-4" data-aos="zoom-in"><i class="fa-solid fa-users text-info"></i> Đội ngũ của
                chúng tôi</h2>
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3 text-center" data-aos="flip-left">
                    <div class="card shadow p-3">
                        <img src="../assets/images/thien.jpg" class="rounded-circle mx-auto" width="140" height="140">
                        <h6 class="mt-2">Nguyễn Lâm Đức Thiện</h6>
                        <small>CEO & Founder</small>
                        <div class="d-flex justify-content-center mt-2">
                            <a href="https://www.facebook.com/ucthien.77958" class="text-primary fs-4"><i
                                    class="fa-brands fa-facebook"></i></a>
                            <a href="#" class="text-danger fs-4"><i class="fa-brands fa-square-instagram"></i></a>
                            <a href="#" class="text-info fs-4"><i class="fa-brands fa-telegram"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 text-center" data-aos="flip-left" data-aos-delay="100">
                    <div class="card shadow p-3">
                        <img src="../assets/images/phuong.jpg" class="rounded-circle mx-auto" width="140" height="140">
                        <h6 class="mt-2">Nguyễn Thị Thanh Phương<</P></h6>
                        <small>Quản lý</small>
                        <div class="d-flex justify-content-center mt-2">
                            <a href="#" class="text-primary fs-4"><i class="fa-brands fa-facebook"></i></a>
                            <a href="#" class="text-danger fs-4"><i class="fa-brands fa-square-instagram"></i></a>
                            <a href="#" class="text-info fs-4"><i class="fa-brands fa-telegram"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 text-center" data-aos="flip-left" data-aos-delay="200">
                    <div class="card shadow p-3">
                        <img src="../assets/images/y.jpg" class="rounded-circle mx-auto" width="140" height="140">
                        <h6 class="mt-2">Nguyễn Ngọc Như Ý</h6>
                        <small> PM & Developer </small>
                        <div class="d-flex justify-content-center mt-2">
                            <a href="https://www.facebook.com/y.ngngnhu" class="text-primary fs-4"><i
    
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 text-center" data-aos="flip-left" data-aos-delay="300">
                    <div class="card shadow p-3">
                        <img src="../assets/images/manh.jpg" class="rounded-circle mx-auto" width="140" height="140">
                        <h6 class="mt-2">Long Huỳnh</h6>
                        <small>CSKH</small>
                        <div class="d-flex justify-content-center mt-2">
                            <a href="https://web.facebook.com/vi.van.manh.857440" class="text-primary fs-4"><i
                                    class="fa-brands fa-facebook"></i></a>
                            <a href="#" class="text-danger fs-4"><i class="fa-brands fa-square-instagram"></i></a>
                            <a href="#" class="text-info fs-4"><i class="fa-brands fa-telegram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Counter -->
        <section class="bg-light py-5 text-center rounded mb-5" data-aos="fade-up">
            <div class="row">
                <div class="col-sm-6 col-lg-3">
                    <h2 class="counter text-primary" data-target="10000">0</h2>
                    <p>Khách hàng</p>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <h2 class="counter text-success" data-target="5000">0</h2>
                    <p>Đơn hàng</p>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <h2 class="counter text-warning" data-target="50">0</h2>
                    <p>Đối tác</p>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <h2 class="counter text-danger" data-target="5">0</h2>
                    <p>Năm kinh nghiệm</p>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="mb-5">
            <h2 class="text-center mb-4" data-aos="fade-up"><i class="fa-solid fa-comment"></i>Khách hàng nói gì?
            </h2>
            <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner text-center">
                    <div class="carousel-item active">
                        <blockquote class="blockquote">
                            <p>"Giày đẹp, chất lượng và giao hàng nhanh!"</p>
                            <footer class="blockquote-footer">Anh Hùng</footer>
                        </blockquote>
                    </div>
                    <div class="carousel-item">
                        <blockquote class="blockquote">
                            <p>"Shop tư vấn nhiệt tình, sản phẩm y hình."</p>
                            <footer class="blockquote-footer">Chị Lan</footer>
                        </blockquote>
                    </div>
                    <div class="carousel-item">
                        <blockquote class="blockquote">
                            <p>"Giá cả hợp lý, sẽ ủng hộ dài dài."</p>
                            <footer class="blockquote-footer">Bạn Minh</footer>
                        </blockquote>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <div class="text-center" data-aos="zoom-in">
            <a href="contact.php" class="btn btn-lg btn-primary"><i class="fa-solid fa-envelope"></i>Liên hệ
                ngay</a>
        </div>
    </div>
    <?php include("../layout/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
    function formatVND(n) {
        return n.toLocaleString("vi-VN", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    AOS.init();

    // Counter animation
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        counter.innerText = '0';
        const intervalDelay = counter.getAttribute('data-interval-delay') ?? 20;
        const suffix = counter.getAttribute('data-suffix') ?? '';
        const updateCounter = () => {
            const target = +counter.getAttribute('data-target');
            const c = +counter.innerText;
            const increment = target / 200;
            if (c < target) {
                counter.innerText = Math.ceil(c + increment);
                setTimeout(updateCounter, intervalDelay);
            } else {
                counter.innerText = formatVND(target) + suffix;
            }
        };
        updateCounter();
    });
    </script>
</body>

</html>