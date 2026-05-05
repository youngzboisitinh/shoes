<script>
    $(document).ready(function() {

        const csrfToken = '<?= $csrf_token ?>';

        $('.add-to-cart').on('click', function() {
            const data = {
                action: 'add',
                id: $(this).data('id'),
                csrf_token: csrfToken
            };
            const btnLoader = makeButtonLoader($(this));
            $.ajax({
                url: "../api/cart_api.php",
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
                url: "../api/product_api.php",
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
                        location.href = "buy_now.php?id=" + $(this).data('id');
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