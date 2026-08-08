<?php
declare(strict_types=1);

namespace HindBoutik\Features\SizeGuide;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;
use HindBoutik\Helpers\TemplateLoader;

/**
 * Size Guide Feature.
 *
 * Replaces the old "Custom Size Guide" mu-plugin.
 * Reads size guide data from native options (migrated from ACF).
 * Shortscode: [custom_size_guide]
 */
class SizeGuideFeature implements FeatureInterface
{
    private TemplateLoader $templates;

    public function __construct()
    {
        $this->templates = new TemplateLoader();
    }

    public function register(): void 
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('size_guide')) {
            return;
        }

        add_shortcode('custom_size_guide', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_shortcode('custom_size_guide');
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueueAssets(): void
    {
        if (!is_product()) {
            return;
        }
        $assets = AssetManager::getInstance();
        $assets->enqueueJs('modal-size-guide');
        $assets->enqueueCss('size-guide-modal');
    }

    /**
     * Shortcode handler — renders the size guide modal.
     */
    public function shortcode(): string
    {
        if (!is_product()) {
            return '';
        }

        return $this->templates->render('size-guide-modal', [
            'product_id'      => get_the_ID(),
            'size_guide_data' => \HindBoutik\Acf\MetaApi::getField('categorie_de_produit', 'option') ?: [],
        ]);
    }
}