/**
 * HindBoutik Core — Checkout Free Shipping Label
 * Replaces "Gratuit" with "OFFERT" for relay point shipping methods.
 */
(function () {
    var attempts = 0;
    var maxAttempts = 50;

    function replaceFreeShipping() {
        var elements = document.querySelectorAll('.wc-block-checkout__shipping-option--free');
        var replaced = false;

        elements.forEach(function (el) {
            if (el.textContent === 'Gratuit') {
                var label = el.closest('label');
                var input = label ? label.querySelector('input[type="radio"]') : null;

                if (input && input.value && input.value.includes('lpc_relay')) {
                    el.textContent = 'OFFERT';
                    replaced = true;
                }
            }
        });

        return replaced;
    }

    function tryReplace() {
        attempts++;

        if (replaceFreeShipping()) {
            return;
        }

        if (attempts < maxAttempts) {
            setTimeout(tryReplace, 200);
        }
    }

    // MutationObserver for dynamically added content.
    var observer = new MutationObserver(function (mutations) {
        var shouldCheck = false;

        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1 &&
                        (node.classList && node.classList.contains('wc-block-checkout__shipping-option--free') ||
                         node.querySelector && node.querySelector('.wc-block-checkout__shipping-option--free'))) {
                        shouldCheck = true;
                    }
                });
            }
        });

        if (shouldCheck) {
            setTimeout(replaceFreeShipping, 100);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        observer.observe(document.body, { childList: true, subtree: true });
        setTimeout(tryReplace, 500);
    });

    // WooCommerce block events.
    document.addEventListener('wc-blocks_checkout_shipping_rates_changed', replaceFreeShipping);
    document.addEventListener('wc-blocks_checkout_updated', replaceFreeShipping);
})();