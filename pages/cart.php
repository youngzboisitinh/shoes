<?php
session_start();
include("../configs/db.php");

if (!isset($_SESSION['user_id'])) {
  header("Location: ../account/login.php");
  exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$user_id = $_SESSION['user_id'];
// Lấy danh sách giỏ hàng
$stmt = $conn->prepare("
  SELECT c.id as cart_id, c.quantity, 
         p.id as product_id, p.name, p.image, p.price as base_price, p.stock as product_stock,
         v.id as variant_id, v.name as variant_name, v.price as variant_price, v.stock as variant_stock
  FROM cart c
  JOIN products p ON p.id = c.product_id
  LEFT JOIN product_variants v ON v.id = c.variant_id
  WHERE c.user_id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$items = [];
while ($row = $result->fetch_assoc()) {
  $price = $row['variant_price'] ?? $row['base_price'];
  $row['price'] = $price;
  $row['subtotal'] = $price * $row['quantity'];
  $total += $row['subtotal'];
  $items[] = $row;
}


// Giảm giá
$discount = 0;
$coupon_code = "";

// Nếu đã có coupon thì tính giảm
if (isset($_SESSION['coupon'])) {
  $coupon = $_SESSION['coupon'];

  if (!empty($coupon['expiry'])) {
    $expiry = strtotime($coupon['expiry']); // convert string "2025-09-30" → timestamp
    if ($expiry < time()) {
      unset($_SESSION['coupon']);
      $msg = "<div class='alert alert-danger mt-3'>❌ Mã giảm giá đã hết hạn</div>";
    }
  }

  if (isset($_SESSION['coupon'])) { // nếu vẫn còn hợp lệ
    if ($coupon['type'] === 'percent') {
      $discount = ($total * $coupon['discount']) / 100;
    } else {
      $discount = $coupon['discount'];
    }
    $coupon_code = $coupon['code'];
  }
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giỏ hàng - Shoe Store</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
  <?php include("../layout/header.php") ?>
  <div class="container" style="padding-top: 80px;">
    <h2 id="cartTitle">
      <span><i class="fa-solid fa-cart-shopping"></i></span>
      Giỏ hàng của tôi
    </h2>
    <?php if (empty($items)): ?>
      <div class="alert alert-info">Giỏ hàng trống. <a href="products.php">Mua sắm ngay</a></div>
    <?php else: ?>
      <form id="cartForm" method="POST">
        <input type="hidden" id="csrfToken" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="action" value="update">
        <table class="table table-bordered text-center">
          <thead class="table-dark">
            <tr>
              <th>Ảnh</th>
              <th>Tên sản phẩm</th>
              <th>Giá</th>
              <th>Số lượng</th>
              <th>Thành tiền</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="cartTableBody">
            <?php foreach ($items as $p): ?>
              <tr>
                <td><img src="<?= $p['image'] ?>" width="80"></td>
                <td><?= htmlspecialchars($p['name']) ?>
                  <?php if ($p['variant_name']) echo " [" . htmlspecialchars($p['variant_name']) . "]"; ?>
                </td>
                <td><?= number_format($p['price'], 0, ',', '.') ?> VND</td>
                <td>
                  <input type="number" name="qty[<?= $p['cart_id'] ?>]" value="<?= $p['quantity'] ?>" 
                    class="form-control w-50 mx-auto input-qty">
                </td>
                <td><?= number_format($p['subtotal'], 0, ',', '.') ?> VND</td>
                <td>
                  <button type="button" class="btn btn-sm btn-danger remove-cart"
                    data-id="<?= $p['cart_id'] ?>">Xóa</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <!-- Coupon -->
        <div class="row mb-3">
          <div class="col-md-6">
            <input type="text" id="couponCode" class="form-control" placeholder="Nhập mã giảm giá"
              value="<?= $coupon_code ?>">
          </div>
          <div class="col-md-2">
            <button type="button" id="applyCoupon" class="btn btn-warning w-100">
              <span><i class="fa-solid fa-ticket"></i></span>
              Áp dụng
            </button>
          </div>
        </div>
        <?= $msg ?? "" ?>

        <!-- Tổng tiền -->
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5>Tạm tính: <span id="total"><?= number_format($total, 0, ',', '.') ?> VND</span></h5>
            <h5 id="discountText" <?php if ($discount <= 0) echo 'class="d-none"' ?>>
              Giảm giá: <span class="text-primary">-<?= number_format($discount, 0, ',', '.') ?> VND</span>
              (<?= $coupon_code ?>)
            </h5>
            <h4>
              Tổng thanh toán: <span id="final_total"
                class="text-danger"><?= number_format($total - $discount, 0, ',', '.') ?>
                VND</span>
            </h4>
          </div>

          <div class="text-end">
            <a href="checkout.php" class="btn btn-success">Thanh toán</a>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>
  <?php include("../layout/footer.php"); ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/script.js"></script>
  <script>
    function formatVND(n) {
      return n.toLocaleString("vi-VN", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }) + " VND";
    }


    function debounce(func, delay = 500) {
      let timeoutId; // This will store the ID of the timeout

      return function(...args) { // Returns a new function that acts as the debounced version
        const context = this; // Preserve the 'this' context

        clearTimeout(timeoutId); // Clear any existing timeout

        timeoutId = setTimeout(() => { // Set a new timeout
          func.apply(context, args); // Execute the original function with its context and arguments
        }, delay);
      };
    }
    $(document).ready(function() {

      const calculateTotal = (data) => {
        if (data.cart_count !== undefined) {
          $('#cartCount').text(data.cart_count);
          if (data.cart_count <= 0) {
            $('#cartCount').addClass('d-none');
          }
        }

        if (data.total) {
          $("#total").text(formatVND(data.total));
          $("#final_total").text(formatVND(data.total - (data
            .discount ?? 0)));
        }

        if (data.discount > 0) {
          $("#discountText").removeClass("d-none");
          $("#discountText").html(`
                    Giảm giá: <span class="text-primary">-${formatVND(data.discount)}</span> (${data.coupon_code})
                `);
        } else {
          $("#discountText").addClass("d-none");
        }
      };

      const renderCartItem = (data) => {
        if (data.items.length > 0) {
          let tbody = "";
          data.items.forEach(p => {
            tbody += `
                <tr>
                  <td><img src="${p.image}" width="80"></td>
                  <td>${p.name}${p.variant_name ? " ["+p.variant_name+"]" : ""}</td>
                  <td>${formatVND(p.price)}</td>
                  <td>
                    <input type="number" name="qty[${p.cart_id}]" value="${p.quantity}"
                      class="form-control w-50 mx-auto input-qty">
                  </td>
                  <td>${formatVND(p.subtotal)}</td>
                  <td><button type="button" class="btn btn-sm btn-danger remove-cart" data-id="${p.cart_id}">Xóa</button></td>
                </tr>`;
          });
          $("#cartTableBody").html(tbody);
        } else {
          $("#cartForm")[0].outerHTML =
            '<div class="alert alert-info">Giỏ hàng trống. <a href="products.php">Mua sắm ngay</a></div>';
        }
      };

      $("#cartForm").on("submit", function(e) {
        e.preventDefault();
        const btnLoader = makeButtonLoader($("#cartTitle"));
        $.ajax({
          url: "../api/cart_api.php",
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
            if (data.status === "success") {
              calculateTotal(data);
              renderCartItem(data);
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

      $(document).on("change", ".input-qty", debounce(function() {
        let $input = $(this);
        let max = parseInt($input.attr("max"));
        let val = parseInt($input.val());

        if (val > max) {
          $input.val(max);
        }

        $("#cartForm").trigger("submit"); // vẫn gọi AJAX
      }, 500));

      $("#applyCoupon").on("click", function() {
        const btnLoader = makeButtonLoader($(this));
        $.ajax({
          url: "../api/cart_api.php",
          type: "POST",
          data: {
            action: "apply_coupon",
            coupon_code: $("#couponCode").val(),
            csrf_token: $("#csrfToken").val()
          },
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
              calculateTotal(data);
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

      $(document).on("click", ".remove-cart", function() {
        const btnLoader = makeButtonLoader($(this));
        $.ajax({
          url: "../api/cart_api.php",
          type: "POST",
          data: {
            action: "remove",
            cart_id: $(this).data("id"),
            csrf_token: $("#csrfToken").val()
          },
          dataType: "json",
          beforeSend: () => {
            btnLoader.showLoading();
          },
          complete: () => {
            btnLoader.showDefault();
          },
          success: (data) => {
            if (data.status === "success") {
              calculateTotal(data);
              renderCartItem(data);
            }
          },
          error: (xhr, status, error) => {
            Swal.fire({
              icon: "error",
              title: "Lỗi server",
              text: "Không thể gửi yêu cầu. Vuiど thử lagi!",
            });
            console.error(error);
          }
        });
      });

    });
  </script>
</body>

</html>