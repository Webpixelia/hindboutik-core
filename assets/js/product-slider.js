/**
 * HindBoutik Core — Product Image Slider (Slick)
 */
jQuery(document).ready(function ($) {
    $('.product-image-slider').slick({
        dots: true,
        arrows: true,
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1
    });
});