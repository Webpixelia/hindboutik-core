<?php
/**
 * Tiroir panier — panneau (fragment AJAX rafraîchi à chaque ajout).
 *
 * Ne contient QUE le panneau lui-même : le voile (`.hdb-cart-drawer__overlay`)
 * est rendu séparément par CartDrawerFeature::renderDrawer() et n'est jamais
 * remplacé par le rafraîchissement de fragments, pour ne pas perdre son état.
 *
 * @var array $vars
 */
$items         = $vars['items']         ?? [];
$subtotalHtml  = $vars['subtotal_html'] ?? '';
$shipping      = $vars['shipping']      ?? ['reached' => false, 'percent' => 0, 'remaining_html' => ''];
$suggestions   = $vars['suggestions']   ?? [];
$isEmpty       = empty($items);
?>
<div class="hdb-cart-drawer" id="hdb-cart-drawer">
    <div class="hdb-cart-drawer__head">
        <span class="hdb-cart-drawer__title"
              data-default="<?php echo esc_attr__('Mon panier', 'hindboutik-core'); ?>"
              data-added="<?php echo esc_attr__('✓ Ajouté au panier', 'hindboutik-core'); ?>">
            <?php esc_html_e('Mon panier', 'hindboutik-core'); ?>
        </span>
        <button type="button" class="hdb-cart-drawer__close" id="hdb-cart-drawer-close" aria-label="<?php echo esc_attr__('Fermer', 'hindboutik-core'); ?>">&times;</button>
    </div>

    <div class="hdb-cart-drawer__body">
        <?php if ($isEmpty) : ?>

            <p class="hdb-cart-drawer__empty"><?php esc_html_e('Votre panier est vide pour le moment.', 'hindboutik-core'); ?></p>

        <?php else : ?>

            <?php foreach ($items as $item) : ?>
                <div class="hdb-cart-drawer__line">
                    <div class="hdb-cart-drawer__thumb"><?php echo wp_kses_post($item['image']); ?></div>
                    <div class="hdb-cart-drawer__info">
                        <div class="hdb-cart-drawer__name"><?php echo esc_html($item['name']); ?></div>
                        <div class="hdb-cart-drawer__variation">
                            <?php if (!empty($item['variation_text'])) : ?>
                                <?php echo wp_kses_post($item['variation_text']); ?> ·
                            <?php endif; ?>
                            <?php
                            printf(
                                /* translators: %d = quantité de l'article dans le panier. */
                                esc_html__('Qté %d', 'hindboutik-core'),
                                (int) $item['quantity']
                            );
                            ?>
                        </div>
                        <div class="hdb-cart-drawer__price"><?php echo wp_kses_post($item['price_html']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="hdb-cart-drawer__subtotal">
                <span><?php esc_html_e('Sous-total', 'hindboutik-core'); ?></span>
                <b><?php echo wp_kses_post($subtotalHtml); ?></b>
            </div>

            <div class="hdb-cart-drawer__shipping <?php echo !empty($shipping['reached']) ? 'is-reached' : ''; ?>">
                <p>
                    <?php if (!empty($shipping['reached'])) : ?>
                        <?php esc_html_e('Livraison offerte — c’est acquis !', 'hindboutik-core'); ?>
                    <?php else : ?>
                        <?php
                        printf(
                            /* translators: %s = montant restant, déjà formaté avec la devise. */
                            esc_html__('Plus que %s pour la livraison offerte', 'hindboutik-core'),
                            wp_kses_post($shipping['remaining_html'])
                        );
                        ?>
                    <?php endif; ?>
                </p>
                <div class="hdb-cart-drawer__gauge">
                    <span style="width:<?php echo esc_attr(round((float) $shipping['percent'], 1)); ?>%"></span>
                </div>
            </div>

            <?php if (!empty($suggestions)) : ?>
                <div class="hdb-cart-drawer__suggestions">
                    <div class="hdb-cart-drawer__suggestions-title"><?php esc_html_e('Complétez votre tenue', 'hindboutik-core'); ?></div>

                    <?php foreach ($suggestions as $suggestion) : ?>
                        <div class="hdb-cart-drawer__suggestion">
                            <div class="hdb-cart-drawer__thumb hdb-cart-drawer__thumb--sm"><?php echo wp_kses_post($suggestion['image']); ?></div>
                            <div class="hdb-cart-drawer__suggestion-txt">
                                <b><?php echo esc_html($suggestion['name']); ?></b><br>
                                <?php echo wp_kses_post($suggestion['price_html']); ?>
                            </div>
                            <button type="button" class="hdb-cart-drawer__add" data-product-id="<?php echo esc_attr((string) $suggestion['id']); ?>">
                                <?php esc_html_e('Ajouter', 'hindboutik-core'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <div class="hdb-cart-drawer__foot">
        <a class="hdb-cart-drawer__view" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : '/panier'); ?>">
            <?php esc_html_e('Voir mon panier', 'hindboutik-core'); ?>
        </a>
        <button type="button" class="hdb-cart-drawer__continue" id="hdb-cart-drawer-continue">
            <?php esc_html_e('Continuer mes achats', 'hindboutik-core'); ?>
        </button>
    </div>
</div>