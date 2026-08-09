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

    if ($cartIcon.length && !$('body').is('.woocommerce-cart, .woocommerce-checkout')) {
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