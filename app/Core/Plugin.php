<?php
declare(strict_types=1);

namespace HindBoutik\Core;

use HindBoutik\Acf\MetaManager;
use HindBoutik\Admin\SettingsPage;
use HindBoutik\Admin\SizeGuideSettings;
use HindBoutik\Admin\TopBarSettings;
use HindBoutik\Admin\FeatureToggleSettings;

class Plugin
{
    private static ?Plugin $instance = null;

    /** @var FeatureInterface[] */
    private array $features = [];

    private AssetManager $assets;

    private function __construct()
    {
        add_action('plugins_loaded', [$this, 'init']);
        register_activation_hook(HINDBOUTIK_CORE_BASENAME, [$this, 'activate']);
        register_deactivation_hook(HINDBOUTIK_CORE_BASENAME, [$this, 'deactivate']);
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Activation : initialise les options de toggle à true pour toutes les features.
     */
    public function activate(): void
    {
        // Initialize all feature toggles to default (enabled).
        FeatureToggleSettings::enableAll();

        // Run ACF → native migration.
        if (!defined('WP_CLI') && !wp_doing_cron() && !wp_doing_ajax()) {
            $migration = new \HindBoutik\Acf\Migration();
            $migration->run();
        }
    }

    public function deactivate(): void
    {
        wp_clear_scheduled_hook('hindboutik_core_daily_cleanup');
    }

    public function init(): void
    {
        $this->assets = AssetManager::getInstance();
        $this->assets->registerAllAssets();

        // --- ACF replacement ---
        (new MetaManager())->register();

        // --- Admin pages ---
        new SettingsPage();
        (new SizeGuideSettings())->register();
        (new TopBarSettings())->register();
        (new FeatureToggleSettings())->register();

        // --- Register features (conditionally) ---
        foreach (FeatureToggleSettings::FEATURES as $id => $config) {
            // Check if this feature is enabled.
            if (!FeatureToggleSettings::isFeatureEnabled($id)) {
                continue;
            }

            // Instantiate and register.
            $featureClass = $config['class'];
            if (class_exists($featureClass)) {
                $this->registerFeature(new $featureClass());
            }
        }

        // Le bandeau top bar est un bloc statique du Header Builder Divi :
        // même désactivé, TopBarFeature::register() n'est jamais appelé
        // (boucle ci-dessus), donc le masquage doit être hooké ici,
        // inconditionnellement, et vérifier l'état à l'affichage.
        add_action('wp_head', [__CLASS__, 'maybeHideDiviTopBar']);

        // Text domain.
        load_plugin_textdomain('hindboutik-core', false, dirname(HINDBOUTIK_CORE_BASENAME) . '/languages');

        // CLI
        if (defined('WP_CLI') && WP_CLI) {
            require_once HINDBOUTIK_CORE_DIR . 'app/Cli/FeatureCommand.php';
        }
    }

    private function registerFeature(FeatureInterface $feature): void
    {
        $feature->register();
        $this->features[] = $feature;
    }

    public function getFeature(string $className): ?FeatureInterface
    {
        foreach ($this->features as $feature) {
            if ($feature instanceof $className) {
                return $feature;
            }
        }
        return null;
    }

    /**
     * Check if a feature is enabled (static helper for use anywhere).
     */
    public static function isFeatureEnabled(string $id): bool
    {
        return FeatureToggleSettings::isFeatureEnabled($id);
    }

    /**
     * Masque le bandeau top bar Divi (#top_bar) si la feature 'top_bar'
     * est désactivée. Le bloc est un module statique du Header Builder
     * Divi, pas un rendu du plugin : on ne peut agir que via CSS.
     */
    public static function maybeHideDiviTopBar(): void
    {
        if (self::isFeatureEnabled('top_bar')) {
            return;
        }

        echo '<style id="hindboutik-top-bar-hide">#top_bar{display:none!important;height:0!important;overflow:hidden!important;}</style>';
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function getAssets(): AssetManager
    {
        return $this->assets;
    }
}