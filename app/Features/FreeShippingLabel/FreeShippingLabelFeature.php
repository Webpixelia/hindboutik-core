<?php
declare(strict_types=1);

namespace HindBoutik\Features\FreeShippingLabel;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;

/**
 * Replace "Gratuit" with "OFFERT" on the checkout page
 * for relay point shipping methods.
 *
 * Replaces inline JS from snipets-hdb.php.
 */
class FreeShippingLabelFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('free_shipping_label')) {
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
        if (is_admin() || !is_checkout()) {
            return;
        }

        $assets = AssetManager::getInstance();
        $assets->enqueueJs('free-shipping-label');
    }
}