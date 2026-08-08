/**
 * HindBoutik Core — Account Page
 * Address toggle form scripts.
 */
jQuery(function ($) {
    // --- Toggle address sections ---
    $('.u-column1.col-1.woocommerce-Address address').addClass('active');
    $('.u-column1.col-1.woocommerce-Address header').addClass('active');

    $('.woocommerce-Address-title').addClass('toggle-header').click(function () {
        var $parentContainer = $(this).closest('.woocommerce-Address');

        if ($parentContainer.find('header').hasClass('active')) {
            return;
        }

        $parentContainer.find('address, header').toggleClass('active');
        $parentContainer.siblings('.woocommerce-Address').find('address, header').removeClass('active');
    });
});