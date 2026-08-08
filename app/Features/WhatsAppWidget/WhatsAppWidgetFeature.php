<?php
declare(strict_types=1);

namespace HindBoutik\Features\WhatsAppWidget;

use HindBoutik\Core\AssetManager;
use HindBoutik\Core\FeatureInterface;
use HindBoutik\Helpers\TemplateLoader;

/**
 * WhatsApp floating widget — displayed on all frontend pages
 * at bottom-right.
 *
 * Replaces the third-party "HT CTC" plugin that managed the widget.
 */
class WhatsAppWidgetFeature implements FeatureInterface
{
    private TemplateLoader $templates;

    public function __construct()
    {
        $this->templates = new TemplateLoader();
    }

    public function register(): void
    {
        // Guard clause : ne rien faire si la feature est désactivée.
        if (!\HindBoutik\Core\Plugin::isFeatureEnabled('whatsapp_widget')) {
            return;
        }

        add_action('wp_footer', [$this, 'renderWidget']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function unregister(): void
    {
        remove_action('wp_footer', [$this, 'renderWidget']);
        remove_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        if (is_admin() || !get_option('hindboutik_whatsapp_enabled', 1)) {
            return;
        }
        $assets = AssetManager::getInstance();
        $assets->enqueueCss('whatsapp-widget');
        $assets->enqueueJs('whatsapp-widget');
    }

    public function renderWidget(): void
    {
        if (is_admin() || !get_option('hindboutik_whatsapp_enabled', 1)) {
            return;
        }

        $phone           = get_option('hindboutik_whatsapp_phone', '33612345678');
        $message         = get_option('hindboutik_whatsapp_message', 'Bonjour, je souhaite des informations sur un produit.');
        $displayText     = get_option('hindboutik_whatsapp_display_text', 'Une question ?');
        $whatsappUrl     = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) . '?text=' . urlencode($message);

        echo $this->templates->render('whatsapp-widget', [
            'whatsapp_url'   => $whatsappUrl,
            'display_text'   => $displayText,
            'icon_url'       => HINDBOUTIK_CORE_URL . 'assets/img/whatsapp-icon.png',
        ]);
    }
}