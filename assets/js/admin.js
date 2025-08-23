jQuery(function($){
    // Turn text inputs into WP Color Pickers that still allow manual hex typing
    $('.abb-color-field').wpColorPicker({
        change: function(event, ui){
            // Write hex back to input
            var hex = ui.color.toString();
            $(event.target).val(hex);
        },
        clear: function(event){
            // Restore default when cleared
            var $input = $(event.target).prev('input.abb-color-field');
            if ($input.length && $input.data('default-color')) {
                $input.val($input.data('default-color'));
            }
        }
    });
});
