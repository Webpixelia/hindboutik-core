/**
 * HindBoutik Core — Top Bar Admin
 * Handles the icon media uploader.
 */
jQuery(document).ready(function ($) {
    $('.hindboutik-select-icon').on('click', function (e) {
        e.preventDefault();
        var input = $('#hindboutik_top_bar_icon_input');
        var preview = $('.hindboutik-icon-preview');

        var frame = wp.media({
            title: '<?php esc_attr_e('Sélectionner une icône', 'hindboutik-core'); ?>',
            button: '<?php esc_attr_e('Utiliser cette icône', 'hindboutik-core'); ?>',
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            input.val(attachment.id);
            preview.html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:50px;"/>');
        });

        frame.open();
    });
});