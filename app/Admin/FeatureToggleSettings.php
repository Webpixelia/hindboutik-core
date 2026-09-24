<?php
declare(strict_types=1);

namespace HindBoutik\Admin;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\Plugin;
use HindBoutik\Features\SizeGuide\SizeGuideFeature;
use HindBoutik\Features\ProductContentDetails\ProductContentDetailsFeature;
use HindBoutik\Features\CrosssellCarousel\CrosssellCarouselFeature;
use HindBoutik\Features\ProductImageGrid\ProductImageGridFeature;
use HindBoutik\Features\PlusMinusQuantity\PlusMinusQuantityFeature;
use HindBoutik\Features\ProductImageSlider\ProductImageSliderFeature;
use HindBoutik\Features\MenuIcons\MenuIconsFeature;
use HindBoutik\Features\KlarnaBadge\KlarnaBadgeFeature;
use HindBoutik\Features\CategoryReadMore\CategoryReadMoreFeature;
use HindBoutik\Features\SaleBadge\SaleBadgeFeature;
use HindBoutik\Features\AccountMenu\AccountMenuFeature;
use HindBoutik\Features\AccountScripts\AccountScriptsFeature;
use HindBoutik\Features\TailleUnique\TailleUniqueFeature;
use HindBoutik\Features\FreeShippingProgress\FreeShippingProgressFeature;
use HindBoutik\Features\AfterMessage\AfterMessageFeature;
use HindBoutik\Features\ColorSwatches\ColorSwatchesFeature;
use HindBoutik\Features\TaxStatusColumn\TaxStatusColumnFeature;
use HindBoutik\Features\FreeShippingLabel\FreeShippingLabelFeature;
use HindBoutik\Features\WhatsAppWidget\WhatsAppWidgetFeature;
use HindBoutik\Features\TopBar\TopBarFeature;
use HindBoutik\Features\StickyAddToCart\StickyAddToCartFeature;
use HindBoutik\Features\CartDrawer\CartDrawerFeature;

/**
 * Admin page to toggle individual features on/off.
 *
 * Each feature is stored as an option: hindboutik_feature_enabled_{id}
 * All features default to enabled on plugin activation.
 */
class FeatureToggleSettings
{
    /**
     * Catalogue of all features.
     * key: {option_id_prefix}
     * class: Feature class FQCN
     * label: Display name
     * description: Short description
     * default: bool (true = enabled by default)
     */
    public const FEATURES = [
        'whatsapp_widget' => [
            'class'       => WhatsAppWidgetFeature::class,
            'label'       => 'Widget WhatsApp',
            'description' => 'Bouton WhatsApp flottant en bas à droite de toutes les pages.',
            'category'    => 'Front-office',
            'default'     => true,
        ],
        'size_guide' => [
            'class'       => SizeGuideFeature::class,
            'label'       => 'Guide des tailles',
            'description' => 'Modal de guide des tailles sur les pages produit via [custom_size_guide].',
            'category'    => 'Produit',
            'default'     => true,
        ],
        'product_content_details' => [
            'class'       => ProductContentDetailsFeature::class,
            'label'       => 'Détails produit',
            'description' => 'Onglets description & livraison via [product_content_details].',
            'category'    => 'Produit',
            'default'     => true,
        ],
        'crosssell_carousel' => [
            'class'       => CrosssellCarouselFeature::class,
            'label'       => 'Carousel cross-sell',
            'description' => 'Carousel de produits cross-sell sur la page panier.',
            'category'    => 'Panier',
            'default'     => true,
        ],
        'product_image_grid' => [
            'class'       => ProductImageGridFeature::class,
            'label'       => 'Grille d’images produit',
            'description' => 'Grille d’images via [custom_product_image_grid].',
            'category'    => 'Produit',
            'default'     => true,
        ],
        'plus_minus_quantity' => [
            'class'       => PlusMinusQuantityFeature::class,
            'label'       => 'Boutons +/- quantité',
            'description' => 'Boutons + et - pour augmenter/diminuer la quantité.',
            'category'    => 'Produit',
            'default'     => true,
        ],
        'product_image_slider' => [
            'class'       => ProductImageSliderFeature::class,
            'label'       => 'Slider d’images produit',
            'description' => 'Slider Slick pour les images produit via [current_product_image_slider].',
            'category'    => 'Produit',
            'default'     => true,
        ],
        'menu_icons' => [
            'class'       => MenuIconsFeature::class,
            'label'       => 'Icônes panier & favoris',
            'description' => 'Icônes panier et favoris dans les menus de navigation.',
            'category'    => 'Menus',
            'default'     => true,
        ],
        'klarna_badge' => [
            'class'       => KlarnaBadgeFeature::class,
            'label'       => 'Badge Klarna',
            'description' => 'Badge de paiement Klarna sur les pages produit.',
            'category'    => 'Paiement',
            'default'     => true,
        ],
        'category_read_more' => [
            'class'       => CategoryReadMoreFeature::class,
            'label'       => 'Read-more catégorie',
            'description' => 'Troncature du texte des descriptions de catégories produits.',
            'category'    => 'Catalogue',
            'default'     => true,
        ],
        'sale_badge' => [
            'class'       => SaleBadgeFeature::class,
            'label'       => 'Badge promotion',
            'description' => 'Texte personnalisé pour les badges de vente (ex: "Pièce unique").',
            'category'    => 'Catalogue',
            'default'     => true,
        ],
        'account_menu' => [
            'class'       => AccountMenuFeature::class,
            'label'       => 'Menu de compte',
            'description' => 'Réorganisation et renommage des liens du compte WooCommerce.',
            'category'    => 'Compte client',
            'default'     => true,
        ],
        'account_scripts' => [
            'class'       => AccountScriptsFeature::class,
            'label'       => 'Scripts compte',
            'description' => 'Scripts JS pour la page de compte (toggle adresses, ...).',
            'category'    => 'Compte client',
            'default'     => true,
        ],
        'taille_unique' => [
            'class'       => TailleUniqueFeature::class,
            'label'       => 'Taille unique',
            'description' => 'Affichage du texte "Taille unique" ou du bouton guide des tailles.',
            'category'    => 'Produit',
            'default'     => true,
        ],
        'free_shipping_progress' => [
            'class'       => FreeShippingProgressFeature::class,
            'label'       => 'Barre de progression',
            'description' => 'Barre de progression vers la livraison gratuite via [ts_progress_bar_free_shipping].',
            'category'    => 'Panier',
            'default'     => true,
        ],
        'after_message' => [
            'class'       => AfterMessageFeature::class,
            'label'       => 'Message après panier',
            'description' => 'Messages retours & échanges + service client via [ts_message_after].',
            'category'    => 'Panier',
            'default'     => true,
        ],
        'color_swatches' => [
            'class'       => ColorSwatchesFeature::class,
            'label'       => 'Pastilles de couleur',
            'description' => 'Pastilles de couleur pour les variations produit dans la boucle.',
            'category'    => 'Catalogue',
            'default'     => true,
        ],
        'tax_status_column' => [
            'class'       => TaxStatusColumnFeature::class,
            'label'       => 'Colonne TVA admin',
            'description' => 'Colonne "État de la TVA" dans la liste des produits WooCommerce.',
            'category'    => 'Admin',
            'default'     => true,
        ],
        'free_shipping_label' => [
            'class'       => FreeShippingLabelFeature::class,
            'label'       => 'Label "OFFERT"',
            'description' => 'Remplace "Gratuit" par "OFFERT" pour points relais au checkout.',
            'category'    => 'Checkout',
            'default'     => true,
        ],
        'top_bar' => [
            'class'       => TopBarFeature::class,
            'label'       => 'Bandeau top bar (Divi)',
            'description' => "Bandeau d'annonce en haut du site (section Header Builder Divi). Texte modifiable dans Réglages > Top bar.",
            'category'    => 'Header',
            'default'     => true,
        ],
        'sticky_add_to_cart' => [
            'class'       => StickyAddToCartFeature::class,
            'label'       => "Barre d'achat collante (mobile)",
            'description' => "Barre fixe en bas d'écran reprenant l'ajout au panier sur la fiche produit, avec libellé adapté à l'état de la sélection de variation (aucune / valide / rupture).",
            'category'    => 'Produit',
            'default'     => true,
        ],
        'cart_drawer' => [
            'class'       => CartDrawerFeature::class,
            'label'       => 'Tiroir panier (cart drawer)',
            'description' => "Panneau qui glisse à l'ajout au panier ou au clic sur l'icône panier du header (rendue flottante au scroll), avec jauge de livraison offerte et suggestion de complément. Ajaxifie l'ajout au panier de la fiche produit.",
            'category'    => 'Produit',
            'default'     => true,
        ],
    ];

    /**
     * Sous-réglages : options booléennes rattachées à une feature parente,
     * trop spécifiques pour mériter leur propre entrée dans FEATURES (pas
     * de classe FeatureInterface dédiée, pas d'enregistrement dans la boucle
     * Plugin::init()). Affichées comme ligne indentée sous la feature parente.
     *
     * key: option_id_suffix (stocké dans hindboutik_subopt_{id})
     * parent: id de la feature parente (clé de FEATURES)
     * label / description: affichage
     * default: bool
     */
    public const SUB_OPTIONS = [
        'cart_drawer_floating_icon' => [
            'parent'      => 'cart_drawer',
            'label'       => 'Icône panier flottante au scroll',
            'description' => "Fait réapparaître l'icône panier en position flottante quand le header sort du viewport (clic = ouverture du tiroir).",
            'default'     => true,
        ],
    ];

    /**
     * Register the admin submenu page.
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'hindboutik-settings',                    // parent
            __('Gestion des fonctionnalités', 'hindboutik-core'),
            __('Fonctionnalités', 'hindboutik-core'),
            'manage_options',
            'hindboutik-features',
            [$this, 'render']
        );
    }

    public function registerSettings(): void
    {
        $optionGroup = 'hindboutik_feature_toggles';

        foreach (self::FEATURES as $id => $feature) {
            register_setting($optionGroup, 'hindboutik_feature_enabled_' . $id, [
                'type'              => 'boolean',
                'default'           => $feature['default'],
                'show_in_rest'      => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ]);
        }

        foreach (self::SUB_OPTIONS as $id => $subOption) {
            register_setting($optionGroup, 'hindboutik_subopt_' . $id, [
                'type'              => 'boolean',
                'default'           => $subOption['default'],
                'show_in_rest'      => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ]);
        }
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'hindboutik_page_hindboutik-features') {
            return;
        }

        // Enqueue any admin CSS needed.
        wp_enqueue_style(
            'hindboutik-feature-toggles',
            HINDBOUTIK_CORE_URL . 'assets/css/admin/feature-toggles.css',
            [],
            HINDBOUTIK_CORE_VERSION
        );
    }

    /**
     * Check if a feature is enabled.
     */
    public static function isFeatureEnabled(string $featureId): bool
    {
        $option = get_option('hindboutik_feature_enabled_' . $featureId);

        // If option doesn't exist, fall back to default.
        if ($option === false) {
            return self::FEATURES[$featureId]['default'] ?? false;
        }

        // ACF stores booleans as strings — normalize.
        if (is_string($option)) {
            return $option === '1' || $option === 'true';
        }

        return (bool) $option;
    }

    /**
     * Check if a sub-option is enabled.
     */
    public static function isSubOptionEnabled(string $subOptionId): bool
    {
        $option = get_option('hindboutik_subopt_' . $subOptionId);

        if ($option === false) {
            return self::SUB_OPTIONS[$subOptionId]['default'] ?? false;
        }

        if (is_string($option)) {
            return $option === '1' || $option === 'true';
        }

        return (bool) $option;
    }

    /**
     * Enable all features (reset to defaults).
     */
    public static function enableAll(): void
    {
        foreach (self::FEATURES as $id => $feature) {
            update_option('hindboutik_feature_enabled_' . $id, $feature['default'] ? 1 : 0);
        }

        foreach (self::SUB_OPTIONS as $id => $subOption) {
            update_option('hindboutik_subopt_' . $id, $subOption['default'] ? 1 : 0);
        }
    }

    /**
     * Disable all features.
     */
    public static function disableAll(): void
    {
        foreach (self::FEATURES as $id => $_) {
            update_option('hindboutik_feature_enabled_' . $id, 0);
        }

        foreach (self::SUB_OPTIONS as $id => $_) {
            update_option('hindboutik_subopt_' . $id, 0);
        }
    }

    /**
     * Render the admin page.
     */
    public function render(): void
    {
        $optionGroup = 'hindboutik_feature_toggles';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Gestion des fonctionnalités', 'hindboutik-core'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields($optionGroup);
                do_settings_sections('hindboutik-features');
                ?>

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Fonctionnalité', 'hindboutik-core'); ?></th>
                            <th><?php esc_html_e('Catégorie', 'hindboutik-core'); ?></th>
                            <th style="width:60px;"><?php esc_html_e('Active', 'hindboutik-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (self::FEATURES as $id => $feature): ?>
                            <?php
                            $enabled = self::isFeatureEnabled($id);
                            $className = 'hindboutik-feature-row';
                            if (!$enabled) {
                                $className .= ' disabled';
                            }
                            ?>
                            <tr class="<?php echo esc_attr($className); ?>" data-feature-id="<?php echo esc_attr($id); ?>">
                                <td>
                                    <strong><?php echo esc_html($feature['label']); ?></strong>
                                    <p class="description"><?php echo esc_html($feature['description']); ?></p>
                                </td>
                                <td><?php echo esc_html($feature['category']); ?></td>
                                <td>
                                    <input type="checkbox"
                                           name="hindboutik_feature_enabled_<?php echo esc_attr($id); ?>"
                                           value="1"
                                           <?php checked($enabled, true); ?>>
                                    <input type="hidden"
                                           name="hindboutik_feature_enabled_<?php echo esc_attr($id); ?>_exists"
                                           value="1">
                                </td>
                            </tr>
                            <?php foreach (self::SUB_OPTIONS as $subId => $subOption): ?>
                                <?php if ($subOption['parent'] !== $id) continue; ?>
                                <?php
                                $subEnabled = self::isSubOptionEnabled($subId);
                                $subClassName = 'hindboutik-feature-row hindboutik-suboption-row';
                                if (!$subEnabled) {
                                    $subClassName .= ' disabled';
                                }
                                ?>
                                <tr class="<?php echo esc_attr($subClassName); ?>" data-feature-id="<?php echo esc_attr($subId); ?>">
                                    <td style="padding-left:2.5em;">
                                        <span style="color:#8c8f94;">↳</span>
                                        <strong><?php echo esc_html($subOption['label']); ?></strong>
                                        <p class="description"><?php echo esc_html($subOption['description']); ?></p>
                                    </td>
                                    <td></td>
                                    <td>
                                        <input type="checkbox"
                                               name="hindboutik_subopt_<?php echo esc_attr($subId); ?>"
                                               value="1"
                                               <?php checked($subEnabled, true); ?>>
                                        <input type="hidden"
                                               name="hindboutik_subopt_<?php echo esc_attr($subId); ?>_exists"
                                               value="1">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <?php submit_button(); ?>
                    <button type="button" class="button button-secondary" id="hindboutik-enable-all"><?php esc_html_e('Activer tout', 'hindboutik-core'); ?></button>
                    <button type="button" class="button" id="hindboutik-disable-all"><?php esc_html_e('Désactiver tout', 'hindboutik-core'); ?></button>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($){
            $('#hindboutik-enable-all').on('click', function(){
                $('input[type="checkbox"][name^="hindboutik_feature_enabled_"], input[type="checkbox"][name^="hindboutik_subopt_"]').prop('checked', true);
            });
            $('#hindboutik-disable-all').on('click', function(){
                $('input[type="checkbox"][name^="hindboutik_feature_enabled_"], input[type="checkbox"][name^="hindboutik_subopt_"]').prop('checked', false);
            });
        });
        </script>
        <?php
    }
}