<?php
declare(strict_types=1);

namespace HindBoutik\Features\AfterMessage;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;

/**
 * "After message" shortcode — returns & exchanges + customer service.
 *
 * Replaces the inline shortcode from snipets-hdb.php.
 * Shortcode: [ts_message_after]
 */
class AfterMessageFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('after_message')) {
            return;
        }

        add_shortcode('ts_message_after', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_shortcode('ts_message_after');
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        $assets = AssetManager::getInstance();
        $assets->enqueueCss('after-message');
    }

    public function shortcode(): string
    {
        $output  = '<div class="ts-message-after">';
        $output .= '<p><strong>✔️ ' . esc_html__('Retours & échanges possibles sous 14 jours', 'hindboutik-core') . '</strong></p>';
        $output .= '<p><strong>✔️ ' . esc_html__('Service client réactif', 'hindboutik-core') . '</strong></p>';
        $output .= '</div>';

        return $output;
    }
}