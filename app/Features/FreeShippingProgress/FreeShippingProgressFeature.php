<?php
declare(strict_types=1);

namespace HindBoutik\Features\FreeShippingProgress;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;

/**
 * Free shipping progress bar shortcode.
 *
 * Replaces the inline shortcode from snipets-hdb.php.
 * Shortcode: [ts_progress_bar_free_shipping]
 */
class FreeShippingProgressFeature implements FeatureInterface
{
    private float $thresholdMarketing = 139.0;
    private float $thresholdFreeShipping = 159.0;

    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('free_shipping_progress')) {
            return;
        }

        add_shortcode('ts_progress_bar_free_shipping', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_shortcode('ts_progress_bar_free_shipping');
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        $assets = AssetManager::getInstance();
        $assets->enqueueCss('progress-bar');
    }

    public function shortcode(): string
    {
        if (!class_exists('WooCommerce') || !WC()->cart) {
            return '';
        }

        $cartSubtotalExclTax = WC()->cart->get_subtotal();
        $totalTax            = WC()->cart->get_subtotal_tax();
        $cartTotal           = $cartSubtotalExclTax + $totalTax;

        $progressPercentage = min(100, max(0, ($cartTotal / $this->thresholdFreeShipping) * 100));
        $remainingAmount    = max(0, $this->thresholdFreeShipping - $cartTotal);

        $barClass = 'progress-bar';
        if ($cartTotal >= $this->thresholdFreeShipping) {
            $barClass .= ' progress-bar-success';
        }

        ob_start();

        echo '<div class="progress-bar-container">';
        echo '<span>0€</span>';
        echo '<div class="progress">';
        echo '<div class="' . esc_attr($barClass) . '" role="progressbar" style="width:' . esc_attr($progressPercentage) . '%" aria-valuenow="' . esc_attr($progressPercentage) . '" aria-valuemin="0" aria-valuemax="100"></div>';
        echo '</div>';
        echo '<span>' . esc_html($this->thresholdFreeShipping) . '€</span>';
        echo '</div>';

        if ($cartTotal < $this->thresholdMarketing) {
            echo '<p class="progress-bar-text marketing-message">' .
                 esc_html__("Ajoutez un article pour compléter votre tenue et profitez d'avantages exclusifs ✨", 'hindboutik-core') .
                 '</p>';
        } elseif ($cartTotal < $this->thresholdFreeShipping) {
            echo '<p class="progress-bar-text">' .
                 sprintf(esc_html__("Plus que %s pour bénéficier de la livraison offerte !", 'hindboutik-core'), wc_price($remainingAmount)) .
                 '</p>';
        } else {
            echo '<p class="progress-bar-text success-message">' .
                 esc_html__("Bravo, la livraison est offerte !", 'hindboutik-core') .
                 '</p>';
        }

        echo '<div>';
        echo '<p><strong>' . esc_html__('🇫🇷 Expédié depuis Toulouse – Livraison rapide', 'hindboutik-core') . '</strong></p>';
        echo do_shortcode('[trustindex data-widget-id="a98cb6865ac8909e3276f5f6c56"]');
        echo '</div>';

        return (string) ob_get_clean();
    }
}