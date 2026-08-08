/**
 * HindBoutik Core — Barre d'achat collante (mobile)
 *
 * Reprend le bouton d'ajout au panier dans une barre fixe en bas d'écran
 * dès que le bouton principal sort du viewport, et reflète l'état de la
 * sélection de variation (aucune / valide / rupture) via les évènements
 * natifs du formulaire WooCommerce ("found_variation" / "reset_data").
 */
jQuery(document).ready(function ($) {
    var $bar = $('#hdb-sticky-atc');

    if (!$bar.length) {
        return;
    }

    var $btn      = $('#hdb-sticky-atc-btn');
    var $name     = $bar.find('.hdb-sticky-atc__name');
    var $price    = $bar.find('.hdb-sticky-atc__price');
    var $mainForm = $('.variations_form').first();
    var $mainBtn  = $('form.cart').find('.single_add_to_cart_button').first();

    var isVariable = $bar.attr('data-is-variable') === '1';
    var baseName   = $bar.attr('data-product-name') || '';
    var basePrice  = $bar.attr('data-price-html') || '';
    var labels     = {};

    var i18n = window.hdbStickyAtc || {};
    var chooseText       = i18n.chooseText       || 'Choisir';
    var addToCartText    = i18n.addToCartText    || 'Ajouter';
    var outOfStockText   = i18n.outOfStockText   || 'Épuisé';
    var outOfStockSuffix = i18n.outOfStockSuffix || '· %d autres coloris disponibles';

    try {
        labels = JSON.parse($bar.attr('data-labels') || '{}');
    } catch (e) {
        labels = {};
    }

    /* ————————————————— Helpers de lecture du formulaire ————————————————— */

    function getAttributeSelects() {
        return $mainForm.find('select[name^="attribute_"]');
    }

    function getCurrentAttributes() {
        var attrs = {};
        getAttributeSelects().each(function () {
            attrs[$(this).attr('name')] = $(this).val();
        });
        return attrs;
    }

    function getPrimaryAttributeName() {
        var $selects  = getAttributeSelects();
        var $preferred = $selects.filter('[name="attribute_pa_couleur"], [name="attribute_pa_color"]');

        if ($preferred.length) {
            return $preferred.first().attr('name');
        }

        return $selects.length ? $selects.first().attr('name') : null;
    }

    function getMissingAttributeLabel() {
        var $missing = getAttributeSelects().filter(function () {
            return !$(this).val();
        });

        if (!$missing.length) {
            return null;
        }

        var taxonomy = $missing.first().attr('name').replace('attribute_', '');
        var label    = labels[taxonomy] || labels['default'] || 'une option';

        return chooseText + ' ' + label;
    }

    function getSelectedAttributesLabel() {
        var attrName = getPrimaryAttributeName();

        if (!attrName) {
            return '';
        }

        var $select = getAttributeSelects().filter('[name="' + attrName + '"]');
        var val     = $select.val();

        if (!val) {
            return '';
        }

        var $opt = $select.find('option[value="' + val.replace(/"/g, '\\"') + '"]');

        return $opt.length ? $.trim($opt.text()) : val;
    }

    function getVariationsData() {
        var raw = $mainForm.attr('data-product_variations');

        if (!raw || raw === 'false') {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    /**
     * Nombre de valeurs encore disponibles pour l'attribut principal
     * (typiquement le coloris), en tenant compte des autres attributs
     * déjà sélectionnés (ex : une taille commune).
     */
    function countAvailableAlternatives(currentAttrs) {
        var variations = getVariationsData();
        var attrName   = getPrimaryAttributeName();

        if (!variations || !attrName) {
            return 0;
        }

        var currentValue = currentAttrs[attrName];
        var seen = {};

        variations.forEach(function (variation) {
            if (!variation.is_in_stock) {
                return;
            }

            var value = variation.attributes ? variation.attributes[attrName] : null;

            if (!value || value === currentValue) {
                return;
            }

            var matchesOthers = true;

            Object.keys(currentAttrs).forEach(function (key) {
                if (key === attrName || !currentAttrs[key]) {
                    return;
                }
                var otherValue = variation.attributes[key];
                if (otherValue && otherValue !== currentAttrs[key]) {
                    matchesOthers = false;
                }
            });

            if (matchesOthers) {
                seen[value] = true;
            }
        });

        return Object.keys(seen).length;
    }

    /* ————————————————————————— États de la barre ————————————————————————— */

    function setVide() {
        $bar.removeClass('is-plein is-off').addClass('is-vide');
        $btn.prop('disabled', false).text(getMissingAttributeLabel() || (chooseText + ' une option'));
        $name.text(baseName);
        $price.html(basePrice);
    }

    function setPlein(variation) {
        $bar.removeClass('is-vide is-off').addClass('is-plein');
        $btn.prop('disabled', false).text(addToCartText);

        var attrsLabel = getSelectedAttributesLabel();
        $name.text(baseName + (attrsLabel ? ' — ' + attrsLabel : ''));
        $price.html(variation && variation.price_html ? variation.price_html : basePrice);
    }

    function setOff(currentAttrs) {
        $bar.removeClass('is-vide is-plein').addClass('is-off');
        $btn.prop('disabled', true).text(outOfStockText);

        var attrsLabel = getSelectedAttributesLabel();
        $name.text(baseName + (attrsLabel ? ' — ' + attrsLabel : ''));

        var remaining = countAvailableAlternatives(currentAttrs || getCurrentAttributes());
        var text      = outOfStockText;

        if (remaining > 0) {
            text += ' ' + outOfStockSuffix.replace('%d', remaining);
        }

        $price.text(text);
    }

    /* ————————————————————————————— Init ————————————————————————————— */

    // L'état initial (vide / plein / off, nom de variante, compteur "Épuisé ...")
    // est déjà déterminé et rendu côté PHP (StickyAddToCartFeature::renderBar()).
    // On ne le réécrit donc PAS ici : le JS se contente d'écouter les
    // changements de sélection à partir de maintenant, pour ne pas provoquer
    // de flash vers "Choisir une option" ni perdre l'info calculée côté serveur.
    if (isVariable && $mainForm.length) {
        $mainForm.on('found_variation', function (event, variation) {
            if (variation.is_in_stock) {
                setPlein(variation);
            } else {
                setOff(getCurrentAttributes());
            }
        });

        $mainForm.on('reset_data hide_variation', function () {
            setVide();
        });
    }

    /* ——————————————————— Apparition au scroll (mobile) ——————————————————— */

    if ('IntersectionObserver' in window && $mainBtn.length) {
        var observer = new IntersectionObserver(function (entries) {
            var entry = entries[0];
            var shouldShow = !entry.isIntersecting && entry.boundingClientRect.top < 0;
            $bar.toggleClass('is-visible', shouldShow);
        }, { threshold: 0 });

        observer.observe($mainBtn.get(0));
    } else {
        // Environnement sans IntersectionObserver : on affiche par défaut.
        $bar.addClass('is-visible');
    }

    /* ——————————————————————————— Interactions ——————————————————————————— */

    $btn.on('click', function () {
        if ($bar.hasClass('is-off')) {
            return;
        }

        if ($bar.hasClass('is-vide')) {
            var $target = $mainForm.find('.variations').first();

            if (!$target.length) {
                $target = $mainForm;
            }

            if ($target.length) {
                $target.get(0).scrollIntoView({ block: 'center', behavior: 'smooth' });
                $target.addClass('hdb-sticky-atc-flash');
                setTimeout(function () {
                    $target.removeClass('hdb-sticky-atc-flash');
                }, 900);
            }

            return;
        }

        // État "plein" : on relaie le clic vers le vrai bouton d'ajout au panier
        // (formulaire AJAX ou classique, la logique WooCommerce reste inchangée).
        $mainBtn.trigger('click');
    });
});