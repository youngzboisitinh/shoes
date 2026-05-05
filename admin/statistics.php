<?php
include("../configs/db.php");
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../index.php");
  exit;
}

// Tổng người dùng
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users");
$stmt->execute();
$users_result = $stmt->get_result()->fetch_assoc();

// Tổng sản phẩm
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products");
$stmt->execute();
$products_result = $stmt->get_result()->fetch_assoc();

// Tổng đơn hàng
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM orders");
$stmt->execute();
$orders_result1 = $stmt->get_result()->fetch_assoc();


// ================== Doanh thu theo tháng (12 tháng gần nhất) ==================
$stmt = $conn->prepare("
  SELECT DATE_FORMAT(created_at, '%m-%Y') AS month, SUM(total_price) AS revenue
  FROM orders
  WHERE status = 'completed'
  GROUP BY month
  ORDER BY MIN(created_at) ASC
");
$stmt->execute();
$monthly = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tạo mảng 12 tháng gần nhất (kể cả doanh thu = 0)
$months = [];
for ($i = 11; $i >= 0; $i--) {
  $m = date("m-Y", strtotime("-$i month"));
  $months[$m] = 0;
}
foreach ($monthly as $row) {
  $months[$row['month']] = (int)$row['revenue'];
}
$labels = array_keys($months);
$revenues = array_values($months);


// ================== Doanh thu theo ngày (7 ngày gần nhất) ==================
$stmt = $conn->prepare("
  SELECT DATE(created_at) AS day, SUM(total_price) AS revenue
  FROM orders
  WHERE status = 'completed'
  GROUP BY day
  HAVING day >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
  ORDER BY day ASC
");
$stmt->execute();
$daily = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tạo mảng 7 ngày gần nhất (dd-mm)
$days = [];
for ($i = 6; $i >= 0; $i--) {
  $d = date("d-m", strtotime("-$i day"));
  $days[$d] = 0;
}
foreach ($daily as $row) {
  $key = date("d-m", strtotime($row['day']));
  $days[$key] = (int)$row['revenue'];
}
$day_labels = array_keys($days);
$day_revenues = array_values($days);


// ================== Đơn hàng trong tháng và tuần hiện tại ==================
$stmt = $conn->prepare("
  SELECT COUNT(*) AS total 
  FROM orders 
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$stmt->execute();
$order_month = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("
  SELECT COUNT(*) AS total 
  FROM orders 
  WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURRENT_DATE(), 1)
");
$stmt->execute();
$order_week = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>Admin Dashboard</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="../assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <div class="container-fluid">
    <div class="row">
      <?php include("../layout/admin_header.php") ?>
      <!-- Main Content -->
      <main class="col-12 col-md-10 ms-sm-auto px-md-4 dashboard-content">
        <h2 class="mb-4">📊 Thống kê</h2>
        <!-- Counter -->
        <section class="my-2">
          <div class="row">
            <div class="col-sm-6 col-lg-3">
              <div class="card shadow d-flex align-items-center justify-content-center gap-2 p-3">
                <h5>Người dùng</h5>
                <h3 class="counter text-primary" data-interval-delay="50"
                  data-target="<?= $users_result['total'] ?>">0</h3>
              </div>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="card shadow d-flex align-items-center justify-content-center gap-2 p-3">
                <h5>Sản phẩm</h5>
                <h3 class="counter text-success" data-target="<?= $products_result['total'] ?>">0</h3>
              </div>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="card shadow d-flex align-items-center justify-content-center gap-2 p-3">
                <h5>Đơn hàng</h5>
                <h3 class="counter text-warning" data-target="<?= $orders_result1['total'] ?>">0</h3>
              </div>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="card shadow d-flex align-items-center justify-content-center gap-2 p-3">
                <h5>Doanh thu</h5>
                <h3 class="counter text-danger" data-target="<?= array_sum($revenues) ?>"
                  data-suffix=" VND" data-interval-delay="10">0</h3>
              </div>
            </div>
          </div>
        </section>

        <div class="mt-5">
          <h4>📈 Doanh thu trong tháng</h4>
          <canvas id="revenueChart" height="100"></canvas>
        </div>

        <div class="mt-5">
          <h4>📆 Doanh thu trong tuần</h4>
          <canvas id="dailyRevenueChart" height="100"></canvas>
        </div>

        <div class="mt-5">
          <h4>📦 Đơn hàng</h4>
          <ul class="list-group">
            <li class="list-group-item">
              Trong tháng: <strong><?= $order_month['total'] ?></strong> đơn
            </li>
            <li class="list-group-item">
              Trong tuần: <strong><?= $order_week['total'] ?></strong> đơn
            </li>
          </ul>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function formatVND(n) {
      return n.toLocaleString("vi-VN", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      });
    }

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

    // Biểu đồ doanh thu theo tháng
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
          label: 'Doanh thu (VND)',
          data: <?= json_encode($revenues) ?>,
          borderColor: 'rgba(13,110,253,1)',
          backgroundColor: 'rgba(13,110,253,0.2)',
          borderWidth: 2,
          tension: 0.3,
          fill: true,
          pointBackgroundColor: '#0d6efd'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: true,
            position: 'top'
          }
        },
        scales: {
          y: {
            ticks: {
              callback: function(value) {
                return value.toLocaleString() + ' đ';
              }
            }
          }
        }
      }
    });

    // Biểu đồ doanh thu 7 ngày gần nhất
    const ctxDaily = document.getElementById('dailyRevenueChart').getContext('2d');
    new Chart(ctxDaily, {
      type: 'bar',
      data: {
        labels: <?= json_encode($day_labels) ?>,
        datasets: [{
          label: 'Doanh thu (VND)',
          data: <?= json_encode($day_revenues) ?>,
          backgroundColor: 'rgba(25,135,84,0.7)',
          borderColor: 'rgba(25,135,84,1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            ticks: {
              callback: function(value) {
                return value.toLocaleString() + ' đ';
              }
            }
          }
        }
      }
    });
  </script>

</body>

</html>