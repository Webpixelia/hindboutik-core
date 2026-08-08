<?php
declare(strict_types=1);

namespace HindBoutik\Admin;

use HindBoutik\Core\AssetManager;

/**
 * Settings page for the size guide (replaces ACF options page "guide-tailles").
 *
 * Uses the Settings API with a JavaScript-powered repeater for the
 * nested "categorie → tableau" structure.
 */
class SizeGuideSettings
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'hindboutik-settings',
            __('Guide des tailles', 'hindboutik-core'),
            __('Guide des tailles', 'hindboutik-core'),
            'manage_options',
            'hindboutik-size-guide',
            [$this, 'render']
        );
    }

    public function registerSettings(): void
    {
        register_setting('hindboutik_size_guide', 'hindboutik_size_guide_data', [
            'sanitize_callback' => [$this, 'sanitize'],
        ]);

        add_settings_section(
            'hindboutik_size_guide_section',
            '',
            null,
            'hindboutik-size-guide'
        );

        add_settings_field(
            'hindboutik_size_guide_data',
            __('Guide des tailles', 'hindboutik-core'),
            [$this, 'renderRepeaterField'],
            'hindboutik-size-guide',
            'hindboutik_size_guide_section'
        );
    }

    /**
     * Sanitize the size guide data structure.
     */
    public function sanitize($input): array
    {
            // Vérifier si c'est une chaîne JSON
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($input)) {
            return [];
        }

        $clean = [];
        foreach ($input as $row) {
            if (!isset($row['categories']) || !is_array($row['categories'])) {
                continue;
            }
            $categories = array_map('intval', $row['categories']);
            $tableau = [];
            if (isset($row['tableau']) && is_array($row['tableau'])) {
                foreach ($row['tableau'] as $tableRow) {
                    $tableau[] = [
                        'taille'    => sanitize_text_field($tableRow['taille'] ?? ''),
                        'taille_fr' => sanitize_text_field($tableRow['taille_fr'] ?? ''),
                    ];
                }
            }
            $clean[] = [
                'categories' => $categories,
                'tableau'    => $tableau,
            ];
        }

        update_option('hindboutik_test', 'ok');

        return $clean;
    }

    /**
     * Render the repeater UI with hidden input + JS.
     */
    public function renderRepeaterField(): void
    {
        $data = get_option('hindboutik_size_guide_data', []);
        $categories = get_categories(['taxonomy' => 'product_cat', 'hide_empty' => false]);

        // Hidden input that stores the JSON.
        echo '<input type="hidden" name="hindboutik_size_guide_data" id="hindboutik_size_guide_data_input">';

        echo '<div id="hindboutik-size-guide-repeater" class="hindboutik-repeater">';

        if (empty($data)) {
            echo '<p class="description">' . esc_html__('Aucun guide de taille configuré.', 'hindboutik-core') . '</p>';
        }

        foreach ($data as $rowIndex => $row) {
            $this->renderCategoryRow($rowIndex, $row, $categories);
        }

        echo '</div>';

        echo '<button type="button" id="hindboutik-add-category" class="button button-primary">' .
            esc_html__('Ajouter une catégorie', 'hindboutik-core') . '</button>';
        
        // Plus besoin de script inline ici !
    }

    /**
     * Render a single category row in the repeater.
     */
    private function renderCategoryRow(int $rowIndex, array $row, array $categories): void
    {
        $selectedCats = $row['categories'] ?? [];
        $tableau = $row['tableau'] ?? [];
        ?>
        <div class="hindboutik-repeater-row" data-index="<?php echo $rowIndex; ?>">
            <h3><?php esc_html_e('Catégorie', 'hindboutik-core'); ?> #<?php echo $rowIndex + 1; ?>
                <button type="button" class="hindboutik-remove-row dashicons dashicons-dismiss"></button>
            </h3>
            <select name="hindboutik_row_<?php echo $rowIndex; ?>_categories[]" multiple style="width:100%;height:150px;">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat->term_id; ?>" <?php
                        selected(in_array((int) $cat->term_id, array_map('intval', $selectedCats), true), true);
                    ?>><?php echo esc_html($cat->name); ?></option>
                <?php endforeach; ?>
            </select>

            <h4><?php esc_html_e('Tableau des tailles', 'hindboutik-core'); ?></h4>
            <table class="hindboutik-size-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Taille', 'hindboutik-core'); ?></th>
                        <th><?php esc_html_e('Taille FR', 'hindboutik-core'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableau as $tableIdx => $tableRow): ?>
                    <tr class="hindboutik-table-row"> <!-- ← AJOUTER LA CLASSE ICI -->
                        <td>
                            <select name="hindboutik_row_<?php echo $rowIndex; ?>_tableau_<?php echo $tableIdx; ?>[taille]">
                                <?php
                                $sizes = ['S', 'M', 'L', 'XL', 'XXL', '3XL', 'Taille unique'];
                                foreach ($sizes as $size):
                                    ?>
                                    <option value="<?php echo esc_attr($size); ?>" <?php echo selected($tableRow['taille'] ?? '', $size, false); ?>>
                                        <?php echo esc_html($size); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="hindboutik_row_<?php echo $rowIndex; ?>_tableau_<?php echo $tableIdx; ?>[taille_fr]"
                                value="<?php echo esc_attr($tableRow['taille_fr'] ?? ''); ?>" class="small-text">
                        </td>
                        <td>
                            <button type="button" class="hindboutik-remove-table-row dashicons dashicons-dismiss"></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="hindboutik-add-table-row button button-secondary"><?php esc_html_e('Ajouter une taille', 'hindboutik-core'); ?></button>
        </div>
        <?php
    }

    public function render(): void
    {
        // 1. Enregistrer et enqueuer le script avec le bon handle
        $assets = AssetManager::getInstance();
        $assets->enqueueAdminJs('size-guide-admin');  // ← Le handle EST 'size-guide-admin'
        $assets->enqueueAdminCss('size-guide-admin');
        
        // 2. Localiser les données avec LE MÊME handle
        wp_localize_script('size-guide-admin', 'hindboutikSizeGuideData', [  // ← PREMIER paramètre = 'size-guide-admin'
            'categories' => $this->getProductCategoriesData(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hindboutik_size_guide_nonce'),
        ]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Guide des tailles', 'hindboutik-core'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('hindboutik_size_guide');
                do_settings_sections('hindboutik-size-guide');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Retourne les catégories produits au format JSON pour le script admin.
     */
    private function getProductCategoriesJson(): string
    {
        $categories = get_categories([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ]);

        $result = [];
        foreach ($categories as $cat) {
            $result[] = ['id' => (int) $cat->term_id, 'name' => $cat->name];
        }

        return json_encode($result);
    }

    /**
     * Retourne les données des catégories produits pour le script admin.
     */
    private function getProductCategoriesData(): array
    {
        $categories = get_categories([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ]);

        $result = [];
        foreach ($categories as $cat) {
            $result[] = [
                'id' => (int) $cat->term_id,
                'name' => $cat->name,
            ];
        }

        return $result;
    }
}