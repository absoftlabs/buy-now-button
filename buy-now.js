jQuery(function($){
    // Archive Buy Now button (simple products only)
    $(document).on('click', '.buy-now-button', function(e){
        e.preventDefault();
        var productId = $(this).data('product_id');
        window.location.href = '?buy_now_add=' + productId + '&quantity=1';
    });

    // Single product Buy Now button (handles simple + variable products)
    $(document).on('click', '.buy-now-button-single', function(e){
        e.preventDefault();

        var $form = $('form.cart');
        var productId = $(this).data('product_id');
        var qty = $form.find('input.qty').val() || 1;
        var variationId = $form.find('input[name=variation_id]').val();

        // Collect selected variation attributes
        var attributes = {};
        $form.find('select[name^=attribute], input[name^=attribute]:checked').each(function(){
            attributes[$(this).attr('name')] = $(this).val();
        });

        // Build URL with variation data if variable product
        var url = '?buy_now_add=' + productId + '&quantity=' + qty;

        if (variationId && variationId !== '0') {
            url += '&variation_id=' + variationId;
            $.each(attributes, function(name, value){
                url += '&' + encodeURIComponent(name) + '=' + encodeURIComponent(value);
            });
        }

        window.location.href = url;
    });
});
