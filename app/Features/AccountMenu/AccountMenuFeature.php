<?php
declare(strict_types=1);

namespace HindBoutik\Features\AccountMenu;

use HindBoutik\Core\FeatureInterface;

/**
 * Reorganise et renomme les liens du tableau de bord WooCommerce.
 *
 * Replaces the inline snippet from snipets-hdb.php.
 */
class AccountMenuFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('account_menu')) {
            return;
        }

        add_filter('woocommerce_account_menu_items', [$this, 'reorderMenuItems'], 9999);
        add_filter('woocommerce_get_endpoint_url', [$this, 'rewriteEndpointUrl'], 10, 4);
        add_action('template_redirect', [$this, 'redirectDashboard']);
    }

    public function unregister(): void
    {
        remove_filter('woocommerce_account_menu_items', [$this, 'reorderMenuItems']);
        remove_filter('woocommerce_get_endpoint_url', [$this, 'rewriteEndpointUrl'], 10);
        remove_action('template_redirect', [$this, 'redirectDashboard']);
    }

    /**
     * Reorganiser et renommer les liens du menu de compte.
     */
    public function reorderMenuItems(array $menuLinks): array
    {
        unset($menuLinks['dashboard']);
        unset($menuLinks['downloads']);
        unset($menuLinks['loyalty_reward']);

        return [
            'edit-account'        => __('Mes informations', 'hindboutik-core'),
            'edit-address'        => __('Mes adresses', 'hindboutik-core'),
            'orders'              => __('Mes achats', 'hindboutik-core'),
            'recompenses-fidelite' => __('Récompenses et fidélité', 'hindboutik-core'),
            'favorites'           => __('Mes favoris', 'hindboutik-core'),
            'customer-logout'     => __('Déconnexion', 'hindboutik-core'),
        ];
    }

    /**
     * Rewrite endpoint URLs to point to actual pages.
     */
    public function rewriteEndpointUrl(string $url, string $endpoint, $value, $permalink): string
    {
        $favoritesPageId = (int) get_option('hindboutik_favorites_page_id', 91);
        $rewardsPageId   = (int) get_option('hindboutik_rewards_page_id', 38643);

        $favoritesPage = get_post($favoritesPageId);
        $rewardsPage   = get_post($rewardsPageId);

        $favoritesSlug = null;
        $rewardsSlug   = null;

        if ($favoritesPage && $favoritesPage->post_status === 'publish') {
            $favoritesSlug = $favoritesPage->post_name;
        }

        if ($rewardsPage && $rewardsPage->post_status === 'publish') {
            $rewardsSlug = $rewardsPage->post_name;
        }

        if ($endpoint === 'favorites' && $favoritesSlug) {
            return home_url($favoritesSlug);
        }

        if ($endpoint === 'recompenses-fidelite' && $rewardsSlug) {
            return home_url($rewardsSlug);
        }

        return $url;
    }

    /**
     * Redirect account page root to edit-account endpoint.
     */
    public function redirectDashboard(): void
    {
        if (is_account_page() && empty(WC()->query->get_current_endpoint())) {
            wp_safe_redirect(wc_get_account_endpoint_url('edit-account'));
            exit;
        }
    }
}