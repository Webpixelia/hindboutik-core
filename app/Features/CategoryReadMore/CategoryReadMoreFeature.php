<?php
declare(strict_types=1);

namespace HindBoutik\Features\CategoryReadMore;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;

/**
 * "Read more" truncation for product category descriptions.
 *
 * Replaces inline JS from snipets-hdb.php.
 */
class CategoryReadMoreFeature implements FeatureInterface
{
    private int $threshold = 350;

    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('category_read_more')) {
            return;
        }

        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (!is_product_category()) {
            return;
        }

        $assets = AssetManager::getInstance();
        $assets->enqueueJs('read-more');
        $assets->enqueueCss('read-more');

        // Localize threshold.
        wp_add_inline_script('read-more', 'window.HINDBOUTIK_READ_MORE_THRESHOLD = ' . $this->threshold . ';', 'before');
    }
}