<?php
declare(strict_types=1);

namespace HindBoutik\Admin;

/**
 * Main HindBoutik Core settings page.
 * Contains WhatsApp configuration and general settings.
 */
class SettingsPage
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            __('HindBoutik Core', 'hindboutik-core'),
            __('HindBoutik', 'hindboutik-core'),
            'manage_options',
            'hindboutik-settings',
            [$this, 'render'],
            'dashicons-admin-plugins',
            81
        );

        add_submenu_page(
            'hindboutik-settings',
            __('HindBoutik Core', 'hindboutik-core'),
            __('Réglages', 'hindboutik-core'),
            'manage_options',
            'hindboutik-settings',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        // If migration hasn't run, offer a button.
        $migrationDone = \HindBoutik\Acf\Migration::isComplete();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('HindBoutik Core — Réglages', 'hindboutik-core'); ?></h1>

            <?php if (!$migrationDone): ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('La migration ACF vers les méta natives n’a pas encore été exécutée.', 'hindboutik-core'); ?></p>
                    <?php
                        wp_nonce_field('hindboutik_run_migration', 'hindboutik_migration_nonce');
                        submit_button(__('Lancer la migration ACF', 'hindboutik-core'), 'primary', 'hindboutik_run_migration', false);
                    ?>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e('Migration ACF terminée. Les données sont stockées dans les méta natifs WordPress.', 'hindboutik-core'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('hindboutik_whatsapp_settings');
                do_settings_sections('hindboutik-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function registerSettings(): void
    {
        register_setting('hindboutik_whatsapp_settings', 'hindboutik_whatsapp_phone');
        register_setting('hindboutik_whatsapp_settings', 'hindboutik_whatsapp_message');
        register_setting('hindboutik_whatsapp_settings', 'hindboutik_whatsapp_display_text');
        register_setting('hindboutik_whatsapp_settings', 'hindboutik_whatsapp_enabled');

        add_settings_section(
            'hindboutik_whatsapp_section',
            __('Widget WhatsApp', 'hindboutik-core'),
            null,
            'hindboutik-settings'
        );

        add_settings_field(
            'hindboutik_whatsapp_enabled',
            __('Activer le widget', 'hindboutik-core'),
            [$this, 'fieldToggle'],
            'hindboutik-settings',
            'hindboutik_whatsapp_section'
        );

        add_settings_field(
            'hindboutik_whatsapp_phone',
            __('Numéro de téléphone', 'hindboutik-core'),
            [$this, 'fieldText'],
            'hindboutik-settings',
            'hindboutik_whatsapp_section',
            ['label_for' => 'hindboutik_whatsapp_phone', 'option' => 'hindboutik_whatsapp_phone']
        );

        add_settings_field(
            'hindboutik_whatsapp_message',
            __('Message pré-rempli', 'hindboutik-core'),
            [$this, 'fieldTextarea'],
            'hindboutik-settings',
            'hindboutik_whatsapp_section',
            ['label_for' => 'hindboutik_whatsapp_message', 'option' => 'hindboutik_whatsapp_message']
        );

        add_settings_field(
            'hindboutik_whatsapp_display_text',
            __('Texte d’affichage', 'hindboutik-core'),
            [$this, 'fieldText'],
            'hindboutik-settings',
            'hindboutik_whatsapp_section',
            ['label_for' => 'hindboutik_whatsapp_display_text', 'option' => 'hindboutik_whatsapp_display_text']
        );
    }

    public function fieldText(array $args): void
    {
        $value = get_option($args['option'], '');
        echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['option']) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function fieldTextarea(array $args): void
    {
        $value = get_option($args['option'], '');
        echo '<textarea id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['option']) . '" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
    }

    public function fieldToggle(array $args): void
    {
        $value = get_option('hindboutik_whatsapp_enabled', 1);
        echo '<input type="checkbox" id="' . esc_attr($args['label_for']) . '" name="hindboutik_whatsapp_enabled" value="1" ' . checked(1, (int) $value, false) . '>';
    }
}