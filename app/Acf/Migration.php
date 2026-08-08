<?php
declare(strict_types=1);

namespace HindBoutik\Acf;

/**
 * Migrates existing ACF data to native WordPress meta / options.
 *
 * Runs once on plugin activation.  Safe to re-run (idempotent).
 */
class Migration
{
    /**
     * Mapping: [acf_field_name => [meta_key, context, source]].
     * context: 'post' | 'term' | 'option'
     */
    private const MIGRATION_MAP = [
        // Post meta → prefixed keys
        'description'         => ['hindboutik_product_description',          'post'],
        'taille_unique'       => ['hindboutik_product_taille_unique',        'post'],
        'texte_taille_unique' => ['hindboutik_product_texte_taille_unique',  'post'],
        // Term meta
        'category_background' => ['hindboutik_category_background',            'term'],
        // Options
        'text_top_bar'        => ['hindboutik_top_bar_text',                 'option'],
        'icone'               => ['hindboutik_top_bar_icon',                 'option'],
        'categorie_de_produit' => ['hindboutik_size_guide_data',              'option'],
    ];

    /**
     * Run the full migration.
     */
    public function run(): void
    {
        $this->migrateProductMeta();
        $this->migrateTermMeta();
        $this->migrateOptions();
        $this->migrateWhatsAppDefaults();

        // Mark migration as done.
        update_option('hindboutik_acf_migration_complete', true);
    }

    /**
     * Migrate product-level meta (ACF post fields).
     */
    private function migrateProductMeta(): void
    {
        global $wpdb;

        $products = $wpdb->get_col(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('description', 'taille_unique', 'texte_taille_unique')"
        );

        foreach ($products as $productId) {
            foreach (['description', 'taille_unique', 'texte_taille_unique'] as $acfKey) {
                $newKey = self::MIGRATION_MAP[$acfKey][0];
                $existing = get_post_meta($productId, $newKey, true);

                if ($existing !== '' && $existing !== null) {
                    continue; // Already migrated.
                }

                $acfValue = get_post_meta($productId, $acfKey, true);

                if ($acfValue !== '' && $acfValue !== null) {
                    // ACF true_false stores 1 or ""; normalize to int.
                    $value = $acfKey === 'taille_unique' ? (int) $acfValue : $acfValue;
                    update_post_meta($productId, $newKey, $value);
                }
            }
        }
    }

    /**
     * Migrate term-level meta (product_cat background image).
     */
    private function migrateTermMeta(): void
    {
        global $wpdb;

        $terms = $wpdb->get_col(
            "SELECT DISTINCT term_id FROM {$wpdb->termmeta} WHERE meta_key = 'category_background'"
        );

        foreach ($terms as $termId) {
            $existing = get_term_meta($termId, 'hindboutik_category_background', true);

            if ($existing !== '' && $existing !== null) {
                continue;
            }

            $acfValue = get_term_meta($termId, 'category_background', true);

            if ($acfValue !== '' && $acfValue !== null) {
                update_term_meta($termId, 'hindboutik_category_background', $acfValue);
            }
        }
    }

    /**
     * Migrate options-level data.
     */
    private function migrateOptions(): void
    {
        // Size guide data (repeater → reconstructed nested array).
        $this->migrateSizeGuideRepeater();

        // Top bar text.
        $existing = get_option('hindboutik_top_bar_text');
        if (!$existing) {
            $acfValue = get_option('text_top_bar');
            if ($acfValue) {
                update_option('hindboutik_top_bar_text', $acfValue);
            }
        }

        // Top bar icon.
        $existing = get_option('hindboutik_top_bar_icon');
        if (!$existing) {
            $acfValue = get_option('icone');
            if ($acfValue) {
                update_option('hindboutik_top_bar_icon', $acfValue);
            }
        }
    }

    /**
     * Reconstruit la structure imbriquée du repeater ACF "categorie_de_produit"
     * (options page "guide-tailles").
     *
     * ACF n'enregistre PAS un repeater comme un tableau PHP unique en base :
     * il "aplatit" les données en plusieurs lignes wp_options
     * (categorie_de_produit = compteur, categorie_de_produit_0_categorie,
     * categorie_de_produit_0_tableau = compteur du sous-repeater,
     * categorie_de_produit_0_tableau_0_taille, etc.).
     *
     * Un simple get_option('categorie_de_produit') ne renvoie donc que le
     * nombre de lignes (un entier), jamais les données — d'où cette
     * reconstruction manuelle, indispensable pour ne pas perdre le guide
     * des tailles existant lors du passage à des méta natives.
     */
    private function migrateSizeGuideRepeater(): void
    {
        // Idempotent : ne pas écraser une migration/saisie déjà faite.
        if (get_option('hindboutik_size_guide_data')) {
            return;
        }

        $rowCount = (int) get_option('categorie_de_produit', 0);

        if ($rowCount < 1) {
            return;
        }

        $rows = [];

        for ($i = 0; $i < $rowCount; $i++) {
            $categoryIds = get_option("categorie_de_produit_{$i}_categorie", []);
            $categoryIds = is_array($categoryIds) ? array_map('intval', $categoryIds) : array_filter([(int) $categoryIds]);

            $tableRowCount = (int) get_option("categorie_de_produit_{$i}_tableau", 0);
            $table = [];

            for ($j = 0; $j < $tableRowCount; $j++) {
                $table[] = [
                    'taille'    => (string) get_option("categorie_de_produit_{$i}_tableau_{$j}_taille", ''),
                    'taille_fr' => (string) get_option("categorie_de_produit_{$i}_tableau_{$j}_taille_fr", ''),
                ];
            }

            $rows[] = [
                'categories' => $categoryIds,
                'tableau'    => $table,
            ];
        }

        update_option('hindboutik_size_guide_data', $rows);
    }

    /**
     * Set WhatsApp defaults if not present.
     */
    private function migrateWhatsAppDefaults(): void
    {
        if (!get_option('hindboutik_whatsapp_phone')) {
            update_option('hindboutik_whatsapp_phone', '33612345678');
        }
        if (!get_option('hindboutik_whatsapp_message')) {
            update_option('hindboutik_whatsapp_message', 'Bonjour, je souhaite des informations sur un produit.');
        }
        if (!get_option('hindboutik_whatsapp_display_text')) {
            update_option('hindboutik_whatsapp_display_text', 'Une question ?');
        }
    }

    /**
     * Returns true if the migration has already been run.
     */
    public static function isComplete(): bool
    {
        return (bool) get_option('hindboutik_acf_migration_complete', false);
    }

    /**
     * Force re-run the migration (e.g. via WP-CLI).
     */
    public static function reset(): void
    {
        delete_option('hindboutik_acf_migration_complete');
    }
}