<?php
declare(strict_types=1);

namespace HindBoutik\Features\CrosssellCarousel;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;
use HindBoutik\Helpers\TemplateLoader;

/**
 * Cross-sell Carousel Feature.
 *
 * Replaces the old "Crosssell Carousel" mu-plugin.
 * Shortscode: [crosssell_carousel]
 */
class CrosssellCarouselFeature implements FeatureInterface
{
    private TemplateLoader $templates;

    public function __construct()
    {
        $this->templates = new TemplateLoader();
    }

    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('crosssell_carousel')) {
            return;
        }

        add_shortcode('crosssell_carousel', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_shortcode('crosssell_carousel');
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (!is_cart()) {
            return;
        }

        $assets = AssetManager::getInstance();

        // External Swiper.
        wp_register_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
        wp_register_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');

        $assets->enqueueCss('crosssell-carousel');
        $assets->enqueueJs('crosssell-carousel');
    }

    public function shortcode(array $atts): string
    {
        $atts = shortcode_atts([
            'limit'   => 5,
            'columns' => 3,
            'orderby' => 'rand',
            'order'   => 'desc',
        ], $atts);

        $crosssellIds = $this->getCrosssellProductIds($atts['limit']);

        if (empty($crosssellIds)) {
            return '';
        }

        return $this->templates->render('crosssell-carousel', [
            'product_ids' => $crosssellIds,
        ]);
    }

    /**
     * Sélectionne des IDs de produits à suggérer en complément du panier
     * (cross-sell des articles présents, puis complété par les plus populaires).
     *
     * Rendue publique pour être réutilisée par CartDrawerFeature (suggestion
     * unique/limitée dans le tiroir panier), en plus du shortcode [crosssell_carousel].
     *
     * @return int[]
     */
    public function getCrosssellProductIds(int $limit): array
    {
        $cartProductIds = [];
        if (WC()->cart && !WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cartItem) {
                $cartProductIds[] = $cartItem['product_id'];
            }
        }

        if (empty($cartProductIds)) {
            return [];
        }

        $crosssellIds = [];
        foreach ($cartProductIds as $productId) {
            $product = wc_get_product($productId);
            if ($product) {
                $crosssellIds = array_merge($crosssellIds, $product->get_cross_sell_ids());
            }
        }

        $crosssellIds = array_diff(array_unique($crosssellIds), $cartProductIds);

        if (count($crosssellIds) < $limit) {
            $popularProducts = wc_get_products([
                'limit'     => $limit - count($crosssellIds),
                'orderby'   => 'popularity',
                'exclude'   => array_merge($cartProductIds, $crosssellIds),
                'status'    => 'publish',
                'stock_status' => 'instock',
            ]);

            foreach ($popularProducts as $product) {
                $crosssellIds[] = $product->get_id();
            }
        }

        return array_slice($crosssellIds, 0, $limit);
    }
}