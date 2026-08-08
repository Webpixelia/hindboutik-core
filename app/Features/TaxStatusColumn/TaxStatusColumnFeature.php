<?php
declare(strict_types=1);

namespace HindBoutik\Features\TaxStatusColumn;

use HindBoutik\Core\FeatureInterface;

/**
 * Adds a "Tax status" column to the WooCommerce product admin list.
 *
 * Replaces the inline snippet from snipets-hdb.php.
 */
class TaxStatusColumnFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('tax_status_column')) {
            return;
        }

        add_filter('manage_edit-product_columns', [$this, 'addColumn']);
        add_action('manage_product_posts_custom_column', [$this, 'renderColumn'], 10, 2);
    }

    public function unregister(): void
    {
        remove_filter('manage_edit-product_columns', [$this, 'addColumn']);
        remove_action('manage_product_posts_custom_column', [$this, 'renderColumn'], 10);
    }

    public function addColumn(array $columns): array
    {
        $newColumns = [];
        foreach ($columns as $key => $column) {
            $newColumns[$key] = $column;
            if ($key === 'is_in_stock') {
                $newColumns['tax_status'] = _x('État de la TVA', 'Column name', 'woocommerce');
            }
        }
        return $newColumns;
    }

    public function renderColumn(string $column, int $postId): void
    {
        if ($column !== 'tax_status') {
            return;
        }

        $product = wc_get_product($postId);

        if (!is_a($product, 'WC_Product')) {
            return;
        }

        $labels = [
            'taxable'  => __('Taxable', 'woocommerce'),
            'shipping' => __('Shipping only', 'woocommerce'),
            'none'     => _x('None', 'Tax status', 'woocommerce'),
        ];

        echo $labels[$product->get_tax_status()] ?? '';
    }
}