<?php
declare(strict_types=1);

namespace HindBoutik\Features\PlusMinusQuantity;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;

/**
 * Plus / Minus quantity buttons for WooCommerce.
 *
 * Replaces the old "plus-minus-qty-woo.php" mu-plugin.
 */
class PlusMinusQuantityFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('plus_minus_quantity')) {
            return;
        }

        add_action('woocommerce_after_quantity_input_field', [$this, 'renderPlusButton']);
        add_action('woocommerce_before_quantity_input_field', [$this, 'renderMinusButton']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_action('woocommerce_after_quantity_input_field', [$this, 'renderPlusButton']);
        remove_action('woocommerce_before_quantity_input_field', [$this, 'renderMinusButton']);
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (!is_product() && !is_cart()) {
            return;
        }
        $assets = AssetManager::getInstance();
        $assets->enqueueJs('plus-minus');
    }

    public function renderPlusButton(): void
    {
        echo '<button type="button" class="plus">+</button>';
    }

    public function renderMinusButton(): void
    {
        echo '<button type="button" class="minus">-</button>';
    }
}