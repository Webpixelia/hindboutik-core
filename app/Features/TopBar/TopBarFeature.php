<?php
declare(strict_types=1);

namespace HindBoutik\Features\TopBar;

use HindBoutik\Core\FeatureInterface;

/**
 * Shortcode pour le texte du bandeau top bar (section Divi Header Builder).
 * Remplace le texte codé en dur dans le module Texte Divi.
 */
class TopBarFeature implements FeatureInterface
{
    public function register(): void
    {
        add_shortcode('hindboutik_top_bar', [$this, 'renderShortcode']);
    }

    public function unregister(): void
    {
        remove_shortcode('hindboutik_top_bar');
    }

    public function renderShortcode(): string
    {
        $content = get_option('hindboutik_top_bar_text', '');

        return $content !== '' ? wp_kses_post($content) : '';
    }
}