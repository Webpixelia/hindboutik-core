<?php
declare(strict_types=1);

namespace HindBoutik\Cli;

use HindBoutik\Admin\FeatureToggleSettings;

/**
 * WP-CLI commands for HindBoutik Core feature toggles.
 */
if (defined('WP_CLI') && WP_CLI) {
    class FeatureCommand
    {
        /**
         * List all features and their status.
         *
         * @when init
         */
        public function __invoke(): void
        {
            \WP_CLI::line('<info>Fonctionnalités HindBoutik Core</info>');
            \WP_CLI::line(str_repeat('-', 60));

            foreach (FeatureToggleSettings::FEATURES as $id => $feature) {
                $enabled = FeatureToggleSettings::isFeatureEnabled($id) ? '<comment>✓ Activé</comment>' : '<error>✗ Désactivé</error>';
                \WP_CLI::line(sprintf("%-25s %-15s %s", $id, $feature['category'], $enabled));
            }
        }
    }

    \WP_CLI::add_command('hindboutik features', __NAMESPACE__ . '\FeatureCommand');

    /**
     * Toggle a single feature.
     *
     * @subcommand toggle <feature_id>
     */
    class ToggleCommand
    {
        public function __invoke(array $args): void
        {
            $featureId = $args[0];

            if (!isset(FeatureToggleSettings::FEATURES[$featureId])) {
                \WP_CLI::error("Feature '$featureId' non trouvée.");
            }

            $currently = FeatureToggleSettings::isFeatureEnabled($featureId);
            $newState = !$currently;

            update_option('hindboutik_feature_enabled_' . $featureId, $newState ? 1 : 0);

            if ($newState) {
                \WP_CLI::success("Feature '$featureId' activée.");
            } else {
                \WP_CLI::success("Feature '$featureId' désactivée.");
            }
        }
    }

    \WP_CLI::add_command('hindboutik toggle', __NAMESPACE__ . '\ToggleCommand');

    /**
     * Enable all features.
     *
     * @subcommand enable-all
     */
    class EnableAllCommand
    {
        public function __invoke(): void
        {
            FeatureToggleSettings::enableAll();
            \WP_CLI::success('Toutes les fonctionnalités sont activées.');
        }
    }

    \WP_CLI::add_command('hindboutik enable-all', __NAMESPACE__ . '\EnableAllCommand');

    /**
     * Disable all features.
     *
     * @subcommand disable-all
     */
    class DisableAllCommand
    {
        public function __invoke(): void
        {
            FeatureToggleSettings::disableAll();
            \WP_CLI::success('Toutes les fonctionnalités sont désactivées.');
        }
    }

    \WP_CLI::add_command('hindboutik disable-all', __NAMESPACE__ . '\DisableAllCommand');

    /**
     * (Re)lance la migration ACF -> méta natives.
     *
     * @subcommand migrate
     */
    class MigrateCommand
    {
        /**
         * @param array $args
         * @param array $assocArgs Peut contenir --force pour ré-écraser les valeurs déjà migrées.
         */
        public function __invoke(array $args, array $assocArgs): void
        {
            $force = isset($assocArgs['force']);

            if ($force) {
                \WP_CLI::log('Option --force : les options déjà migrées seront écrasées.');
                delete_option('hindboutik_size_guide_data');
                delete_option('hindboutik_top_bar_text');
                delete_option('hindboutik_top_bar_icon');
            }

            $migration = new \HindBoutik\Acf\Migration();
            $migration->run();

            \WP_CLI::success('Migration ACF -> natif exécutée.');

            $sizeGuide = get_option('hindboutik_size_guide_data');
            if ($sizeGuide) {
                \WP_CLI::log(sprintf('hindboutik_size_guide_data : %d ligne(s) migrée(s).', count($sizeGuide)));
            } else {
                \WP_CLI::warning("hindboutik_size_guide_data toujours vide après migration : vérifie que l'option ACF 'categorie_de_produit' existe bien en base (wp option get categorie_de_produit).");
            }
        }
    }

    \WP_CLI::add_command('hindboutik migrate', __NAMESPACE__ . '\MigrateCommand');
}