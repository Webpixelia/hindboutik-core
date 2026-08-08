<?php
declare(strict_types=1);

namespace HindBoutik\Features\MenuIcons;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;

/**
 * Cart and Wishlist menu icons.
 *
 * Replaces the old "icons-count-menu.php" mu-plugin.
 * Shortcodes: [woo_cart_but], [yith_wcwl_items_count]
 */
class MenuIconsFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('menu_icons')) {
            return;
        }

        add_shortcode('woo_cart_but', [$this, 'cartButton']);
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'updateCartFragments']);
        add_shortcode('yith_wcwl_items_count', [$this, 'wishlistCount']);
        add_filter('wp_nav_menu_menu-compte-anglais_items', [$this, 'addMenuIcons'], 10, 2);
        add_filter('wp_nav_menu_menu-icons_items', [$this, 'addMenuIcons'], 10, 2);
        add_action('wp_ajax_yith_wcwl_update_wishlist_count', [$this, 'ajaxWishlistCount']);
        add_action('wp_ajax_nopriv_yith_wcwl_update_wishlist_count', [$this, 'ajaxWishlistCount']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScript'], 20);
    }

    public function unregister(): void
    {
        remove_shortcode('woo_cart_but');
        remove_filter('woocommerce_add_to_cart_fragments', [$this, 'updateCartFragments']);
        remove_shortcode('yith_wcwl_items_count');
        remove_filter('wp_nav_menu_menu-compte-anglais_items', [$this, 'addMenuIcons'], 10);
        remove_filter('wp_nav_menu_menu-icons_items', [$this, 'addMenuIcons'], 10);
        remove_action('wp_ajax_yith_wcwl_update_wishlist_count', [$this, 'ajaxWishlistCount']);
        remove_action('wp_ajax_nopriv_yith_wcwl_update_wishlist_count', [$this, 'ajaxWishlistCount']);
        remove_action('wp_enqueue_scripts', [$this, 'enqueueScript'], 20);
    }

    /**
     * Render the cart button shortcode.
     */
    public function cartButton(): string
    {
        $cartCount = WC()->cart ? WC()->cart->cart_contents_count : 0;
        $cartUrl = wc_get_cart_url();
        $iconUrl = get_stylesheet_directory_uri() . '/img/icone-panier.svg';

        ob_start();
        ?>
        <a class="menu-item cart-contents" href="<?php echo esc_url($cartUrl); ?>" title="<?php esc_attr_e('Mon panier', 'hindboutik-core'); ?>">
            <img src="<?php echo esc_url($iconUrl); ?>" width="24" height="24" alt="<?php esc_attr_e('Panier', 'hindboutik-core'); ?>">
            <span class="label_menu"><?php esc_html_e('Panier', 'hindboutik-core'); ?></span>
            <?php if ($cartCount > 0): ?>
                <span class="cart-contents-count"><?php echo esc_html($cartCount); ?></span>
            <?php endif; ?>
        </a>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * WooCommerce cart fragments AJAX callback.
     */
    public function updateCartFragments(array $fragments): array
    {
        $cartCount = WC()->cart ? WC()->cart->cart_contents_count : 0;
        $cartUrl = wc_get_cart_url();
        $iconUrl = get_stylesheet_directory_uri() . '/img/icone-panier.svg';

        ob_start();
        ?>
        <a class="cart-contents menu-item" href="<?php echo esc_url($cartUrl); ?>" title="<?php esc_attr_e('Voir mon panier', 'hindboutik-core'); ?>">
            <img src="<?php echo esc_url($iconUrl); ?>" width="24" height="24" alt="<?php esc_attr_e('Panier', 'hindboutik-core'); ?>">
            <span class="label_menu"><?php esc_html_e('Panier', 'hindboutik-core'); ?></span>
            <?php if ($cartCount > 0): ?>
                <span class="cart-contents-count"><?php echo esc_html($cartCount); ?></span>
            <?php endif; ?>
        </a>
        <?php
        $fragments['a.cart-contents'] = (string) ob_get_clean();
        return $fragments;
    }

    /**
     * Wishlist count shortcode.
     */
    public function wishlistCount(): string
    {
        if (!defined('YITH_WCWL')) {
            return '';
        }

        $listCount = \yith_wcwl_count_all_products();
        $wishlistUrl = YITH_WCWL() ? YITH_WCWL()->get_wishlist_url() : '';
        $iconUrl = get_stylesheet_directory_uri() . '/img/icone-favori.svg';

        ob_start();
        ?>
        <a href="<?php echo esc_url($wishlistUrl); ?>">
            <img src="<?php echo esc_url($iconUrl); ?>" width="24" height="24" alt="<?php esc_attr_e('Favoris', 'hindboutik-core'); ?>">
            <span class="label_menu"><?php esc_html_e('Favoris', 'hindboutik-core'); ?></span>
            <?php             if ($listCount > 0): ?>
                <span class="yith-wcwl-items-count">
                    <?php echo esc_html($listCount); ?>
                </span>
            <?php endif; ?>
        </a>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * AJAX callback for wishlist count updates.
     */
    public function ajaxWishlistCount(): void
    {
        wp_send_json(['count' => \yith_wcwl_count_all_products()]);
    }

    /**
     * Enqueue the wishlist count update script.
     */
    public function enqueueScript(): void
    {
        if (!defined('YITH_WCWL')) {
            return;
        }

        wp_add_inline_script(
            'jquery-yith-wcwl',
            "
            jQuery(function($){
                $(document).on('added_to_wishlist removed_from_wishlist', function(){
                    $.get(yith_wcwl_l10n.ajax_url, {
                        action: 'yith_wcwl_update_wishlist_count'
                    }, function(data){
                        $('.yith-wcwl-items-count').children('i').html(data.count);
                    });
                });
            });
            "
        );
    }

    /**
     * Add cart + wishlist icons to navigation menus.
     */
    public function addMenuIcons(string $items, object $args): string
    {
        $items .= "<li class='menu-item favorite_menu icon-favorite'>";
        $items .= do_shortcode('[yith_wcwl_items_count]');
        $items .= "</li>";
        $items .= "<li class='menu-item cart_menu icon-cart'>";
        $items .= do_shortcode('[woo_cart_but]');
        $items .= "</li>";
        return $items;
    }
}