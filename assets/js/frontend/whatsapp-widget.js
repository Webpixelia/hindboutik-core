/**
 * HindBoutik Core — WhatsApp Widget
 * Handles opening the WhatsApp chat window with the configured phone number.
 */
jQuery(function ($) {
    $('#hindboutik-whatsapp-widget').on('click', function (e) {
        // The link is already an <a href="https://wa.me/..."> so default behaviour
        // handles the navigation. This script adds analytics tracking.
        if (window.gtag) {
            window.gtag('event', 'click', {
                event_category: 'WhatsApp',
                event_label: 'widget_open'
            });
        }
    });
});