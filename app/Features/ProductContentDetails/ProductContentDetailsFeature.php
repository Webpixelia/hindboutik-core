<?php
declare(strict_types=1);

namespace HindBoutik\Features\ProductContentDetails;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;
use HindBoutik\Helpers\TemplateLoader;

/**
 * Product Content Details Feature.
 *
 * Replaces the old "Product content details" mu-plugin.
 * Renders the product description section.
 * Shortscode: [product_content_details]
 */
class ProductContentDetailsFeature implements FeatureInterface
{
    private TemplateLoader $templates;

    public function __construct()
    {
        $this->templates = new TemplateLoader();
    }

    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('product_content_details')) {
            return;
        }

        add_shortcode('product_content_details', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_shortcode('product_content_details');
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        $assets = AssetManager::getInstance();
        $assets->enqueueJs('toggle-menu');
    }

    public function shortcode(): string
    {
        if (!is_product()) {
            return '';
        }

        $productId = get_the_ID();
        $description = \HindBoutik\Acf\MetaApi::getField('description', $productId);

        return $this->templates->render('product-details-content', [
            'product_id'      => $productId,
            'description'     => $description,
            'description_label' => \HindBoutik\Acf\MetaApi::getFieldLabel('description'),
        ]);
    }
}