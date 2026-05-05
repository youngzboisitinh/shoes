<?php
session_start();
include("../configs/db.php");

// Nếu đã đăng nhập thì quay về
if (isset($_SESSION['username'])) {
  header("Location: ../index.php");
  exit;
}

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
  <title>Đăng ký - Shoe Store</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="../assets/css/style.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(135deg, #00c6ff, #0072ff);
      overflow: hidden;
    }

    .register-card {
      background: rgba(255, 255, 255, 0.92);
      border-radius: 20px;
      backdrop-filter: blur(12px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
      transform: translateY(40px);
      opacity: 0;
      animation: fadeUp 0.8s ease forwards;
    }

    @keyframes fadeUp {
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .register-card h3 {
      font-weight: 700;
      background: linear-gradient(90deg, #36d1dc, #5b86e5);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .form-control:focus {
      border-color: #0072ff;
      box-shadow: 0 0 8px rgba(0, 114, 255, 0.6);
    }

    .input-group:focus-within label {
      color: #0072ff;
    }

    .btn-gradient {
      border: none;
      background: linear-gradient(to right, #36d1dc, #5b86e5);
      background-size: 150% auto;
      transition: background-position 0.4s ease;
    }

    .btn-gradient:hover {
      background-position: 100% 0;
    }

    .toggle-password {
      cursor: pointer;
      color: #888;
    }

    .toggle-active {
      color: #0d6efd !important;
    }
  </style>
</head>

<body>
  <?php include("../layout/header.php") ?>

  <div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
    <div class="card register-card shadow p-4" style="width:420px;">
      <h3 class="text-center mb-4"><i class="fa-solid fa-user-lock"></i> Đăng ký tài khoản</h3>

      <form id="registerForm" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <div class="input-group mb-3">
          <label for="email" class="input-group-text"><i class="fas fa-envelope"></i></label>
          <input type="email" class="form-control" id="email" name="email" placeholder="Email đăng ký"
            required>
        </div>

        <div class="input-group mb-3">
          <label for="full_name" class="input-group-text"><i class="fas fa-a"></i></label>
          <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Họ và Tên"
            required>
        </div>

        <div class="input-group mb-3">
          <label for="day_of_birth" class="input-group-text"><i class="fas fa-cake-candles"></i></label>
          <input type="date" class="form-control" id="day_of_birth" name="day_of_birth">
        </div>

        <div class="input-group mb-3">
          <label for="password" class="input-group-text"><i class="fas fa-lock"></i></label>
          <input type="password" id="password" name="password" placeholder="Mật khẩu" class="form-control"
            autocomplete="off" required>
          <span class="input-group-text bg-white toggle-password">
            <i class="fa fa-eye"></i>
          </span>
        </div>

        <div class="input-group mb-3">
          <label for="confirm" class="input-group-text"><i class="fas fa-lock"></i></label>
          <input type="password" id="confirm" name="confirm" placeholder="Xác nhận mật khẩu"
            class="form-control" autocomplete="off" required>
          <span class="input-group-text bg-white toggle-password">
            <i class="fa fa-eye"></i>
          </span>
        </div>

        <button id="btnSubmit" type="submit" class="btn btn-gradient w-100 py-2 text-white fw-bold">
          <span>
            <i class="fa-solid fa-globe"></i>
          </span>
          Đăng ký
        </button>
        <p class="text-center mt-3 mb-0">Đã có tài khoản? <a href="login.php"
            class="fw-semibold text-decoration-none">Đăng nhập</a></p>
      </form>
    </div>
  </div>

  <?php include("../layout/footer.php") ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/script.js"></script>
  <script>
    $(document).ready(function() {

      const togglePassword = $(".toggle-password");
      $(".toggle-password").on("click", function() {
        const passwordInput = togglePassword.prev();
        const type = passwordInput.attr("type") === "password" ? "text" : "password";
        passwordInput.attr("type", type);

        togglePassword.find('i').toggleClass("fa-eye-slash");
        togglePassword.find('i').toggleClass("toggle-active");
      });

      const btnLoader = makeButtonLoader($("#btnSubmit"));

      $("#registerForm").on("submit", function(e) {
        e.preventDefault();

        $.ajax({
          url: "../api/register_api.php",
          type: "POST",
          data: $(this).serialize(),
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
              $(this)[0].reset();
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