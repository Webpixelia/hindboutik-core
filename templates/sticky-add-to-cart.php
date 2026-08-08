<?php
/**
 * Barre d'achat collante — squelette HTML.
 *
 * L'état ("vide" / "plein" / "off") est déterminé côté PHP par
 * StickyAddToCartFeature::renderBar() dès que possible (variante déjà
 * connue via l'URL, un attribut par défaut, ou un seul choix possible).
 * Le JS (sticky-add-to-cart.js) ne fait ensuite que réagir aux changements
 * de sélection de la cliente ; il n'écrase jamais cet état initial.
 *
 * @var array $vars
 */
$productName      = $vars['product_name']       ?? '';
$basePriceHtml    = $vars['price_html']         ?? '';
$displayPriceHtml = $vars['display_price_html'] ?? $basePriceHtml;
$isVariable       = !empty($vars['is_variable']);
$labels           = $vars['labels']             ?? [];
$state            = in_array($vars['state'] ?? 'vide', ['vide', 'plein', 'off'], true) ? $vars['state'] : 'vide';
$variationLabel   = $vars['variation_label']    ?? '';
$outOfStockCount  = (int) ($vars['out_of_stock_count'] ?? 0);

// Nom affiché : "Produit — Variante" dès que la variante est connue.
$displayName = $productName;
if ('' !== $variationLabel) {
    $displayName .= ' — ' . $variationLabel;
}

// Libellé du bouton + contenu de la ligne prix, selon l'état.
switch ($state) {
    case 'plein':
        $btnLabel    = __('Ajouter', 'hindboutik-core');
        $btnDisabled = false;
        $priceLine   = $displayPriceHtml; // HTML (peut contenir des <ins>/<del> WooCommerce).
        break;

    case 'off':
        $btnLabel    = __('Épuisé', 'hindboutik-core');
        $btnDisabled = true;
        $priceLine   = $outOfStockCount > 0
            ? sprintf(
                /* translators: %d = nombre d'autres coloris encore disponibles. */
                esc_html__('Épuisé · %d autres coloris disponibles', 'hindboutik-core'),
                $outOfStockCount
            )
            : esc_html__('Épuisé', 'hindboutik-core');
        break;

    default: // vide
        $btnLabel    = esc_html__('Choisir une option', 'hindboutik-core');
        $btnDisabled = false;
        $priceLine   = $basePriceHtml;
}
?>
<div class="hdb-sticky-atc is-<?php echo esc_attr($state); ?>"
     id="hdb-sticky-atc"
     data-is-variable="<?php echo $isVariable ? '1' : '0'; ?>"
     data-product-name="<?php echo esc_attr($productName); ?>"
     data-price-html="<?php echo esc_attr($basePriceHtml); ?>"
     data-labels="<?php echo esc_attr(wp_json_encode($labels)); ?>">

    <div class="hdb-sticky-atc__info">
        <div class="hdb-sticky-atc__name"><?php echo esc_html($displayName); ?></div>
        <div class="hdb-sticky-atc__price">
            <?php
            // "off" : texte simple (déjà échappé ci-dessus).
            // "vide"/"plein" : HTML WooCommerce (prix barré, devise, etc.).
            echo 'off' === $state ? $priceLine : wp_kses_post($priceLine);
            ?>
        </div>
    </div>

    <button type="button"
            class="hdb-sticky-atc__btn"
            id="hdb-sticky-atc-btn"
            <?php disabled($btnDisabled); ?>>
        <?php echo esc_html($btnLabel); ?>
    </button>
</div>