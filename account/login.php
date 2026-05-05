<?php
session_start();
include("../configs/db.php");

// Nếu đã đăng nhập thì chuyển về trang chủ
if (isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Nếu có refresh_token trong cookie
if (isset($_COOKIE['refresh_token'])) {
  $token = $_COOKIE['refresh_token'];
  $stmt = $conn->prepare("SELECT * FROM users WHERE refresh_token=? LIMIT 1");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if ($user) {
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['email']       = $user['email'];
    $_SESSION['balance']     = $user['balance'];
    $_SESSION['role']        = $user['role'];
    $_SESSION['full_name']   = $user['full_name'];
    $_SESSION['day_of_birth'] = $user['day_of_birth'];
    $_SESSION['phone']       = $user['phone'];
    $_SESSION['address']     = $user['address'];
    $_SESSION['created_at']  = $user['created_at'];

    setcookie("refresh_token", $token, time() + 60 * 60 * 24 * 7, "/", "", false, true);
    $stmt = $conn->prepare("UPDATE users SET refresh_token=? WHERE id=?");
    $stmt->bind_param("si", $token, $user['id']);
    $stmt->execute();
    header("Location: ../index.php");
    exit;
  } else {
    setcookie("refresh_token", "", -1, "/", "", false, true);
  }
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>Đăng nhập - Shoe Store</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea, #764ba2);
      overflow: hidden;
    }

    .login-card {
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

    .login-card h3 {
      font-weight: 700;
      background: linear-gradient(90deg, #4facfe, #00f2fe);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 8px rgba(102, 126, 234, 0.6);
    }

    .input-group:focus-within label {
      color: #667eea;
    }

    .toggle-password {
      cursor: pointer;
      color: #888;
    }

    .btn-gradient {
      border: none;
      background: linear-gradient(to right, #4facfe, #764ba2);
      background-size: 150% auto;
      transition: background-position 0.4s ease;
    }

    .btn-gradient:hover {
      background-position: 100% 0;
    }

    .toggle-active {
      color: #0d6efd !important;
    }
  </style>
</head>

<body>
  <?php include("../layout/header.php") ?>

  <div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
    <div class="card login-card shadow p-4" style="width:400px;">
      <h3 class="text-center mb-4"><i class="fas fa-key"></i> Đăng nhập</h3>

      <form id="loginForm" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <div class="input-group mb-3">
          <label for="email" class="input-group-text"><i class="fa fa-envelope"></i></label>
          <input type="email" class="form-control" id="email" name="email" placeholder="Email đăng nhập"
            required>
        </div>

        <div class="input-group mb-3">
          <label for="password" class="input-group-text"><i class="fa fa-lock"></i></label>
          <input type="password" id="password" name="password" placeholder="Mật khẩu" class="form-control"
            autocomplete="off" required>
          <span class="input-group-text bg-white toggle-password">
            <i class="fa fa-eye"></i>
          </span>
        </div>

        <button id="btnSubmit" type="submit" class="btn btn-gradient w-100 py-2 text-white fw-bold">
          <span>
            <i class="fa-solid fa-right-to-bracket"></i>
          </span>
          Đăng nhập
        </button>
        <p class="text-center mt-3 mb-0">
          Chưa có tài khoản? <a href="register.php" class="fw-semibold text-decoration-none">Đăng ký</a>
        </p>
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
      // Toggle hiện/ẩn mật khẩu + đổi màu icon
      const togglePassword = $(".toggle-password");

      $(".toggle-password").on("click", function() {
        const passwordInput = togglePassword.prev();
        const type = passwordInput.attr("type") === "password" ? "text" : "password";
        passwordInput.attr("type", type);

        togglePassword.find('i').toggleClass("fa-eye-slash");
        togglePassword.find('i').toggleClass("toggle-active");
      });

      const btnLoader = makeButtonLoader($("#btnSubmit"));

      $("#loginForm").on("submit", function(e) {
        e.preventDefault();

        $.ajax({
          url: "../api/login_api.php",
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
              window.location.href = "../index.php";
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