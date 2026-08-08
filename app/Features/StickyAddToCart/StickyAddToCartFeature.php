<?php
declare(strict_types=1);

namespace HindBoutik\Features\StickyAddToCart;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;
use HindBoutik\Helpers\TemplateLoader;

/**
 * Barre d'achat collante (mobile) sur la fiche produit.
 *
 * Reprend le bouton d'ajout au panier dans une barre fixe en bas d'écran
 * dès que le bouton principal sort du viewport. Trois états, gérés côté
 * JS via les évènements natifs du formulaire de variations WooCommerce :
 *
 *   - aucune sélection    → bouton en contour "Choisir un coloris / une taille"
 *   - sélection valide    → bouton plein "Ajouter" (nom + prix rappelés)
 *   - variante en rupture → bouton inactif "Épuisé" (+ nb d'alternatives dispo)
 *
 * Le PHP ne fait que poser le squelette HTML (état initial "sans sélection"
 * pour les produits variables, "prêt à ajouter" pour les produits simples) ;
 * toute la logique d'état est ensuite pilotée par sticky-add-to-cart.js.
 */
class StickyAddToCartFeature implements FeatureInterface
{
    private TemplateLoader $templates;

    public function __construct()
    {
        $this->templates = new TemplateLoader();
    }

    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('sticky_add_to_cart')) {
            return;
        }

        add_action('woocommerce_after_single_product_summary', [$this, 'renderBar'], 5);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_action('woocommerce_after_single_product_summary', [$this, 'renderBar'], 5);
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (!$this->shouldDisplay()) {
            return;
        }

        $assets = AssetManager::getInstance();
        $assets->enqueueCss('sticky-add-to-cart');
        $assets->enqueueJs('sticky-add-to-cart');

        wp_localize_script('sticky-add-to-cart', 'hdbStickyAtc', [
            'chooseText'       => __('Choisir', 'hindboutik-core'),
            'addToCartText'    => __('Ajouter', 'hindboutik-core'),
            'outOfStockText'   => __('Épuisé', 'hindboutik-core'),
            /* translators: %d = nombre d'alternatives encore disponibles. */
            'outOfStockSuffix' => __('· %d autres coloris disponibles', 'hindboutik-core'),
        ]);
    }

    private function shouldDisplay(): bool
    {
        return class_exists('WooCommerce') && function_exists('is_product') && is_product();
    }

    public function renderBar(): void
    {
        if (!$this->shouldDisplay()) {
            return;
        }

        global $product;

        if (!$product instanceof \WC_Product || !$product->is_purchasable()) {
            return;
        }

        $vars = [
            'product_name'       => $product->get_name(),
            'price_html'         => $product->get_price_html(), // toujours le prix "de base", utilisé par le JS comme repli.
            'display_price_html' => $product->get_price_html(),
            'is_variable'        => $product->is_type('variable'),
            'labels'             => $this->buildAttributeLabels(),
            'state'              => 'vide',
            'variation_label'    => '',
            'out_of_stock_count' => 0,
        ];

        if (!$product instanceof \WC_Product_Variable) {
            // Produit simple : toujours prêt à ajouter, pas de notion de variante.
            $vars['state'] = 'plein';
        } else {
            $initial = $this->resolveInitialVariation($product);

            if (null !== $initial) {
                $variation = $initial['variation'];

                $vars['variation_label']    = $initial['label'];
                $vars['display_price_html'] = $variation->get_price_html();

                if ($variation->is_in_stock()) {
                    $vars['state'] = 'plein';
                } else {
                    $vars['state']              = 'off';
                    $vars['out_of_stock_count'] = $this->countAvailableAlternatives(
                        $product,
                        $initial['attributes'],
                        $initial['primary_attribute']
                    );
                }
            }
            // Sinon : sélection incomplète au chargement → état "vide" par défaut,
            // le JS prendra le relais dès que la cliente choisit une variante.
        }

        echo $this->templates->render('sticky-add-to-cart', $vars);
    }

    /**
     * Libellés français pour le bouton "Choisir ..." selon l'attribut
     * de variation manquant. À compléter au besoin pour d'autres taxonomies.
     *
     * @return array<string, string>
     */
    private function buildAttributeLabels(): array
    {
        return [
            'pa_couleur' => __('un coloris', 'hindboutik-core'),
            'pa_color'   => __('un coloris', 'hindboutik-core'),
            'pa_taille'  => __('une taille', 'hindboutik-core'),
            'pa_size'    => __('une taille', 'hindboutik-core'),
            'default'    => __('une option', 'hindboutik-core'),
        ];
    }

    /**
     * Tente de résoudre la variation déjà déterminée au chargement de la page :
     * attributs passés en query string (?attribute_pa_couleur=...), attributs
     * par défaut du produit, ou attribut n'ayant qu'un seul choix possible.
     *
     * Évite un "flash" vers l'état "Choisir une option" côté JS quand la
     * variante est en réalité déjà connue avant même l'exécution du script
     * (lien direct vers un coloris, produit à un seul coloris, etc.).
     *
     * @return array{variation: \WC_Product_Variation, attributes: array<string,string>, label: string, primary_attribute: string}|null
     */
    private function resolveInitialVariation(\WC_Product_Variable $product): ?array
    {
        $variationAttributes = $product->get_variation_attributes();

        if (empty($variationAttributes)) {
            return null;
        }

        $defaultAttributes = $product->get_default_attributes();
        $selected          = [];
        $primaryAttribute  = null;

        foreach ($variationAttributes as $taxonomy => $options) {
            $requestKey = 'attribute_' . sanitize_title($taxonomy);
            $value      = '';

            if (isset($_GET[$requestKey])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $value = wc_clean(wp_unslash((string) $_GET[$requestKey])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif (!empty($defaultAttributes[$taxonomy])) {
                $value = $defaultAttributes[$taxonomy];
            } elseif (!empty($defaultAttributes[str_replace('pa_', '', $taxonomy)])) {
                $value = $defaultAttributes[str_replace('pa_', '', $taxonomy)];
            } elseif (1 === count($options)) {
                // Un seul choix possible pour cet attribut : sélection implicite.
                $value = current($options);
            }

            if ('' === $value) {
                // Sélection incomplète : on ne peut pas déterminer de variation.
                return null;
            }

            $selected[$taxonomy] = $value;

            if (null === $primaryAttribute && $this->isPrimaryAttribute($taxonomy)) {
                $primaryAttribute = $taxonomy;
            }
        }

        if (null === $primaryAttribute) {
            $primaryAttribute = array_key_first($selected);
        }

        $dataStore   = \WC_Data_Store::load('product');
        $variationId = $dataStore->find_matching_product_variation($product, $selected);

        if (!$variationId) {
            return null;
        }

        $variation = wc_get_product($variationId);

        if (!$variation instanceof \WC_Product_Variation) {
            return null;
        }

        return [
            'variation'         => $variation,
            'attributes'        => $selected,
            'label'             => $this->getAttributeValueLabel($primaryAttribute, $selected[$primaryAttribute]),
            'primary_attribute' => $primaryAttribute,
        ];
    }

    /**
     * Attribut considéré comme "principal" pour l'affichage du nom de
     * variante et le calcul des alternatives disponibles (typiquement
     * le coloris, cf. ColorSwatchesFeature qui utilise la même taxonomie).
     */
    private function isPrimaryAttribute(string $taxonomy): bool
    {
        return in_array($taxonomy, ['pa_couleur', 'pa_color'], true);
    }

    /**
     * Libellé lisible d'une valeur d'attribut (nom du terme de taxonomie,
     * ou valeur brute nettoyée pour un attribut personnalisé).
     */
    private function getAttributeValueLabel(string $taxonomy, string $value): string
    {
        if (taxonomy_exists($taxonomy)) {
            $term = get_term_by('slug', $value, $taxonomy);

            if ($term && !is_wp_error($term)) {
                return $term->name;
            }
        }

        return ucfirst(str_replace('-', ' ', $value));
    }

    /**
     * Compte, parmi les variations encore en stock, le nombre de valeurs
     * distinctes de l'attribut principal (hors valeur actuellement
     * sélectionnée) compatibles avec les autres attributs déjà choisis.
     *
     * Sert au message "Épuisé · X autres coloris disponibles".
     *
     * @param array<string,string> $selected
     */
    private function countAvailableAlternatives(\WC_Product_Variable $product, array $selected, string $primaryAttribute): int
    {
        $primaryKey = 'attribute_' . sanitize_title($primaryAttribute);
        $seen       = [];

        foreach ($product->get_available_variations() as $variationData) {
            if (empty($variationData['is_in_stock'])) {
                continue;
            }

            $attributes = $variationData['attributes'] ?? [];
            $value      = $attributes[$primaryKey] ?? '';

            if ('' === $value || $value === $selected[$primaryAttribute]) {
                continue;
            }

            $matchesOthers = true;

            foreach ($selected as $taxonomy => $selectedValue) {
                if ($taxonomy === $primaryAttribute) {
                    continue;
                }

                $otherKey   = 'attribute_' . sanitize_title($taxonomy);
                $otherValue = $attributes[$otherKey] ?? '';

                if ('' !== $otherValue && $otherValue !== $selectedValue) {
                    $matchesOthers = false;
                    break;
                }
            }

            if ($matchesOthers) {
                $seen[$value] = true;
            }
        }

        return count($seen);
    }
}