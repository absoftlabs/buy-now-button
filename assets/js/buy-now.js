jQuery(function($){
    function disableBtn($btn) {
        $btn.addClass('is-loading').prop('disabled', true).css('opacity', 0.7);
    }
    function enableBtn($btn) {
        $btn.removeClass('is-loading').prop('disabled', false).css('opacity', '');
    }

    function doRipple($btn) {
        if ($btn.hasClass('bn-anim-ripple')) {
            $btn.addClass('is-active');
            setTimeout(function(){ $btn.removeClass('is-active'); }, 220);
        }
    }

    function postBuyNow(payload, $btn) {
        disableBtn($btn);
        $.ajax({
            url: abbBnVars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: $.extend({ action: 'abb_bn_buy_now', nonce: abbBnVars.nonce }, payload),
            success: function(res){
                if (res && res.success && res.data && res.data.redirect) {
                    window.location.href = res.data.redirect;
                } else {
                    console.error('Buy Now error', res);
                    enableBtn($btn);
                }
            },
            error: function(xhr){
                console.error('Buy Now AJAX failed', xhr);
                enableBtn($btn);
            }
        });
    }

    // Archive: simple products
    $(document).on('click', '.buy-now-button', function(e){
        e.preventDefault();
        var $btn = $(this);
        doRipple($btn);
        postBuyNow({
            product_id: $btn.data('product_id'),
            quantity: 1
        }, $btn);
    });

    // Single product: simple + variable
    $(document).on('click', '.buy-now-button-single', function(e){
        e.preventDefault();
        var $btn = $(this);
        doRipple($btn);

        var $form = $('form.cart');
        var qty = $form.find('input.qty').val() || 1;
        var variationId = $form.find('input[name=variation_id]').val();

        // Collect selected variation attributes
        var attributes = {};
        $form.find('select[name^=attribute], input[name^=attribute]:checked').each(function(){
            attributes[$(this).attr('name')] = $(this).val();
        });

        var payload = {
            product_id: $btn.data('product_id'),
            quantity: qty
        };
        if (variationId && variationId !== '0') {
            payload.variation_id = variationId;
            payload.attributes = attributes;
        }
        postBuyNow(payload, $btn);
    });
});
