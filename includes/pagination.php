<?php
$type = $type ?? '';
$keyword = $keyword ?? '';
function buildPageUrl($page, $category_id = 0, $keyword = '', $type = '')
{
    $params = [];
    if ($keyword !== '') {
        $params['q'] = $keyword;
    }
    if ($type !== '') {
        $params['type'] = $type;
    }
    if ($category_id) {
        $params['category_id'] = $category_id;
    }
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>

<?php if ($total_pages > 1): ?>
    <nav style="overflow: auto;">
        <ul class="pagination justify-content-center">
            <!-- Nút First page -->
            <?php if (isset($_GET['page']) && $_GET['page'] != 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= buildPageUrl(1, $category_id, $keyword, $type) ?>">
                        <i class="fa-solid fa-angles-left"></i>
                    </a>
                </li>
            <?php endif; ?>
            <!-- Nút Previous -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= buildPageUrl(max(1, $page - 1), $category_id, $keyword, $type) ?>">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
            </li>
            <!-- Các số trang -->
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= buildPageUrl($i, $category_id, $keyword, $type) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

            <!-- Nút Next -->
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="<?= buildPageUrl(min($total_pages, $page + 1), $category_id, $keyword, $type) ?>">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
            </li>
            <!-- Nút Last page -->
            <?php if (isset($_GET['page']) && $_GET['page'] != $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= buildPageUrl($total_pages, $category_id, $keyword, $type) ?>">
                        <i class="fa-solid fa-angles-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>