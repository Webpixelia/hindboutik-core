<?php
declare(strict_types=1);

namespace HindBoutik\Features\CartDrawer;

use HindBoutik\Admin\FeatureToggleSettings;
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
 * L'ajout au panier depuis la fiche produit (`form.cart`) reste natif
 * (soumission classique, page rechargée) — le tiroir est alimenté par les
 * boutons AJAX déjà natifs de la boucle produits ainsi que par le bouton
 * "Ajouter" des suggestions cross-sell à l'intérieur du tiroir lui-même.
 * Une version ajaxifiée du formulaire fiche produit existe en commentaire
 * dans cart-drawer.js, désactivée pour écarter toute interaction avec le
 * cache LiteSpeed/Cloudflare (cf. historique Git).
 *
 * L'icône panier du header conserve son comportement natif (navigation
 * vers /panier) ; seule sa version flottante (apparue au scroll, header
 * hors viewport) ouvre le tiroir au clic.
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
        add_action('wc_ajax_hdb_remove_cart_item', [$this, 'ajaxRemoveCartItem']);
        add_action('wc_ajax_hdb_update_cart_item_qty', [$this, 'ajaxUpdateCartItemQty']);
    }

    public function unregister(): void
    {
        remove_action('wp_footer', [$this, 'renderDrawer']);
        remove_filter('woocommerce_add_to_cart_fragments', [$this, 'addDrawerFragment']);
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        remove_action('wc_ajax_hdb_remove_cart_item', [$this, 'ajaxRemoveCartItem']);
        remove_action('wc_ajax_hdb_update_cart_item_qty', [$this, 'ajaxUpdateCartItemQty']);
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
            'addText'      => __('Ajouter', 'hindboutik-core'),
            'addingText'   => __('Ajout…', 'hindboutik-core'),
            'floatingIcon' => FeatureToggleSettings::isSubOptionEnabled('cart_drawer_floating_icon'),
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

    /**
     * Endpoint AJAX (`?wc-ajax=hdb_remove_cart_item`) : retire une ligne du
     * panier et renvoie les fragments à jour (tiroir + compteur du header),
     * au même format que la réponse native de `add_to_cart`.
     */
    public function ajaxRemoveCartItem(): void
    {
        $cart    = class_exists('WooCommerce') ? WC()->cart : null;
        $itemKey = $this->postedCartItemKey();

        // La clé de ligne est liée à la session du visiteur : on ne peut
        // retirer que ce qui se trouve dans son propre panier.
        if (!$cart || $itemKey === '' || !$cart->get_cart_item($itemKey)) {
            wp_send_json(['error' => true]);
        }

        $cart->remove_cart_item($itemKey);
        $cart->calculate_totals();

        $this->sendCartFragments($cart);
    }

    /**
     * Endpoint AJAX (`?wc-ajax=hdb_update_cart_item_qty`) : change la quantité
     * d'une ligne (produit simple ou variation), après vérification des
     * bornes (min/max/pas) et du stock. En cas de refus, le panier n'est pas
     * modifié et un message est renvoyé.
     */
    public function ajaxUpdateCartItemQty(): void
    {
        $cart    = class_exists('WooCommerce') ? WC()->cart : null;
        $itemKey = $this->postedCartItemKey();
        $qty     = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        $cartItem = ($cart && $itemKey !== '') ? $cart->get_cart_item($itemKey) : null;

        if (!$cart || empty($cartItem)) {
            wp_send_json(['error' => true]);
        }

        /** @var \WC_Product $product */
        $product = $cartItem['data'];

        // Quantité nulle ou négative : équivaut à retirer l'article.
        if ($qty <= 0) {
            $cart->remove_cart_item($itemKey);
            $cart->calculate_totals();
            $this->sendCartFragments($cart);
        }

        $limits = $this->getQuantityLimits($product);

        if ($qty < $limits['min'] || ($limits['max'] > 0 && $qty > $limits['max'])
            || ($limits['step'] > 1 && $qty % $limits['step'] !== 0)) {
            wp_send_json([
                'error'   => true,
                'message' => __('Cette quantité n’est pas disponible pour cet article.', 'hindboutik-core'),
            ]);
        }

        $previousQty = (int) $cartItem['quantity'];

        // Contrôle de stock natif WooCommerce (gère le stock au niveau du
        // parent pour les variations et les quantités déjà réservées).
        $cart->set_quantity($itemKey, $qty, false);
        $stockCheck = $cart->check_cart_item_stock();

        if (is_wp_error($stockCheck)) {
            $cart->set_quantity($itemKey, $previousQty, false);

            wp_send_json([
                'error'   => true,
                'message' => wp_strip_all_tags($stockCheck->get_error_message()),
            ]);
        }

        $cart->calculate_totals();

        $this->sendCartFragments($cart);
    }

    private function postedCartItemKey(): string
    {
        return isset($_POST['cart_item_key']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            ? sanitize_text_field(wp_unslash($_POST['cart_item_key'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
    }

    /**
     * Réponse commune : fragments (tiroir + compteur du header) et hash du panier.
     */
    private function sendCartFragments(\WC_Cart $cart): void
    {
        wp_send_json([
            'fragments' => apply_filters('woocommerce_add_to_cart_fragments', []),
            'cart_hash' => $cart->get_cart_hash(),
        ]);
    }

    /**
     * Bornes de quantité d'un produit (mêmes filtres que le champ quantité
     * natif de WooCommerce). `max` vaut -1 quand il n'y a pas de limite.
     *
     * @return array{min:int, max:int, step:int}
     */
    private function getQuantityLimits(\WC_Product $product): array
    {
        $min  = (int) apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product);
        $max  = (int) apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product);
        $step = (int) apply_filters('woocommerce_quantity_input_step', 1, $product);

        return [
            'min'  => max(1, $min),
            'max'  => $max > 0 ? $max : -1,
            'step' => max(1, $step),
        ];
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

                $limits = $this->getQuantityLimits($product);

                $items[] = [
                    'key'            => $cartItemKey,
                    'name'           => $product->get_name(),
                    'image'          => $product->get_image('thumbnail'),
                    'variation_text' => wc_get_formatted_cart_item_data($cartItem, true),
                    'quantity'       => (int) $cartItem['quantity'],
                    'qty_min'        => $limits['min'],
                    'qty_max'        => $limits['max'],
                    'qty_step'       => $limits['step'],
                    'qty_editable'   => !$product->is_sold_individually() && $limits['max'] !== 1,
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
     * `ajax_add` vaut false pour les produits qui demandent un choix (variable,
     * groupé…) ou qui ne sont pas achetables directement : le tiroir affiche
     * alors un lien vers la fiche produit au lieu du bouton « Ajouter ».
     *
     * @return array<int, array{id:int, name:string, image:string, price_html:string, ajax_add:bool, url:string}>
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
                'ajax_add'   => $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock(),
                'url'        => $product->get_permalink(),
            ];
        }

        return $suggestions;
    }
}