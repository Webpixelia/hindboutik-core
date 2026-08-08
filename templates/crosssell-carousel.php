<?php
/**
 * @var int[] $product_ids
 */
?>

<div class="crosssell-carousel-container alignfull">
    <h2 class="wp-block-heading" style="font-size: 1.7em;">
        <strong><?php esc_html_e('Les clients adorent aussi ces articles', 'hindboutik-core'); ?></strong>
    </h2>

    <div class="swiper crosssell-swiper">
        <div class="swiper-wrapper">
            <?php foreach ($product_ids as $product_id) :

                $product = wc_get_product($product_id);

                if (!$product || !$product->is_visible()) {
                    continue;
                }
            ?>

                <div class="swiper-slide">
                    <div class="crosssell-product">
                        <div class="product-image">
                            <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                            </a>
                        </div>

                        <div class="product-info">
                            <h4 class="product-title">
                                <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                    <?php echo esc_html($product->get_name()); ?>
                                </a>
                            </h4>

                            <div class="product-price">
                                <?php echo $product->get_price_html(); ?>
                            </div>

                            <div class="wp-block-button wc-block-components-product-button wp-block-cart-cross-sells-product__product-add-to-cart">
                                <?php if ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()) : ?>

                                    <a href="<?php echo esc_url($product->add_to_cart_url()); ?>"
                                       class="add_to_cart_button wc-block-components-product-button__button add-to-cart-btn"
                                       data-product_id="<?php echo esc_attr($product->get_id()); ?>">
                                        <?php esc_html_e('Ajouter au panier', 'hindboutik-core'); ?>
                                    </a>

                                <?php else : ?>

                                    <a href="<?php echo esc_url($product->get_permalink()); ?>"
                                       class="add_to_cart_button wc-block-components-product-button__button">
                                        <?php esc_html_e('Choix des options', 'hindboutik-core'); ?>
                                    </a>

                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>

        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>
</div>