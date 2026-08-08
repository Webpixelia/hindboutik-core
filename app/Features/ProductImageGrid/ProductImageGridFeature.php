<?php
declare(strict_types=1);

namespace HindBoutik\Features\ProductImageGrid;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;
use HindBoutik\Helpers\TemplateLoader;

class ProductImageGridFeature implements FeatureInterface
{
    private TemplateLoader $templates;

    public function __construct()
    {
        $this->templates = new TemplateLoader();
    }

    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('product_image_grid')) {
            return;
        }

        add_shortcode('custom_product_image_grid', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_shortcode('custom_product_image_grid');
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (!is_product()) {
            return;
        }
        $assets = AssetManager::getInstance();
        $assets->enqueueCss('product-image-grid');
    }

    public function shortcode(): string
    {
        if (!class_exists('WooCommerce') || !is_product()) {
            return '';
        }

        global $product;
        if (!$product) {
            return '';
        }

        $productId = $product->get_id();
        $mainImageId = get_post_thumbnail_id($productId);

        $mainImage = $mainImageId
            ? wp_get_attachment_image_src($mainImageId, 'full')
            : false;

        $mainImageUrl = $mainImage ? $mainImage[0] : '';
        $galleryImageIds = $product->get_gallery_image_ids();

        return $this->templates->render('product-image-grid', [
            'main_image_url'    => $mainImageUrl,
            'gallery_image_ids' => $galleryImageIds,
        ]);
    }
}