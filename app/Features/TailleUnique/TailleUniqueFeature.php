<?php
declare(strict_types=1);

namespace HindBoutik\Features\TailleUnique;

use HindBoutik\Core\FeatureInterface;
use HindBoutik\Features\SizeGuide\SizeGuideFeature;

/**
 * Affiche "Taille unique" ou le bouton "Guide des tailles".
 *
 * Remplace l'appel ACF get_field() par MetaApi::getField().
 */
class TailleUniqueFeature implements FeatureInterface
{
    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('taille_unique')) {
            return;
        }

        add_action('woocommerce_before_add_to_cart_quantity', [$this, 'renderSizeInfo']);
    }

    public function unregister(): void
    {
        remove_action('woocommerce_before_add_to_cart_quantity', [$this, 'renderSizeInfo']);
    }

    public function renderSizeInfo(): void
    {
        if (!is_product()) {
            return;
        }

        global $product;
        if (!$product) {
            return;
        }

        $productId = $product->get_id();
        // Catégorie sans guide configuré : aucune mention de taille.
        if (SizeGuideFeature::findGuide($productId) === null) {
            return;
        }
        $texteTailleUnique = \HindBoutik\Acf\MetaApi::getField('texte_taille_unique', $productId);
        $tailleUnique      = \HindBoutik\Acf\MetaApi::getField('taille_unique', $productId);

        // On active le JS pour le modal size guide si le produit n'est pas taille unique.
        if (!$tailleUnique) {
            $assets = \HindBoutik\Core\AssetManager::getInstance();
            $assets->enqueueJs('modal-size-guide');
        }

        $texte = '';

        if (!empty($texteTailleUnique)) {
            $texte = $texteTailleUnique;
        } elseif ($tailleUnique) {
            $texte = __('Taille unique', 'hindboutik-core');
        }

        echo '<div class="bloc">';
        echo '<div class="lab">' . esc_html__('Taille', 'hindboutik-core') . '</div>';
        echo '<div class="taille">';

        if ($tailleUnique) {
            echo '<p>' . esc_html($texte) . '</p>';
        } else {
            echo '<button type="button" class="lien-guide" id="size-guide-link">' .
                 esc_html__('Guide des tailles', 'hindboutik-core') . '</button>';
        }

        echo '</div>';  // .taille
        echo '</div>';  // .bloc
    }
}