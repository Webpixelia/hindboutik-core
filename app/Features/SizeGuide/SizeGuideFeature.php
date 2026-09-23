<?php
declare(strict_types=1);

namespace HindBoutik\Features\SizeGuide;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;
use HindBoutik\Helpers\TemplateLoader;

/**
 * Size Guide Feature.
 *
 * Replaces the old "Custom Size Guide" mu-plugin.
 * Reads size guide data from native options (migrated from ACF).
 * Shortscode: [custom_size_guide]
 */
class SizeGuideFeature implements FeatureInterface
{
    private TemplateLoader $templates;

    public function __construct()
    {
        $this->templates = new TemplateLoader();
    }

    public function register(): void 
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('size_guide')) {
            return;
        }

        add_shortcode('custom_size_guide', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_shortcode('custom_size_guide');
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueueAssets(): void
    {
        if (!is_product()) {
            return;
        }
        $assets = AssetManager::getInstance();
        $assets->enqueueJs('modal-size-guide');
        $assets->enqueueCss('size-guide-modal');
    }

    /**
     * Retourne le guide correspondant aux catégories du produit, ou null.
     */
    public static function findGuide(int $productId): ?array
    {
        $terms = get_the_terms($productId, 'product_cat');

        if (empty($terms) || is_wp_error($terms)) {
            return null;
        }

        $categoryIds = array_map('intval', wp_list_pluck($terms, 'term_id'));
        $guides      = \HindBoutik\Acf\MetaApi::getField('categorie_de_produit', 'option') ?: [];

        foreach ($guides as $guide) {
            $guideCategories = $guide['categories'] ?? $guide['categorie'] ?? [];

            if (!is_array($guideCategories)) {
                continue;
            }

            if (!empty(array_intersect($categoryIds, array_map('intval', $guideCategories)))) {
                return $guide;
            }
        }

        return null;
    }

    /**
     * Shortcode handler — renders the size guide modal.
     */
    public function shortcode(): string
    {
        if (!is_product()) {
            return '';
        }

        $productId = get_the_ID();
        $guide     = self::findGuide($productId);

        // Catégorie sans guide, ou produit taille unique (pas de bouton) : rien à rendre.
        if ($guide === null || \HindBoutik\Acf\MetaApi::getField('taille_unique', $productId)) {
            return '';
        }

        return $this->templates->render('size-guide-modal', [
            'product_id'    => $productId,
            'matched_guide' => $guide,
        ]);
    }
}