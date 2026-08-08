<?php
declare(strict_types=1);

namespace HindBoutik\Features\ProductImageSlider;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;

/**
 * Product Image Slider (Slick).
 *
 * Replaces the old "slider-woo-images.php" mu-plugin.
 * Shortcode: [current_product_image_slider]
 */
class ProductImageSliderFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('product_image_slider')) {
            return;
        }

        add_shortcode('current_product_image_slider', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_shortcode('current_product_image_slider');
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (!is_product()) {
            return;
        }

        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', [], '1.8.1');
        wp_register_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], '1.8.1', true);

        $assets = AssetManager::getInstance();
        $assets->enqueueJs('product-slider');
    }

    public function shortcode(): string
    {
        if (!is_product()) {
            return '';
        }

        global $product;
        if (!$product) {
            return '';
        }

        $galleryImages = $product->get_gallery_image_ids();
        if (empty($galleryImages)) {
            return '';
        }

        $mainImageUrl = wp_get_attachment_image_url($product->get_image_id(), '');

        ob_start();
        ?>
        <div class="product-image-slider">
            <?php if ($mainImageUrl): ?>
                <img src="<?php echo esc_url($mainImageUrl); ?>" alt="<?php echo esc_attr($product->get_title()); ?>">
            <?php endif; ?>

            <?php foreach ($galleryImages as $imageId): ?>
                <?php $imageUrl = wp_get_attachment_image_url($imageId, ''); ?>
                <img src="<?php echo esc_url($imageUrl); ?>" alt="<?php echo esc_attr($product->get_title()); ?>">
            <?php endforeach; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}