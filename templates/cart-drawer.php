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
                        <?php if (!empty($item['variation_text'])) : ?>
                            <div class="hdb-cart-drawer__variation"><?php echo wp_kses_post($item['variation_text']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['qty_editable'])) : ?>
                            <div class="hdb-cart-drawer__qty"
                                 data-cart-item-key="<?php echo esc_attr((string) $item['key']); ?>"
                                 data-min="<?php echo esc_attr((string) $item['qty_min']); ?>"
                                 data-max="<?php echo esc_attr((string) $item['qty_max']); ?>"
                                 data-step="<?php echo esc_attr((string) $item['qty_step']); ?>">
                                <button type="button" class="hdb-cart-drawer__qty-btn" data-dir="-1"
                                        aria-label="<?php echo esc_attr__('Diminuer la quantité', 'hindboutik-core'); ?>"
                                        <?php disabled((int) $item['quantity'] <= (int) $item['qty_min']); ?>>&minus;</button>
                                <span class="hdb-cart-drawer__qty-value" aria-live="polite"><?php echo (int) $item['quantity']; ?></span>
                                <button type="button" class="hdb-cart-drawer__qty-btn" data-dir="1"
                                        aria-label="<?php echo esc_attr__('Augmenter la quantité', 'hindboutik-core'); ?>"
                                        <?php disabled((int) $item['qty_max'] > 0 && (int) $item['quantity'] >= (int) $item['qty_max']); ?>>+</button>
                            </div>
                        <?php else : ?>
                            <div class="hdb-cart-drawer__variation">
                                <?php
                                printf(
                                    /* translators: %d = quantité de l'article dans le panier. */
                                    esc_html__('Qté %d', 'hindboutik-core'),
                                    (int) $item['quantity']
                                );
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="hdb-cart-drawer__price"><?php echo wp_kses_post($item['price_html']); ?></div>
                    </div>
                    <button type="button"
                            class="hdb-cart-drawer__remove"
                            data-cart-item-key="<?php echo esc_attr((string) $item['key']); ?>"
                            aria-label="<?php echo esc_attr(sprintf(
                                /* translators: %s = nom de l'article. */
                                __('Retirer %s du panier', 'hindboutik-core'),
                                $item['name']
                            )); ?>">&times;</button>
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
                            <?php if (!empty($suggestion['ajax_add'])) : ?>
                                <button type="button" class="hdb-cart-drawer__add" data-product-id="<?php echo esc_attr((string) $suggestion['id']); ?>">
                                    <?php esc_html_e('Ajouter', 'hindboutik-core'); ?>
                                </button>
                            <?php else : ?>
                                <a class="hdb-cart-drawer__add" href="<?php echo esc_url($suggestion['url']); ?>">
                                    <?php esc_html_e('Choisir', 'hindboutik-core'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <div class="hdb-cart-drawer__foot">
        <a class="hdb-cart-drawer__view" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : '/panier'); ?>">
            <?php esc_html_e('Commander', 'hindboutik-core'); ?>
        </a>
        <button type="button" class="hdb-cart-drawer__continue" id="hdb-cart-drawer-continue">
            <?php esc_html_e('Continuer mes achats', 'hindboutik-core'); ?>
        </button>
    </div>
</div>