<?php
declare(strict_types=1);

namespace HindBoutik\Features\SaleBadge;

use HindBoutik\Core\FeatureInterface;
use WC_Product;

/**
 * Custom sale flash badge text.
 *
 * Replaces the inline snippet from snipets-hdb.php.
 */
class SaleBadgeFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('sale_badge')) {
            return;
        }

        add_filter('woocommerce_sale_flash', [$this, 'renderSaleBadge'], 10, 3);
    }

    public function unregister(): void
    {
        remove_filter('woocommerce_sale_flash', [$this, 'renderSaleBadge'], 10);
    }

    public function renderSaleBadge(string $html, $post, WC_Product $product): string
    {
        $stock = $product->get_stock_quantity();

        if ($stock !== null && (int) $stock === 1) {
            return '<span class="onsale">' . esc_html__('Pièce unique', 'hindboutik-core') . '</span>';
        }

        return '<span class="onsale">' . esc_html__('Dernières pièces disponibles', 'hindboutik-core') . '</span>';
    }
}