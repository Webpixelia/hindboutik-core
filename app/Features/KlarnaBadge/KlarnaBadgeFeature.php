<?php
declare(strict_types=1);

namespace HindBoutik\Features\KlarnaBadge;

use HindBoutik\Core\FeatureInterface;

/**
 * Klarna payment badge on product pages.
 *
 * Replaces the old "klarna-custom.php" mu-plugin.
 * Shortcode: [klarna_badge]
 */
class KlarnaBadgeFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('klarna_badge')) {
            return;
        }

        add_shortcode('klarna_badge', [$this, 'shortcode']);
    }

    public function unregister(): void
    {
        remove_shortcode('klarna_badge');
    }

    public function shortcode(): string
    {
        global $product;
        if (!$product) {
            return '';
        }

        $price = $this->getDisplayedPrice($product) * 100;

        if (!is_numeric($price)) {
            return '';
        }

        $output = sprintf(
            '<klarna-placement data-key="credit-promotion-badge" data-locale="fr-FR" data-purchase-amount="%d"></klarna-placement>',
            (int) $price
        );

        // Update price for variable products.
        if ($product->is_type('variable')) {
            $output .= '<script>
            jQuery(document).ready(function($) {
                $("form.variations_form").on("found_variation", function(event, variation) {
                    if (variation.display_price) {
                        const amount = Math.round(variation.display_price * 100);
                        $("klarna-placement").attr("data-purchase-amount", amount);
                        if (window.KlarnaOnsiteService) {
                            window.KlarnaOnsiteService.push({ eventName: "refresh-placements" });
                        }
                    }
                });
            });
            </script>';
        }

        return $output;
    }

    private function getDisplayedPrice($product): float
    {
        if (wc_tax_enabled() && 'incl' === get_option('woocommerce_tax_display_shop')) {
            if ($product->is_type('variable')) {
                return (float) wc_get_price_including_tax($product, ['price' => $product->get_variation_price('min')]);
            }
            return (float) wc_get_price_including_tax($product);
        }

        if ($product->is_type('variable')) {
            return (float) wc_get_price_excluding_tax($product, ['price' => $product->get_variation_price('min')]);
        }
        return (float) wc_get_price_excluding_tax($product);
    }
}