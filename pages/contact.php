<?php
include("../configs/db.php");
session_start();

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
  <title>Liên hệ - Shoe Store</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" />
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
  <link href="../assets/css/style.css" rel="stylesheet">

  <style>
    .map-responsive {
      overflow: hidden;
      padding-bottom: 56.25%;
      position: relative;
      height: 0;
    }

    .map-responsive iframe {
      left: 0;
      top: 0;
      height: 100%;
      width: 100%;
      position: absolute;
    }
  </style>
</head>

<body>
  <?php include("../layout/header.php"); ?>

  <div class="container" style="padding-top: 80px;">
    <h2 data-aos="fade-down">📞 Liên hệ với chúng tôi</h2>
    <p data-aos="fade-down" data-aos-delay="100">Nếu bạn có thắc mắc, hãy để lại thông tin hoặc ghé trực tiếp cửa
      hàng.</p>

    <div class="row my-4">
      <!-- Form liên hệ -->
      <div class="col-md-6" data-aos="fade-right">
        <div class="card shadow-sm p-4">
          <form id="contactForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="input-group mb-3">
              <label for="full_name" class="input-group-text"><i class="fa fa-user"></i></label>
              <input type="text" class="form-control" id="full_name" name="full_name"
                placeholder="Họ và tên" required>
            </div>

            <div class="input-group mb-3">
              <label for="email" class="input-group-text"><i class="fa fa-envelope"></i></label>
              <input type="email" class="form-control" id="email" name="email" placeholder="Email"
                required>
            </div>

            <div class="input-group mb-3">
              <label for="phone" class="input-group-text"><i class="fa fa-phone"></i></label>
              <input type="text" class="form-control" id="phone" name="phone"
                placeholder="Số điện thoại (tuỳ chọn)">
            </div>

            <div class="input-group mb-3">
              <label for="message" class="input-group-text"><i class="fa fa-comment"></i></label>
              <textarea class="form-control" id="message" name="message" rows="1"
                placeholder="Nội dung..." required></textarea>
            </div>

            <button id="btnSubmit" type="submit" class="btn btn-primary w-100">
              <span>
                <i class="fa fa-paper-plane"></i>
              </span>
              Gửi tin nhắn
            </button>
          </form>
        </div>
      </div>

      <!-- Google Map dạng Tab -->
      <div class="col-md-6" data-aos="fade-left">
        <ul class="nav nav-tabs" id="branchTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="hcm-tab" data-bs-toggle="tab" data-bs-target="#hcm"
              type="button" role="tab">
              📍 TP.HCM
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="bacninh-tab" data-bs-toggle="tab" data-bs-target="#bacninh"
              type="button" role="tab">
              📍 Bắc Ninh
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="danang-tab" data-bs-toggle="tab" data-bs-target="#danang"
              type="button" role="tab">
              📍 Đà Nẵng
            </button>
          </li>
        </ul>

        <div class="tab-content mt-3" id="branchTabsContent">
          <!-- HCM -->
          <div class="tab-pane fade show active" id="hcm" role="tabpanel" aria-labelledby="hcm-tab">
            <h5>📍 Chi nhánh TP.HCM</h5>
            <p>123 Đường ABC, Quận XYZ, TP.HCM</p>
            <div class="map-responsive">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d501714.3777334782!2d106.3555433547297!3d10.76192843195741!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752eefdb25d923%3A0x4bcf54ddca2b7214!2zSOG7kyBDaMOtIE1pbmgsIFZp4buHdCBOYW0!5e0!3m2!1svi!2s!4v1757660239633!5m2!1svi!2s"
                width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>

          <!-- Bắc Ninh -->
          <div class="tab-pane fade" id="bacninh" role="tabpanel" aria-labelledby="bacninh-tab">
            <h5>📍 Chi nhánh Bắc Ninh</h5>
            <p>456 Đường DEF, Quận 1, TP. Bắc Ninh</p>
            <div class="map-responsive">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d59527.278591227216!2d105.99969598343276!3d21.174080186859044!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31350c5b3464ae51%3A0x1a3035b9749102f9!2zVHAuIELhuq9jIE5pbmgsIELhuq9jIE5pbmgsIFZp4buHdCBOYW0!5e0!3m2!1svi!2s!4v1757659535214!5m2!1svi!2s"
                width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>

          <!-- Đà Nẵng -->
          <div class="tab-pane fade" id="danang" role="tabpanel" aria-labelledby="danang-tab">
            <h5>📍 Chi nhánh Đà Nẵng</h5>
            <p>789 Đường GHI, Quận Hải Châu, TP. Đà Nẵng</p>
            <div class="map-responsive">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d245374.1268620618!2d107.91381930847082!3d16.067008502048147!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x314219c792252a13%3A0x1df0cb4b86727e06!2zxJDDoCBO4bq1bmcsIFZp4buHdCBOYW0!5e0!3m2!1svi!2s!4v1757660188538!5m2!1svi!2s"
                width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toast" class="toast text-bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <i class="fa fa-bell me-2"></i>
        <strong class="me-auto">Thông báo</strong>
        <small>Just now</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body"></div>
    </div>
  </div>

  <?php include("../layout/footer.php"); ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/script.js"></script>
  <script>
    AOS.init();

    const btnLoader = makeButtonLoader($("#btnSubmit"));

    $(document).ready(function() {
      $("#contactForm").on("submit", function(e) {
        e.preventDefault();

        $.ajax({
          url: "../api/contact_api.php", // endpoint API tách riêng
          type: "POST",
          data: $(this).serialize(),
          dataType: "json",
          beforeSend: () => {
            btnLoader.showLoading();
          },
          complete: () => {
            btnLoader.showDefault();
          },
          success: function(data) {
            showMessage(data);
            if (data.status == "success") {
              $("#contactForm")[0].reset();
            }
          },
          error: function(xhr, status, error) {
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