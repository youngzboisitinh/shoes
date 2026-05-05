<?php
session_start();
include("../configs/db.php");

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
  header("Location: ../account/login.php");
  exit;
}

$user_id    = $_SESSION['user_id'];
$email      = $_SESSION['email'] ?? '';
$balance    = floatval($_SESSION['balance'] ?? 0);
$full_name  = $_SESSION['full_name'] ?? '';
$phone      = $_SESSION['phone'] ?? '';
$address    = $_SESSION['address'] ?? '';

$msg = NULL;
$error = NULL;

// Kiểm tra giỏ hàng trong DB
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cart WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res['total'] == 0) {
  header("Location: ../pages/cart.php");
  exit;
}


// Lấy danh sách giỏ hàng
$stmt = $conn->prepare("
  SELECT c.id as cart_id, c.quantity, c.price as cart_price,
         p.id as product_id, p.name, p.image, p.price as base_price,
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
  $price = isset($row['cart_price']) ? floatval($row['cart_price']) : ($row['variant_price'] ?? $row['base_price']);
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
$final_total = $total - $discount;

// XỬ LÝ ĐẶT HÀNG
if (isset($_POST['checkout'])) {
  $address = trim($_POST['address']);
  $phone   = trim($_POST['phone']);
  $payment = $_POST['payment'];

  // Kiểm tra tồn kho
  // foreach ($items as $row) {
  //   if ($row['quantity'] > $row['variant_stock']) {
  //     $error = "<div class='alert alert-danger text-center'>❌ Sản phẩm <b>" . htmlspecialchars($row['name']) . "</b> không đủ hàng!</div>";
  //     break;
  //   }
  // }

  if (!$error) {
    $conn->begin_transaction();
    try {
      if ($payment === "BALANCE" && $final_total > $balance) {
        throw new Exception("Số dư trong tài khoản không đủ để thanh toán.");
      }

      // Insert order
      $stmt = $conn->prepare("INSERT INTO orders (user_id, payment_method, total_price, status) VALUES (?, ?, ?, 'pending')");
      $stmt->bind_param("isd", $user_id, $payment, $final_total);
      $stmt->execute();
      $order_id = $conn->insert_id;
      $stmt->close();

      // Insert order_items + trừ stock
      $stmtInsertItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price) VALUES (?,?,?,?,?)");
      $stmtUpdateVariant = $conn->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");
      $stmtUpdateProduct = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

      foreach ($items as $item) {
        $v = $item['variant_id'];
        $stmtInsertItem->bind_param("iiiid", $order_id, $item['product_id'], $v, $item['quantity'], $item['price']);
        $stmtInsertItem->execute();

        if (!is_null($v)) {
          $stmtUpdateVariant->bind_param("ii", $item['quantity'], $v);
          $stmtUpdateVariant->execute();
        } else {
          $stmtUpdateProduct->bind_param("ii", $item['quantity'], $item['product_id']);
          $stmtUpdateProduct->execute();
        }
      }

      // Xóa giỏ hàng
      $stmtDel = $conn->prepare("DELETE FROM cart WHERE user_id=?");
      $stmtDel->bind_param("i", $user_id);
      $stmtDel->execute();
      $stmtDel->close();

      // Nếu dùng BALANCE thì trừ tiền
      if ($payment === "BALANCE") {
        $stmtBal = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmtBal->bind_param("di", $final_total, $user_id);
        $stmtBal->execute();
        $stmtBal->close();
        $_SESSION['balance'] = floatval($balance - $final_total);
      }

      if (isset($_SESSION['coupon'])) {
        $coupon_id = $_SESSION['coupon']['id'];

        $stmt1 = $conn->prepare("UPDATE coupons SET quantity = quantity - 1 WHERE id = ? LIMIT 1");
        $stmt1->bind_param("i", $coupon_id);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("INSERT INTO coupon_usage (coupon_id, user_id) VALUES (?, ?)");
        $stmt2->bind_param("ii", $coupon_id, $user_id);
        $stmt2->execute();
        $stmt2->close();
      }


      $conn->commit();

      $msg = "
        <div class='card shadow-lg border-success'>
          <div class='card-body text-center'>
            <h3 class='text-success'>✅ Đặt hàng thành công!</h3>
            <p class='lead'>Cảm ơn bạn <b>" . htmlspecialchars($full_name) . "</b> đã mua sắm tại cửa hàng.</p>
            <p>Mã đơn hàng của bạn: <b>#{$order_id}</b></p>
            <a href='products.php' class='btn btn-primary mt-3'>Tiếp tục mua sắm</a>
            <a href='orders.php' class='btn btn-outline-success mt-3'>Xem đơn hàng của tôi</a>
          </div>
        </div>";
    } catch (Exception $e) {
      $conn->rollback();
      $error = "<div class='alert alert-danger text-center'>❌ Lỗi đặt hàng: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thanh toán - Shoe Store</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
  <?php include("../layout/header.php"); ?>

  <div class="container" style="padding-top: 80px;">
    <?php if (!empty($msg)): ?>
      <?= $msg ?>
    <?php else: ?>
      <?php if (!empty($error)) echo $error; ?>
      <h2>🛒 Xác nhận thanh toán</h2>
      <table class="table table-bordered text-center">
        <thead class="table-dark">
          <tr>
            <th>Ảnh</th>
            <th>Tên sản phẩm</th>
            <th>Giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $p): ?>
            <tr>
              <td><img src="<?= $p['image'] ?>" width="80"></td>
              <td><?= htmlspecialchars($p['name']) ?>
                <?php if ($p['variant_name']) echo " [" . htmlspecialchars($p['variant_name']) . "]"; ?>
              </td>
              <td><?= number_format($p['price'], 0, ',', '.') ?> VND</td>
              <td><?= $p['quantity'] ?></td>
              <td><?= number_format($p['subtotal'], 0, ',', '.') ?> VND</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <!-- Tổng tiền -->
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5>Tạm tính: <?= number_format($total, 0, ',', '.') ?> VND</h5>
          <?php if ($discount > 0): ?>
            <h5>Giảm giá: -<?= number_format($discount, 0, ',', '.') ?> VND</h5>
          <?php endif; ?>
          <h4>Tổng thanh toán: <span class="text-danger"><?= number_format($total - $discount, 0, ',', '.') ?>
              VND</span></h4>
        </div>
      </div>
      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Họ tên</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($full_name) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Số điện thoại</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Địa chỉ giao hàng</label>
          <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($address) ?>"
            required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Phương thức thanh toán</label>
          <select name="payment" class="form-select" required>
            <option value="COD">💵 Thanh toán khi nhận hàng</option>
            <option value="BALANCE">💰 Số dư tài khoản</option>
          </select>
        </div>
        <div class="col-md-12">
          <button type="submit" name="checkout" class="btn btn-success w-100">Xác nhận đặt hàng</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
  <?php include("../layout/footer.php"); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>