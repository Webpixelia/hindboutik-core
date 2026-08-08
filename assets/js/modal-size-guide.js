/**
 * HindBoutik Core — Size Guide Modal
 * Opens/closes the size guide modal window.
 */
jQuery(document).ready(function ($) {
    $('#size-guide-link').on('click', function (e) {
        e.preventDefault();
        $('.size-guide-modal').fadeIn();
    });

    $('.size-guide-modal .remodal-close').on('click', function (e) {
        e.preventDefault();
        $('.size-guide-modal').fadeOut();
    });
});