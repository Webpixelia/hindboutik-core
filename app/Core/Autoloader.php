<?php
declare(strict_types=1);

namespace HindBoutik\Core;

/**
 * PSR-4 autoloader.
 * No external dependencies — works without Composer.
 */
class Autoloader
{
    /** @var array<string, string> */
    private static array $namespaces = [];

    /**
     * Register the autoloader.
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
        self::init();
    }

    /**
     * Add a namespace prefix → base directory mapping.
     */
    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        self::$namespaces[$prefix] = rtrim($baseDir, '/') . '/';
    }

    /**
     * Autoload a class.
     */
    public static function autoload(string $class): void
    {
        foreach (self::$namespaces as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }

    /**
     * Register all plugin namespaces on init.
     */
    public static function init(): void
    {
        $base = HINDBOUTIK_CORE_DIR . 'app/';
        self::addNamespace('HindBoutik\\Core',       $base . 'Core');
        self::addNamespace('HindBoutik\\Acf',         $base . 'Acf');
        self::addNamespace('HindBoutik\\Admin',       $base . 'Admin');
        self::addNamespace('HindBoutik\\Features',    $base . 'Features');
        self::addNamespace('HindBoutik\\Helpers',     $base . 'Helpers');
    }

    // Called from hindBoutik-core.php
    public static function registerAll(): void
    {
        self::init();
    }
}