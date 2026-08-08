<?php
declare(strict_types=1);

namespace HindBoutik\Features\CartDrawer;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;
use HindBoutik\Core\Plugin;
use HindBoutik\Features\CrosssellCarousel\CrosssellCarouselFeature;
use HindBoutik\Features\FreeShippingProgress\FreeShippingProgressFeature;
use HindBoutik\Helpers\TemplateLoader;

/**
 * Tiroir panier (cart drawer).
 *
 * Remplace la redirection vers la page panier par un panneau qui glisse
 * depuis la droite, à l'ajout d'un produit ou au clic sur l'icône panier
 * du header (celle-ci devient flottante au scroll via CSS/JS — pas de
 * duplication de markup, cf. cart-drawer.css / cart-drawer.js).
 *
 * Le site n'ayant pas d'ajout au panier en AJAX à ce jour, le formulaire
 * `form.cart` de la fiche produit est lui-même ajaxifié côté JS via
 * l'endpoint natif `wc-ajax=add_to_cart` (le même que celui utilisé par
 * les boutons AJAX de la boucle produits).
 *
 * Contenu du tiroir 100% réutilisé, rien de dupliqué :
 *  - jauge de livraison → même seuil que FreeShippingProgressFeature ;
 *  - suggestion(s) → même moteur que CrosssellCarouselFeature, limité à
 *    self::MAX_SUGGESTIONS.
 */
class CartDrawerFeature implements FeatureInterface
{
    private const MAX_SUGGESTIONS = 2;

    private TemplateLoader $templates;

    public function __construct()
    {
        $this->templates = new TemplateLoader();
    }

    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!Plugin::isFeatureEnabled('cart_drawer')) {
            return;
        }

        add_action('wp_footer', [$this, 'renderDrawer']);
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'addDrawerFragment']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_action('wp_footer', [$this, 'renderDrawer']);
        remove_filter('woocommerce_add_to_cart_fragments', [$this, 'addDrawerFragment']);
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $assets = AssetManager::getInstance();
        $assets->enqueueCss('cart-drawer');
        $assets->enqueueJs('cart-drawer');

        wp_localize_script('cart-drawer', 'hdbCartDrawer', [
            'addText'    => __('Ajouter', 'hindboutik-core'),
            'addingText' => __('Ajout…', 'hindboutik-core'),
        ]);
    }

    /**
     * Rendu initial du tiroir (une seule fois, dans le footer, présent sur
     * tout le site puisqu'accessible depuis l'icône panier du header).
     */
    public function renderDrawer(): void
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        echo '<div class="hdb-cart-drawer__overlay" id="hdb-cart-drawer-overlay"></div>';
        echo $this->templates->render('cart-drawer', $this->buildDrawerVars());
    }

    /**
     * Fournit le HTML à jour du panneau (`.hdb-cart-drawer`) dans la même
     * réponse AJAX que le comptage de l'icône panier (cf. MenuIconsFeature),
     * pour add_to_cart comme pour tout rafraîchissement de fragments natif.
     */
    public function addDrawerFragment(array $fragments): array
    {
        $fragments['.hdb-cart-drawer'] = $this->templates->render('cart-drawer', $this->buildDrawerVars());

        return $fragments;
    }

    private function buildDrawerVars(): array
    {
        $cart     = class_exists('WooCommerce') ? WC()->cart : null;
        $items    = [];
        $subtotal = 0.0;

        if ($cart && !$cart->is_empty()) {
            foreach ($cart->get_cart() as $cartItemKey => $cartItem) {
                /** @var \WC_Product|false $product */
                $product = $cartItem['data'] ?? false;

                if (!$product) {
                    continue;
                }

                $items[] = [
                    'key'            => $cartItemKey,
                    'name'           => $product->get_name(),
                    'image'          => $product->get_image('thumbnail'),
                    'variation_text' => wc_get_formatted_cart_item_data($cartItem, true),
                    'quantity'       => (int) $cartItem['quantity'],
                    'price_html'     => wc_price($cartItem['line_total'] + $cartItem['line_tax']),
                ];
            }

            $subtotal = (float) $cart->get_subtotal() + (float) $cart->get_subtotal_tax();
        }

        return [
            'items'         => $items,
            'subtotal_html' => wc_price($subtotal),
            'shipping'      => $this->buildShippingProgress($subtotal),
            'suggestions'   => $this->buildSuggestions(),
        ];
    }

    private function buildShippingProgress(float $subtotal): array
    {
        $threshold = FreeShippingProgressFeature::THRESHOLD_FREE_SHIPPING;
        $remaining = max(0, $threshold - $subtotal);
        $percent   = min(100, max(0, ($subtotal / $threshold) * 100));

        return [
            'reached'        => $subtotal >= $threshold,
            'percent'        => $percent,
            'remaining_html' => wc_price($remaining),
        ];
    }

    /**
     * @return array<int, array{id:int, name:string, image:string, price_html:string}>
     */
    private function buildSuggestions(): array
    {
        $crosssell = Plugin::instance()->getFeature(CrosssellCarouselFeature::class);

        if (!$crosssell instanceof CrosssellCarouselFeature) {
            // La feature crosssell_carousel est désactivée : pas de suggestion,
            // le reste du tiroir (articles, jauge) fonctionne quand même.
            return [];
        }

        $suggestions = [];

        foreach ($crosssell->getCrosssellProductIds(self::MAX_SUGGESTIONS) as $productId) {
            $product = wc_get_product($productId);

            if (!$product || !$product->is_visible()) {
                continue;
            }

            $suggestions[] = [
                'id'         => $productId,
                'name'       => $product->get_name(),
                'image'      => $product->get_image('thumbnail'),
                'price_html' => $product->get_price_html(),
            ];
        }

        return $suggestions;
    }
}