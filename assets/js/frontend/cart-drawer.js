/**
 * HindBoutik Core — Tiroir panier (cart drawer)
 *
 * L'ajout au panier depuis la fiche produit (`form.cart`) reste natif
 * (soumission classique, page rechargée) : voir le bloc commenté plus bas
 * pour la version AJAX, désactivée le temps d'écarter toute interaction
 * avec le cache LiteSpeed/Cloudflare, à réactiver le jour où le site
 * passera entièrement en AJAX.
 *
 * Le tiroir reste néanmoins alimenté par les boutons AJAX natifs de la
 * boucle produits (WooCommerce déclenche déjà `added_to_cart` sur
 * `document.body` dans ce cas) et par le bouton "Ajouter" des suggestions
 * cross-sell à l'intérieur du tiroir lui-même (cf. plus bas).
 */
jQuery(document).ready(function ($) {
    var i18n = window.hdbCartDrawer || {};
    var addText    = i18n.addText    || 'Ajouter';
    var addingText = i18n.addingText || 'Ajout…';

    /* ———————————————————————— Helpers génériques ———————————————————————— */

    function wcAjaxUrl(endpoint) {
        var base = (window.wc_cart_fragments_params && wc_cart_fragments_params.wc_ajax_url)
            || (window.wc_add_to_cart_params && wc_add_to_cart_params.wc_ajax_url)
            || '/?wc-ajax=%%endpoint%%';

        return base.toString().replace('%%endpoint%%', endpoint);
    }

    function applyFragments(fragments) {
        if (!fragments) {
            return;
        }

        $.each(fragments, function (key, html) {
            $(key).replaceWith(html);
        });
    }

    function $drawer() {
        return $('#hdb-cart-drawer');
    }

    function $overlay() {
        return $('#hdb-cart-drawer-overlay');
    }

    function setTitle(mode) {
        var $title = $drawer().find('.hdb-cart-drawer__title');

        if (!$title.length) {
            return;
        }

        $title.text(mode === 'added' ? $title.data('added') : $title.data('default'));
    }

    function openDrawer() {
        $drawer().addClass('is-open');
        $overlay().addClass('is-open');
        $('body').addClass('hdb-cart-drawer-open');
    }

    function closeDrawer() {
        $drawer().removeClass('is-open');
        $overlay().removeClass('is-open');
        $('body').removeClass('hdb-cart-drawer-open');
    }

    /* ———————————————————— Ajout au panier (fiche produit) ———————————————————— */

    // DÉSACTIVÉ (2026-08) : hijack AJAX du submit de `form.cart`, remplacé par
    // le comportement natif WooCommerce (soumission classique, rechargement).
    // Conservé ici pour réactivation future — voir commentaire d'en-tête du
    // fichier pour le contexte.
    /*
    $(document).on('submit', 'form.cart', function (e) {
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"], input[type="submit"]').first();
        var productId = $submitBtn.attr('name') === 'add-to-cart'
            ? $submitBtn.val()
            : $form.find('input[name="add-to-cart"]').val();

        if (!productId) {
            // Pas de add-to-cart identifiable : on laisse WooCommerce gérer nativement.
            return;
        }

        e.preventDefault();

        var data = {
            product_id: productId,
            quantity: $form.find('input.qty').val() || 1,
        };

        var variationId = $form.find('input[name="variation_id"]').val();

        if (variationId) {
            data.variation_id = variationId;
            $form.find('[name^="attribute_"]').each(function () {
                data[$(this).attr('name')] = $(this).val();
            });
        }

        $submitBtn.prop('disabled', true).addClass('is-loading');

        $.ajax({
            type: 'POST',
            url: wcAjaxUrl('add_to_cart'),
            data: data,
            dataType: 'json',
        }).done(function (response) {
            $submitBtn.prop('disabled', false).removeClass('is-loading');

            if (!response) {
                return;
            }

            if (response.error) {
                // Rupture / erreur de sélection : on laisse WooCommerce gérer
                // l'affichage natif de l'erreur (redirection avec le message).
                if (response.product_url) {
                    window.location = response.product_url;
                }
                return;
            }

            applyFragments(response.fragments);
            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $submitBtn]);
        }).fail(function () {
            $submitBtn.prop('disabled', false).removeClass('is-loading');
            // Repli : on soumet le formulaire normalement (rechargement classique).
            $form.off('submit').trigger('submit');
        });
    });
    */

    // Bouton "Ajouter" sur une suggestion cross-sell à l'intérieur du tiroir.
    $(document).on('click', '.hdb-cart-drawer__add', function () {
        var $btn = $(this);
        var productId = $btn.data('product-id');

        if (!productId) {
            return;
        }

        $btn.prop('disabled', true).text(addingText);

        $.ajax({
            type: 'POST',
            url: wcAjaxUrl('add_to_cart'),
            data: { product_id: productId, quantity: 1 },
            dataType: 'json',
        }).done(function (response) {
            if (!response || response.error) {
                $btn.prop('disabled', false).text(addText);
                return;
            }

            applyFragments(response.fragments);
            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
        }).fail(function () {
            $btn.prop('disabled', false).text(addText);
        });
    });

    /* ———————————————————— Suppression d'un article (croix) ———————————————————— */

    $(document).on('click', '.hdb-cart-drawer__remove', function () {
        var $btn = $(this);
        var $line = $btn.closest('.hdb-cart-drawer__line');
        var itemKey = $btn.data('cart-item-key');

        if (!itemKey || $line.hasClass('is-busy')) {
            return;
        }

        clearTimeout(qtyTimers[itemKey]); // annule un changement de quantité en attente
        $line.addClass('is-busy');
        $btn.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: wcAjaxUrl('hdb_remove_cart_item'),
            data: { cart_item_key: itemKey },
            dataType: 'json',
        }).done(function (response) {
            if (!response || response.error || !response.fragments) {
                $line.removeClass('is-busy');
                $btn.prop('disabled', false);
                return;
            }

            // Le panneau est remplacé par les fragments : on conserve son état
            // ouvert et la position de scroll du corps du tiroir.
            var scrollTop = $drawer().find('.hdb-cart-drawer__body').scrollTop();

            applyFragments(response.fragments);
            $drawer().addClass('is-open');
            $drawer().find('.hdb-cart-drawer__body').scrollTop(scrollTop);

            $(document.body).trigger('removed_from_cart', [response.fragments, response.cart_hash, $btn]);
        }).fail(function () {
            $line.removeClass('is-busy');
            $btn.prop('disabled', false);
        });
    });

    /* ———————————————————— Quantité d'un article (− / +) ———————————————————— */

    var qtyTimers = {};
    var noticeTimer = null;

    function showLineNotice($line, message) {
        $line.find('.hdb-cart-drawer__notice').remove();
        $('<div class="hdb-cart-drawer__notice" role="alert"></div>')
            .text(message)
            .appendTo($line.find('.hdb-cart-drawer__info'));

        clearTimeout(noticeTimer);
        noticeTimer = setTimeout(function () {
            $('.hdb-cart-drawer__notice').remove();
        }, 4000);
    }

    function refreshQtyButtons($qty, value) {
        var min = parseInt($qty.data('min'), 10) || 1;
        var max = parseInt($qty.data('max'), 10);

        $qty.find('[data-dir="-1"]').prop('disabled', value <= min);
        $qty.find('[data-dir="1"]').prop('disabled', max > 0 && value >= max);
    }

    function sendQuantity($qty, itemKey, revertTo) {
        var $line = $qty.closest('.hdb-cart-drawer__line');
        var quantity = parseInt($qty.find('.hdb-cart-drawer__qty-value').text(), 10);

        $line.addClass('is-busy');

        $.ajax({
            type: 'POST',
            url: wcAjaxUrl('hdb_update_cart_item_qty'),
            data: { cart_item_key: itemKey, quantity: quantity },
            dataType: 'json',
        }).done(function (response) {
            if (!response || response.error || !response.fragments) {
                // Refus (stock, bornes…) : on remet l'ancienne quantité et on explique.
                $qty.find('.hdb-cart-drawer__qty-value').text(revertTo);
                refreshQtyButtons($qty, revertTo);
                $line.removeClass('is-busy');

                if (response && response.message) {
                    showLineNotice($line, response.message);
                }
                return;
            }

            var scrollTop = $drawer().find('.hdb-cart-drawer__body').scrollTop();

            applyFragments(response.fragments);
            $drawer().addClass('is-open');
            $drawer().find('.hdb-cart-drawer__body').scrollTop(scrollTop);

            $(document.body).trigger('updated_cart_totals');
        }).fail(function () {
            $qty.find('.hdb-cart-drawer__qty-value').text(revertTo);
            refreshQtyButtons($qty, revertTo);
            $line.removeClass('is-busy');
        });
    }

    $(document).on('click', '.hdb-cart-drawer__qty-btn', function () {
        var $qty = $(this).closest('.hdb-cart-drawer__qty');
        var $value = $qty.find('.hdb-cart-drawer__qty-value');
        var itemKey = $qty.data('cart-item-key');
        var min = parseInt($qty.data('min'), 10) || 1;
        var max = parseInt($qty.data('max'), 10);
        var step = parseInt($qty.data('step'), 10) || 1;
        var current = parseInt($value.text(), 10) || min;
        var next = current + (parseInt($(this).data('dir'), 10) * step);

        if ($qty.closest('.hdb-cart-drawer__line').hasClass('is-busy') || next < min || (max > 0 && next > max)) {
            return;
        }

        // Affichage immédiat ; l'envoi est regroupé si l'on clique plusieurs fois d'affilée.
        var lastServerQty = $qty.data('server-qty') || current;
        $qty.data('server-qty', lastServerQty);
        $value.text(next);
        refreshQtyButtons($qty, next);

        clearTimeout(qtyTimers[itemKey]);
        qtyTimers[itemKey] = setTimeout(function () {
            sendQuantity($qty, itemKey, lastServerQty);
        }, 400);
    });

    /* ———————————————————————— Ouverture / fermeture ———————————————————————— */

    $(document.body).on('added_to_cart', function () {
        setTitle('added');
        openDrawer();
    });

    // Icône panier : comportement dissocié selon son état.
    // - État normal (dans le header) : on laisse le lien natif faire son
    //   travail, navigation classique vers /panier — rien à faire ici.
    // - État flottant (icône réapparue au scroll, header hors viewport) :
    //   ouvre le tiroir au lieu de naviguer.
    $(document).on('click', 'a.cart-contents', function (e) {
        var $icon = $(this).closest('.icon-cart');

        if (!$icon.hasClass('is-floating')) {
            return;
        }

        e.preventDefault();
        setTitle('default');
        openDrawer();
    });

    $(document).on('click', '#hdb-cart-drawer-close, #hdb-cart-drawer-overlay, #hdb-cart-drawer-continue', function (e) {
        e.preventDefault();
        closeDrawer();
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('body').hasClass('hdb-cart-drawer-open')) {
            closeDrawer();
        }
    });

    /* ————————————————— Icône panier flottante au scroll ————————————————— */

    var $cartIcon = $('.icon-cart');
    var floatThreshold = 200; // px
    // wp_localize_script caste les booléens PHP en chaînes ("" pour false,
    // "1" pour true) : on ne peut donc pas comparer à `=== false`, il faut
    // évaluer la "truthiness" de la chaîne reçue.
    var floatingIconEnabled = !!i18n.floatingIcon; // réglable via hindboutik-features

    if (floatingIconEnabled && $cartIcon.length && !$('body').is('.woocommerce-cart, .woocommerce-checkout')) {
        var ticking = false;

        var updateFloating = function () {
            $cartIcon.toggleClass('is-floating', window.scrollY > floatThreshold);
            ticking = false;
        };

        $(window).on('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(updateFloating);
                ticking = true;
            }
        });

        updateFloating();
    }
});