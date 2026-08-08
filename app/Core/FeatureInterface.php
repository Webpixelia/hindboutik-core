<?php
declare(strict_types=1);

namespace HindBoutik\Core;

/**
 * Interface for every feature class.
 * One class = one feature. Business logic is separated from rendering.
 */
interface FeatureInterface
{
    /**
     * Register all WordPress hooks (actions / filters) for this feature.
     */
    public function register(): void;

    /**
     * Remove all previously registered hooks.
     * Useful when the feature needs to be toggled off dynamically.
     */
    public function unregister(): void;
}