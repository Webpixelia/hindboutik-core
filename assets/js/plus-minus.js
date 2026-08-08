jQuery(document).ready(function ($) {
    $(document).on('click', 'button.plus, button.minus', function () {
        var $qty = $(this).parent('.quantity').find('.qty');
        var val  = parseFloat($qty.val());
        var max  = parseFloat($qty.attr('max'));
        var min  = parseFloat($qty.attr('min'));
        var step = parseFloat($qty.attr('step')) || 1;

        if ($(this).is('.plus')) {
            if (max && max <= val) {
                $qty.val(max);
            } else {
                $qty.val(val + step);
            }
        } else {
            if (min && min >= val) {
                $qty.val(min);
            } else if (val > 1) {
                $qty.val(val - step);
            }
        }

        $qty.change();
    });
});