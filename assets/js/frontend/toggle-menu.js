/**
 * HindBoutik Core — Product Details Toggle Menu
 * Adds accordion-style toggle to product detail sections.
 */
jQuery(function ($) {
    var toggleMenuSelector = '.product-details-item';

    // Add arrow icon to section titles.
    $(toggleMenuSelector + ' .wrapper__section-title').prepend(
        '<span class="arrow-svg-icon">' +
        '<svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        '<polyline points="6 9 12 15 18 9"></polyline>' +
        '</svg></span>'
    );

    // Hide all sections on load.
    $(toggleMenuSelector + ' .wrapper__section-content').hide();

    // Click handler.
    $(toggleMenuSelector).click(function () {
        var menu = $(this).find('.wrapper__section-content');
        menu.toggle();
        $(this).toggleClass('active');
    });
});