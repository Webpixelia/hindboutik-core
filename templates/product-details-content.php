<?php
/**
 * @var string $title
 * @var string $content
 */
?>
<div id="product-details-content" class="product-details-content">
    <?php if (!empty($description)) : ?>
        <div id="description" class="product-details-item title_menu">
            <p class="wrapper__section-title"><?php echo esc_html($vars['description_label']); ?></p>
            <div class="wrapper__section-content">
                <?php echo wp_kses_post($vars['description']); ?>
            </div>
        </div>
    <?php endif; ?>
    <div id="shipping-returns" class="product-details-item title_menu">
        <p class="wrapper__section-title">Expédition & Retours</p>
        <div class="wrapper__section-content">
            <p>Nous nous efforçons d’expédier les commandes sous 1 jour ouvré. Veuillez noter que lors des soldes et des lancements de nouvelles collections, cela peut être plus long en raison du nombre élevé de commandes.</p>
            <p>Les articles que vous avez commandés chez HindBoutik peuvent être retournés dans les 14 jours. Les 14 jours courent à compter de la date de réception.</p>
        </div>
    </div>
</div>