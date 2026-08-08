<?php
/**
 * Global helper functions for HindBoutik Core.
 *
 * These are loaded before the autoloader and provide utility
 * functions used across the plugin.
 */

declare(strict_types=1);

if (!function_exists('hindboutik_get_field')) {
    /**
     * Replacement for ACF's get_field().
     *
     * @param string       $name  ACF-style field name.
     * @param int|string   $id    Post ID, term ID, or 'option'.
     * @return mixed
     */
    function hindboutik_get_field(string $name, $id = null): mixed
    {
        return \HindBoutik\Acf\MetaApi::getField($name, $id);
    }
}

if (!function_exists('hindboutik_the_field')) {
    /**
     * Replacement for ACF's the_field().
     */
    function hindboutik_the_field(string $name, $id = null): void
    {
        \HindBoutik\Acf\MetaApi::theField($name, $id);
    }
}

if (!function_exists('hindboutik_get_option')) {
    /**
     * Convenience wrapper for get_option with HindBoutik prefix.
     */
    function hindboutik_get_option(string $key, mixed $default = null): mixed
    {
        $value = get_option('hindboutik_' . $key, $default);
        return $value;
    }
}

if (!function_exists('hindboutik_update_option')) {
    /**
     * Convenience wrapper for update_option with HindBoutik prefix.
     */
    function hindboutik_update_option(string $key, mixed $value, bool $autoload = false): bool
    {
        return update_option('hindboutik_' . $key, $value, $autoload);
    }
}

if (!function_exists('hindboutik_asset_url')) {
    /**
     * Build an asset URL.
     */
    function hindboutik_asset_url(string $path): string
    {
        return HINDBOUTIK_CORE_URL . 'assets/' . ltrim($path, '/');
    }
}

if (!function_exists('hindboutik_template')) {
    /**
     * Render a template.
     */
    function hindboutik_template(string $template, array $vars = []): string
    {
        return \HindBoutik\Core\Plugin::instance()
            ->getAssets()
            ->getTemplateLoader()
            ->render($template, $vars);
    }
}

/*
 * ---------------------------------------------------------------------
 * Filet de sécurité : compatibilité avec les fonctions globales ACF.
 * ---------------------------------------------------------------------
 * ACF est désactivé après la migration. Or les champs migrés (description,
 * taille_unique, category_background, guide des tailles, top bar...) ont
 * pu être appelés ailleurs que dans les snippets/mu-plugins fournis ici
 * (templates du thème enfant, modules "contenu dynamique" de Divi Builder
 * stockés en base, etc.). Sans ce shim, le premier get_field() rencontré
 * hors du périmètre audité provoque un fatal error "Call to undefined
 * function get_field()" et casse la page concernée.
 *
 * ⚠️ Ce filet ne couvre QUE les champs listés dans MetaApi::FIELD_MAP.
 * Il est fortement recommandé d'auditer le thème (recherche "get_field(",
 * "the_field(", "get_field_object(") et les modules Divi avant de
 * désactiver définitivement ACF, puis de retirer ce shim une fois
 * l'audit terminé.
 */
if (!function_exists('get_field')) {
    function get_field(string $selector, $post_id = false, bool $format_value = true)
    {
        return \HindBoutik\Acf\MetaApi::getField($selector, $post_id ?: null);
    }
}

if (!function_exists('the_field')) {
    function the_field(string $selector, $post_id = false): void
    {
        \HindBoutik\Acf\MetaApi::theField($selector, $post_id ?: null);
    }
}

if (!function_exists('get_field_object')) {
    function get_field_object(string $selector, $post_id = false): array
    {
        return [
            'label' => \HindBoutik\Acf\MetaApi::getFieldLabel($selector),
            'name'  => $selector,
            'value' => \HindBoutik\Acf\MetaApi::getField($selector, $post_id ?: null),
        ];
    }
}

if (!function_exists('hindboutik_is_feature_enabled')) {
    /**
     * Check if a feature is enabled in admin toggle.
     */
    function hindboutik_is_feature_enabled(string $featureId): bool
    {
        return \HindBoutik\Core\Plugin::isFeatureEnabled($featureId);
    }
}