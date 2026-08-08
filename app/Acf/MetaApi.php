<?php
declare(strict_types=1);

namespace HindBoutik\Acf;

/**
 * Native meta read API — drop-in replacement for ACF's get_field().
 *
 * Each field name maps to a context (post / term / option) and a
 * prefixed meta key.  The migration class ensures the data exists.
 */
class MetaApi
{
    /**
     * Field name → [context, meta_key, return_format].
     *
     * return_format: 'raw' | 'bool' | 'image_array' | 'image_url' | 'html'
     */
    public const FIELD_MAP = [
        // Product post meta
        'description'          => ['post',  'hindboutik_product_description',          'html'],
        'taille_unique'        => ['post',  'hindboutik_product_taille_unique',        'bool'],
        'texte_taille_unique'  => ['post',  'hindboutik_product_texte_taille_unique',  'text'],

        // Term meta (product_cat)
        'category_background'    => ['term',  'hindboutik_category_background',          'image_array'],

        // Options
        'categorie_de_produit'   => ['option', 'hindboutik_size_guide_data',             'repeater'],
        'text_top_bar'           => ['option', 'hindboutik_top_bar_text',                'html'],
        'icone'                  => ['option', 'hindboutik_top_bar_icon',                'image_url'],

        // WhatsApp settings
        'whatsapp_phone'         => ['option', 'hindboutik_whatsapp_phone',             'text'],
        'whatsapp_message'       => ['option', 'hindboutik_whatsapp_message',           'text'],
        'whatsapp_display_text'  => ['option', 'hindboutik_whatsapp_display_text',      'text'],
    ];

    /**
     * Get a meta value the same way ACF's get_field() worked.
     *
     * @param string    $name  ACF field name (e.g. 'description').
     * @param int|string|null $id  Post ID, term ID, or 'option'.
     * @return mixed
     */
    public static function getField(string $name, $id = null): mixed
    {
        if (!isset(self::FIELD_MAP[$name])) {
            // Fallback: try ACF if still active.
            if (function_exists('get_field')) {
                return get_field($name, $id);
            }
            return null;
        }

        [$context, $key, $format] = self::FIELD_MAP[$name];

        // Resolve the actual ID.
        if ($id === null || $id === 'option') {
            $id = $id ?? get_the_ID();
        }

        $value = match ($context) {
            'post'  => get_post_meta((int) $id, $key, true),
            'term'  => get_term_meta((int) $id, $key, true),
            'option' => get_option($key),
            default => null,
        };

        return self::format($value, $format, $id);
    }

    /**
     * Echo a field value (replacement for ACF's the_field()).
     */
    public static function theField(string $name, $id = null): void
    {
        echo self::getField($name, $id);
    }

    /**
     * Get the human-readable label for a field (replacement for ACF's get_field_object()).
     */
    public static function getFieldLabel(string $name): string
    {
        return match ($name) {
            'description'            => __('Description', 'hindboutik-core'),
            'taille_unique'          => __('Taille unique', 'hindboutik-core'),
            'texte_taille_unique'    => __('Texte taille unique', 'hindboutik-core'),
            'category_background'    => __('Category background', 'hindboutik-core'),
            'categorie_de_produit'   => __('Catégorie de produit', 'hindboutik-core'),
            'text_top_bar'           => __('Text top bar', 'hindboutik-core'),
            'icone'                  => __('Icone', 'hindboutik-core'),
            default                  => $name,
        };
    }

    /**
     * Format a raw value based on its registered return format.
     */
    private static function format(mixed $value, string $format, $id): mixed
    {
        if ($value === '' || $value === null) {
            return match ($format) {
                'bool'       => false,
                'repeater'   => [],
                default      => $value,
            };
        }

        return match ($format) {
            'bool'        => (bool) (int) $value,
            'repeater'    => is_array($value) ? $value : [],
            // Mime le format ACF "array" (return_format: 'array') : url, ID, sizes...
            // Important pour tout appel existant côté thème/Divi de type
            // get_field('category_background')['url'].
            'image_array' => is_numeric($value) ? self::attachmentToAcfArray((int) $value) : $value,
            'image_url'   => is_numeric($value) ? (wp_get_attachment_image_url((int) $value, 'full') ?: '') : $value,
            default       => $value,
        };
    }

    /**
     * Construit un tableau compatible avec le format ACF "array" pour un champ image.
     */
    private static function attachmentToAcfArray(int $attachmentId): array
    {
        $url = wp_get_attachment_image_url($attachmentId, 'full');

        if (!$url) {
            return [];
        }

        return [
            'ID'    => $attachmentId,
            'id'    => $attachmentId,
            'url'   => $url,
            'alt'   => get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
            'sizes' => [
                'thumbnail' => wp_get_attachment_image_url($attachmentId, 'thumbnail') ?: $url,
                'medium'    => wp_get_attachment_image_url($attachmentId, 'medium') ?: $url,
                'large'     => wp_get_attachment_image_url($attachmentId, 'large') ?: $url,
            ],
        ];
    }
}