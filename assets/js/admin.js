jQuery(function ($) {
    // WP Color Pickers (hex typing allowed)
    $('.abb-color-field').wpColorPicker({
        change: function (event, ui) {
            $(event.target).val(ui.color.toString()).trigger('input');
        },
        clear: function (event) {
            var $input = $(event.target).prev('input.abb-color-field');
            if ($input.length && $input.data('default-color')) {
                $input.val($input.data('default-color')).trigger('input');
            }
        }
    });

    const $demo = $('#abb-bn-demo');

    function setAnimClasses($el, anim, state) {
        // Remove any previous bn-anim-* classes
        $el.removeClass(function (i, cls) { return (cls.match(/bn-anim-[^\s]+/g) || []).join(' '); });
        if (anim && anim !== 'none') {
            $el.addClass('bn-anim-' + anim);
            if (state === 'normal') $el.addClass('bn-anim-normal'); // Always on
        }
    }

    function updatePreview() {
        const text = $('#abb-bn-button-text').val() || 'Buy Now';
        const align = $('#abb-bn-text-align').val();
        const normal = $('#abb-bn-color-normal').val() || '#1e40af';
        const hover = $('#abb-bn-color-hover').val() || '#1d4ed8';
        const active = $('#abb-bn-color-active').val() || '#1e3a8a';
        const textColor = $('#abb-bn-text-color').val() || '#ffffff';
        const radius = parseInt($('#abb-bn-radius').val() || 8, 10);
        const anim = $('#abb-bn-animation').val();
        const astate = $('#abb-bn-animation-state').val();
        const state = $('input[name="abb-bn-state"]:checked').val() || 'normal';

        // Text + radius + align
        let justify = 'center';
        if (align === 'left') justify = 'flex-start';
        if (align === 'right') justify = 'flex-end';

        $demo.text(text)
            .css({
                'border-radius': radius + 'px',
                display: 'inline-flex',
                'justify-content': justify,
                'text-align': align,
                color: textColor,
                border: '0',
                padding: '10px 16px'
            });

        // State-based background
        let bg = normal;
        if (state === 'hover') bg = hover;
        if (state === 'active') bg = active;
        $demo.css({ background: bg, 'background-color': bg, 'border-color': bg });

        // Animation classes
        setAnimClasses($demo, anim, astate);
    }

    // React to any changes
    $(document).on('input change',
        '#abb-bn-button-text, #abb-bn-text-align, #abb-bn-color-normal, #abb-bn-color-hover, #abb-bn-color-active, #abb-bn-text-color, #abb-bn-radius, #abb-bn-animation, #abb-bn-animation-state, input[name="abb-bn-state"]',
        updatePreview
    );

    // Initial render
    updatePreview();
});
