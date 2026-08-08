<?php
declare(strict_types=1);

namespace HindBoutik\Features\ColorSwatches;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;

/**
 * Affiche des pastilles de couleur pour les variations de couleur
 * dans la boucle produit.
 *
 * Replaces the inline snippet from snipets-hdb.php.
 */
class ColorSwatchesFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('color_swatches')) {
            return;
        }

        add_action('woocommerce_before_shop_loop_item_title', [$this, 'renderColorSwatches'], 15);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_action('woocommerce_before_shop_loop_item_title', [$this, 'renderColorSwatches'], 15);
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        $assets = AssetManager::getInstance();
        $assets->enqueueCss('color-swatches');
    }

    public function renderColorSwatches(): void
    {
        global $product;

        if (!$product instanceof \WC_Product_Variable) {
            return;
        }

        $terms = wc_get_product_terms($product->get_id(), 'pa_couleur', ['fields' => 'all']);

        if (empty($terms) || is_wp_error($terms)) {
            return;
        }

        echo '<p class="label_colors_list">' . esc_html__('Coloris disponibles', 'hindboutik-core') . '</p>';
        echo '<ul class="list_variation_colors hdb">';

        foreach ($terms as $term) {
            $hexColor = get_term_meta($term->term_id, 'product_attribute_color', true);

            if (empty($hexColor)) {
                continue;
            }

            printf(
                '<li class="variation-color" style="background-color:%s" title="%s"></li>',
                esc_attr($hexColor),
                esc_attr($term->name)
            );
        }

        echo '</ul>';
    }
}