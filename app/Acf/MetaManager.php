<?php
declare(strict_types=1);

namespace HindBoutik\Acf;

use WP_Post;

/**
 * Registers native meta boxes, taxonomy form fields, and settings
 * pages that replace ACF's field groups and options pages.
 */
class MetaManager
{
    /**
     * Register everything.
     */
    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'registerProductMetaBoxes']);
        add_action('save_post_product', [$this, 'saveProductMeta'], 10, 3);
        add_action('edit_product_cat_form_fields', [$this, 'categoryBackgroundField']);
        add_action('edited_product_cat', [$this, 'saveCategoryBackground'], 10, 1);

        // Register meta for REST / Gutenberg.
        $this->registerRestMeta();
    }

    /* -----------------------------------------------------------------
     *  Product meta boxes (replaces ACF "Détails produit" group)
     * ----------------------------------------------------------------- */

    public function registerProductMetaBoxes(): void
    {
        add_meta_box(
            'hindboutik_product_details',
            __('Détails produit', 'hindboutik-core'),
            [$this, 'renderProductDetailsMetaBox'],
            'product',
            'normal',
            'default'
        );
    }

    public function renderProductDetailsMetaBox(WP_Post $post): void
    {
        $postId = $post->ID;

        $description       = MetaApi::getField('description', $postId);
        $tailleUnique      = MetaApi::getField('taille_unique', $postId);
        $texteTailleUnique = MetaApi::getField('texte_taille_unique', $postId);

        // Build a nonce for verification on save.
        wp_nonce_field('hindboutik_save_product_details', 'hindboutik_product_details_nonce');

        echo '<table class="form-table">';

        // Description (WYSIWYG replacement — use wp_editor)
        echo '<tr><th>' . esc_html(MetaApi::getFieldLabel('description')) . '</th><td>';
        wp_editor(
            $description ?: '',
            'hindboutik_product_description',
            ['textarea_rows' => 5, 'media_buttons' => false]
        );
        echo '</td></tr>';

        // Taille unique (checkbox)
        echo '<tr><th>' . esc_html(MetaApi::getFieldLabel('taille_unique')) . '</th><td>';
        echo '<label><input type="checkbox" name="hindboutik_product_taille_unique" value="1" ' . checked(1, (int) $tailleUnique, false) . '>' . esc_html__('Cochez si taille unique', 'hindboutik-core') . '</label>';
        echo '</td></tr>';

        // Texte taille unique (textarea)
        echo '<tr><th>' . esc_html(MetaApi::getFieldLabel('texte_taille_unique')) . '</th><td>';
        echo '<textarea name="hindboutik_product_texte_taille_unique" rows="3" class="widefat">' . esc_textarea($texteTailleUnique ?: '') . '</textarea>';
        echo '</td></tr>';

        echo '</table>';
    }

    public function saveProductMeta(int $postId, object $post, bool $update): void
    {
        // Verify nonce.
        if (!isset($_POST['hindboutik_product_details_nonce']) ||
            !wp_verify_nonce($_POST['hindboutik_product_details_nonce'], 'hindboutik_save_product_details')) {
            return;
        }

        // Verify current user capabilities.
        if (!current_user_can('edit_product', $postId)) {
            return;
        }

        // Don't store autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Description (WYSIWYG).
        if (isset($_POST['hindboutik_product_description'])) {
            update_post_meta($postId, 'hindboutik_product_description', wp_kses_post($_POST['hindboutik_product_description']));
        }

        // Taille unique (checkbox).
        $tailleUnique = isset($_POST['hindboutik_product_taille_unique']) ? 1 : 0;
        update_post_meta($postId, 'hindboutik_product_taille_unique', $tailleUnique);

        // Texte taille unique (textarea).
        if (isset($_POST['hindboutik_product_texte_taille_unique'])) {
            update_post_meta($postId, 'hindboutik_product_texte_taille_unique', sanitize_textarea_field($_POST['hindboutik_product_texte_taille_unique']));
        }
    }

    /* -----------------------------------------------------------------
     *  Taxonomy meta (replaces ACF "Category background" group)
     * ----------------------------------------------------------------- */

    public function categoryBackgroundField(): void
    {
        $term = get_queried_object();
        if (!$term) {
            return;
        }
        $value = get_term_meta($term->term_id, 'hindboutik_category_background', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="hindboutik_category_background"><?php esc_html_e('Category background', 'hindboutik-core'); ?></label></th>
            <td>
                <input type="hidden" name="hindboutik_category_background" id="hindboutik_category_background_input" value="<?php echo esc_attr($value ?: ''); ?>">
                <button type="button" class="button hindboutik-select-image"><?php esc_html_e('Select image', 'hindboutik-core'); ?></button>
                <span class="hindboutik-preview"></span>
                <script>
                jQuery(document).ready(function($){
                    $('.hindboutik-select-image').on('click', function(e){
                        e.preventDefault();
                        var input = $('#hindboutik_category_background_input');
                        var frame = wp.media({
                            title: '<?php esc_attr_e('Select an image', 'hindboutik-core'); ?>',
                            button: '<?php esc_attr_e('Use this image', 'hindboutik-core'); ?>',
                            multiple: false
                        });
                        frame.on('select', function(){
                            var attachment = frame.state().get('selection').first().toJSON();
                            input.val(attachment.id);
                            $('.hindboutik-preview').html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:60px;"/>');
                        });
                        frame.open();
                    });
                });
                </script>
            </td>
        </tr>
        <?php
    }

    public function saveCategoryBackground(int $termId): void
    {
        if (isset($_POST['hindboutik_category_background'])) {
            update_term_meta($termId, 'hindboutik_category_background', (int) $_POST['hindboutik_category_background']);
        }
    }

    /* -----------------------------------------------------------------
     *  REST / Gutenberg meta registration
     * ----------------------------------------------------------------- */

    private function registerRestMeta(): void
    {
        register_meta('post', 'hindboutik_product_description', [
            'object_subtype' => 'product',
            'show_in_rest'   => true,
            'single'         => true,
            'type'          => 'string',
        ]);
        register_meta('post', 'hindboutik_product_taille_unique', [
            'object_subtype' => 'product',
            'show_in_rest'   => true,
            'single'         => true,
            'type'          => 'integer',
        ]);
        register_meta('post', 'hindboutik_product_texte_taille_unique', [
            'object_subtype' => 'product',
            'show_in_rest'   => true,
            'single'         => true,
            'type'          => 'string',
        ]);
        register_meta('term', 'hindboutik_category_background', [
            'object_subtype' => 'product_cat',
            'show_in_rest'   => true,
            'single'         => true,
            'type'          => 'integer',
        ]);
    }
}