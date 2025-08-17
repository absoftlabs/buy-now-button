jQuery(function($){
    // Archive Buy Now button
    $(document).on('click', '.buy-now-button', function(e){
        e.preventDefault();
        var productId = $(this).data('product_id');
        window.location.href = '?buy_now_add=' + productId + '&quantity=1';
    });

    // Single product Buy Now button
    $(document).on('click', '.buy-now-button-single', function(e){
        e.preventDefault();
        var productId = $(this).data('product_id');
        var qty = $('form.cart input.qty').val() || 1;
        window.location.href = '?buy_now_add=' + productId + '&quantity=' + qty;
    });
});
