<?php
declare(strict_types=1);

namespace HindBoutik\Features\AccountScripts;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;

/**
 * Enqueues account-page JS (toggle addresses, Brevo form scripts).
 *
 * Replaces inline JS from snipets-hdb.php.
 */
class AccountScriptsFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('account_scripts')) {
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
        if (!is_account_page()) {
            return;
        }

        $assets = AssetManager::getInstance();
        $assets->enqueueJs('account-page');
    }
}