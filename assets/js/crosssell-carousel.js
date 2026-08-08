/**
 * HindBoutik Core — Cross-sell Carousel
 * Swiper initialization + AJAX add-to-cart.
 */
jQuery(document).ready(function ($) {
    var swiper = new Swiper('.crosssell-swiper', {
        slidesPerView: 2,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            0:   { slidesPerView: 2, spaceBetween: 15 },
            768: { slidesPerView: 3, spaceBetween: 20 },
            1024:{ slidesPerView: 4, spaceBetween: 30 },
        }
    });

    // AJAX add-to-cart.
    $('.add-to-cart-btn').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var productId = $button.data('product_id');

        $button.addClass('loading').text('Ajout...');

        $.ajax({
            url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
            type: 'POST',
            data: {
                product_id: productId,
                quantity: 1
            },
            success: function (response) {
                if (response.error) {
                    alert(response.error_message);
                } else {
                    $button.removeClass('loading').text('Ajouté !');
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                    setTimeout(function () {
                        $button.text('Ajouter au panier');
                    }, 2000);
                }
            },
            error: function () {
                $button.removeClass('loading').text('Erreur');
                setTimeout(function () {
                    $button.text('Ajouter au panier');
                }, 2000);
            }
        });
    });
});