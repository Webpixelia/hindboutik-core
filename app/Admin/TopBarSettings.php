<?php
declare(strict_types=1);

namespace HindBoutik\Admin;

/**
 * Settings page for the top bar (replaces ACF options page "top-bar").
 */
class TopBarSettings
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
            __('Top bar', 'hindboutik-core'),
            __('Top bar', 'hindboutik-core'),
            'manage_options',
            'hindboutik-top-bar',
            [$this, 'render']
        );
    }

    public function registerSettings(): void
    {
        register_setting('hindboutik_top_bar', 'hindboutik_top_bar_text');
        register_setting('hindboutik_top_bar', 'hindboutik_top_bar_icon');

        add_settings_section('hindboutik_top_bar_section', '', null, 'hindboutik-top-bar');

        add_settings_field(
            'hindboutik_top_bar_text',
            __('Texte du bandeau', 'hindboutik-core'),
            [$this, 'renderEditor'],
            'hindboutik-top-bar',
            'hindboutik_top_bar_section'
        );

        add_settings_field(
            'hindboutik_top_bar_icon',
            __('Icône', 'hindboutik-core'),
            [$this, 'renderImageField'],
            'hindboutik-top-bar',
            'hindboutik_top_bar_section'
        );
    }

    public function renderEditor(): void
    {
        $content = get_option('hindboutik_top_bar_text', '');
        wp_editor($content, 'hindboutik_top_bar_text', ['textarea_rows' => 5, 'media_buttons' => true]);
    }

    public function renderImageField(): void
    {
        $value = get_option('hindboutik_top_bar_icon', '');
        $preview = '';
        if ($value) {
            $preview = wp_get_attachment_image((int) $value, 'thumbnail');
        }
        echo '<input type="hidden" name="hindboutik_top_bar_icon" id="hindboutik_top_bar_icon_input" value="' . esc_attr($value) . '">';
        echo '<button type="button" class="button hindboutik-select-icon">' . esc_html__('Sélectionner une icône', 'hindboutik-core') . '</button>';
        echo '<span class="hindboutik-icon-preview">' . $preview . '</span>';
        echo '<script>
        jQuery(document).ready(function($){
            $(".hindboutik-select-icon").on("click", function(e){
                e.preventDefault();
                var input = $("#hindboutik_top_bar_icon_input");
                var frame = wp.media({title: "Sélectionner une icône", button: "Utiliser cette icône", multiple: false});
                frame.on("select", function(){
                    var attachment = frame.state().get("selection").first().toJSON();
                    input.val(attachment.id);
                    $(".hindboutik-icon-preview").html("<img src=\'" + attachment.sizes.thumbnail.url + "\' style=\'max-width:50px;\'/>");
                });
                frame.open();
            });
        });
        </script>';
    }

    public function render(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Top bar', 'hindboutik-core'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('hindboutik_top_bar');
                do_settings_sections('hindboutik-top-bar');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}