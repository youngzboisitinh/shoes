 <div class="row g-2 mb-4" data-masonry='{"itemSelector": ".grid-item", "percentPosition": true }'>
     <?php if ($products && $products->num_rows > 0): ?>
     <?php while ($row = $products->fetch_assoc()): ?>
     <div class="grid-item col-sm-6 col-md-4 col-lg-3">
         <div class="card h-100 shadow-sm">
             <a href="./product_detail.php?id=<?= $row['id'] ?>">
                 <img src="<?= htmlspecialchars($row['image']) ?>" class="card-img-top"
                     alt="<?= htmlspecialchars($row['name']) ?>" style="height:200px; object-fit:cover;"
                     onerror="this.src='../uploads/default-shoe.jpg';">
             </a>
             <div class="card-body d-flex flex-column">
                 <div class="product-texts">
                     <a href="./product_detail.php?id=<?= $row['id'] ?>"
                         class="card-title text-link"><?= htmlspecialchars($row['name']) ?></a>
                     <div>
                         <?php if (isset($row['cat_name'])): ?>
                         <span class="badge bg-secondary small">
                             <?= htmlspecialchars($row['cat_name']) ?>
                         </span>
                         <?php endif; ?>
                         <?php if (isset($row['variant_count']) && $row['variant_count'] > 0): ?>
                         <span class="badge bg-info text-dark small">
                             <?= $row['variant_count'] ?> phân loại
                         </span>
                         <?php endif; ?>
                     </div>

                     <?php if (isset($row['description'])): ?>
                     <span class="card-text product-description">
                         <?= htmlspecialchars($row['description']) ?>
                     </span>
                     <?php endif; ?>
                     <span class="card-text text-danger fw-bold">
                         <?= number_format($row['price'], 0, ',', '.') ?> VND
                     </span>
                 </div>
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
     <?php else: ?>
     <div class="grid-item col-12 text-center">
         <div class="alert alert-info">Không tìm thấy sản phẩm nào.</div>
     </div>
     <?php endif; ?>
 </div>