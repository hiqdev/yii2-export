<?php declare(strict_types=1);

/**
 * PHPUnit bootstrap for this package's own tests. Deliberately does NOT boot
 * the consuming application's multi-tenant web config (host-based, requires
 * a real HTTP request and API credentials) - only the Yii2 framework pieces
 * the export core actually touches: the formatter and a cache component
 * (ExportJobStorage needs Yii::$app->cache).
 */

error_reporting(E_ALL);

defined('YII_ENV') || define('YII_ENV', 'test');
defined('YII_DEBUG') || define('YII_DEBUG', true);
// Let PHPUnit, not Yii, handle PHP errors and deprecations (some come from dependencies).
defined('YII_ENABLE_ERROR_HANDLER') || define('YII_ENABLE_ERROR_HANDLER', false);

// Standalone checkout (own vendor/) or installed into an app
// (vendor/hiqdev/yii2-export/tests -> app root).
$appRoot = is_file(dirname(__DIR__) . '/vendor/autoload.php') ? dirname(__DIR__) : dirname(__DIR__, 4);

require $appRoot . '/vendor/autoload.php';
require $appRoot . '/vendor/yiisoft/yii2/Yii.php';

// The app's Composer autoloader only merges this package's `autoload` section
// (it's a dependency, not the root project), so test-only classes under
// hiqdev\yii2\export\tests\ need their own lightweight PSR-4 mapping here.
spl_autoload_register(static function (string $class): void {
    $prefix = 'hiqdev\\yii2\\export\\tests\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

new yii\console\Application([
    'id' => 'yii2-export-tests',
    'basePath' => dirname(__DIR__),
    'components' => [
        'cache' => ['class' => yii\caching\ArrayCache::class],
        'formatter' => ['class' => yii\i18n\Formatter::class],
        'i18n' => [
            'translations' => [
                'hiqdev.export' => [
                    'class' => yii\i18n\PhpMessageSource::class,
                    'basePath' => dirname(__DIR__) . '/src/messages',
                ],
            ],
        ],
    ],
]);
