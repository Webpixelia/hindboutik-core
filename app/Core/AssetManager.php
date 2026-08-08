<?php
declare(strict_types=1);

namespace HindBoutik\Core;

use HindBoutik\Helpers\TemplateLoader; 

/**
 * Centralized asset registry.
 *
 * Usage in features:
 *   $assets = AssetManager::getInstance();
 *   $assets->enqueueJs('crosssell-carousel');
 *   $assets->enqueueCss('crosssell-carousel');
 */
class AssetManager
{
    private static ?AssetManager $instance = null;

    /** @var array<string, array> Registered JS asset definitions.  */
    private array $registeredJs   = [];
    /** @var array<string, array> Registered CSS asset definitions. */
    private array $registeredCss  = [];
    /** @var array<string, array> Registered admin JS definitions.   */
    private array $registeredAdminJs  = [];
    /** @var array<string, array> Registered admin CSS definitions.  */
    private array $registeredAdminCss = [];

    private TemplateLoader $templateLoader;

    private function __construct()
    {
        $this->templateLoader = new TemplateLoader();   // ← plus besoin du FQN
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function getTemplateLoader(): TemplateLoader
    {
        return $this->templateLoader;
    }

    // ── Registration ──────────────────────────────────────────────

    public function registerJs(string $handle, string $path, array $deps = [], string $ver = '1.0.0'): void
    {
        $this->registeredJs[$handle] = [
            'handle' => $handle,
            'src'    => HINDBOUTIK_CORE_URL . 'assets/js/' . ltrim($path, '/'),
            'deps'   => $deps,
            'ver'    => $ver,
        ];
    }

    public function registerCss(string $handle, string $path, array $deps = [], string $ver = '1.0.0'): void
    {
        $this->registeredCss[$handle] = [
            'handle' => $handle,
            'src'    => HINDBOUTIK_CORE_URL . 'assets/css/' . ltrim($path, '/'),
            'deps'   => $deps,
            'ver'    => $ver,
        ];
    }

    public function registerAdminJs(string $handle, string $path, array $deps = [], string $ver = '1.0.0'): void
    {
        $this->registeredAdminJs[$handle] = [
            'handle' => $handle,
            'src'    => HINDBOUTIK_CORE_URL . 'assets/js/' . ltrim($path, '/'),
            'deps'   => $deps,
            'ver'    => $ver,
        ];
    }

    public function registerAdminCss(string $handle, string $path, array $deps = [], string $ver = '1.0.0'): void
    {
        $this->registeredAdminCss[$handle] = [
            'handle' => $handle,
            'src'    => HINDBOUTIK_CORE_URL . 'assets/css/' . ltrim($path, '/'),
            'deps'   => $deps,
            'ver'    => $ver,
        ];
    }

    // ── Enqueue (called by individual features) ──────────────────

    public function enqueueJs(string $handle): void
    {
        if (isset($this->registeredJs[$handle])) {
            $a = $this->registeredJs[$handle];
            wp_enqueue_script($a['handle'], $a['src'], $a['deps'], $a['ver'], true);
        }
    }

    public function enqueueCss(string $handle): void
    {
        if (isset($this->registeredCss[$handle])) {
            $a = $this->registeredCss[$handle];
            wp_enqueue_style($a['handle'], $a['src'], $a['deps'], $a['ver']);
        }
    }

    public function enqueueAdminJs(string $handle): void
    {
        if (isset($this->registeredAdminJs[$handle])) {
            $a = $this->registeredAdminJs[$handle];
            wp_enqueue_script($a['handle'], $a['src'], $a['deps'], $a['ver'], true);
        }
    }

    public function enqueueAdminCss(string $handle): void
    {
        if (isset($this->registeredAdminCss[$handle])) {
            $a = $this->registeredAdminCss[$handle];
            wp_enqueue_style($a['handle'], $a['src'], $a['deps'], $a['ver']);
        }
    }

    // ── Bulk registration (called once in Plugin::init) ───────────

    public function registerAllAssets(): void
    {
        // Frontend JS
        $this->registerJs('read-more',            'frontend/read-more.js',            ['jquery'], HINDBOUTIK_CORE_VERSION);
        $this->registerJs('account-page',         'frontend/account-page.js',         ['jquery'], HINDBOUTIK_CORE_VERSION);
        $this->registerJs('free-shipping-label',  'frontend/free-shipping-label.js',  [],         HINDBOUTIK_CORE_VERSION);
        $this->registerJs('toggle-menu',          'frontend/toggle-menu.js',          ['jquery'], HINDBOUTIK_CORE_VERSION);
        $this->registerJs('crosssell-carousel',   'crosssell-carousel.js',           ['jquery', 'swiper-js'], HINDBOUTIK_CORE_VERSION);
        $this->registerJs('modal-size-guide',     'modal-size-guide.js',             ['jquery'], HINDBOUTIK_CORE_VERSION);
        $this->registerJs('product-slider',       'product-slider.js',               ['jquery', 'slick-js'], HINDBOUTIK_CORE_VERSION);
        $this->registerJs('plus-minus',           'plus-minus.js',                   ['jquery'], HINDBOUTIK_CORE_VERSION);
        $this->registerJs('whatsapp-widget',      'frontend/whatsapp-widget.js',     ['jquery'], HINDBOUTIK_CORE_VERSION);

        // Frontend CSS
        $this->registerCss('crosssell-carousel',  'crosssell-carousel.css',   ['swiper-css'], HINDBOUTIK_CORE_VERSION);
        $this->registerCss('size-guide-modal',    'size-guide-modal.css',     [], HINDBOUTIK_CORE_VERSION);
        $this->registerCss('product-image-grid',  'product-image-grid.css',   [], HINDBOUTIK_CORE_VERSION);
        $this->registerCss('whatsapp-widget',     'whatsapp-widget.css',      [], HINDBOUTIK_CORE_VERSION);
        $this->registerCss('read-more',           'frontend/read-more.css',   [], HINDBOUTIK_CORE_VERSION);
        $this->registerCss('progress-bar',        'frontend/progress-bar.css',  [], HINDBOUTIK_CORE_VERSION);
        $this->registerCss('color-swatches',      'frontend/color-swatches.css', [], HINDBOUTIK_CORE_VERSION);
        $this->registerCss('after-message',       'frontend/after-message.css', [], HINDBOUTIK_CORE_VERSION);

        // Admin JS
        $this->registerAdminJs('size-guide-admin', 'admin/size-guide-admin.js', ['jquery', 'wp-i18n', 'wp-element'], HINDBOUTIK_CORE_VERSION);
        $this->registerAdminJs('top-bar-admin',    'admin/top-bar-admin.js',    ['jquery', 'wp-i18n', 'wp-element'], HINDBOUTIK_CORE_VERSION);

        // Admin CSS
        $this->registerAdminCss('size-guide-admin', 'admin/size-guide-admin.css', [], HINDBOUTIK_CORE_VERSION);
        $this->registerAdminCss('top-bar-admin',    'admin/top-bar-admin.css',    [], HINDBOUTIK_CORE_VERSION);
    }
}