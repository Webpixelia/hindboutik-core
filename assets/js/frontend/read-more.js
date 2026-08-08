/**
 * HindBoutik Core — Category Read More
 * Truncates long product category descriptions.
 */
jQuery(function ($) {
    var threshold = parseInt(window.HINDBOUTIK_READ_MORE_THRESHOLD || 350, 10);

    var $container = $(".woocommerce-products-header .et_pb_text_inner");

    if (!$container.length) return;

    var originalHtml = $container.html();
    var plainText   = $container.text().trim();

    if (plainText.length <= threshold) return;

    var shortText = plainText.substring(0, threshold);

    $container.html(
        '<div class="excerpt">' +
            shortText +
            '... <a href="#" class="read-more">Lire la suite</a>' +
            '</div>' +
            '<div class="full-content" style="display:none;">' +
            originalHtml +
            '<p><a href="#" class="read-less">Afficher moins</a></p>' +
            '</div>'
    );

    $container.on('click', '.read-more', function (e) {
        e.preventDefault();
        $container.find('.excerpt').hide();
        $container.find('.full-content').show();
    });

    $container.on('click', '.read-less', function (e) {
        e.preventDefault();
        $container.find('.full-content').hide();
        $container.find('.excerpt').show();
    });
});