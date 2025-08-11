<?php

/**
 * PHPUnit bootstrap file
 *
 * This file intentionally mixes declarations and side effects as it needs to
 * configure the testing environment before defining any symbols.
 *
 * @package Pantheon\ContentPublisher
 * @phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
 */

$_tests_dir = getenv('WP_TESTS_DIR');
if (! $_tests_dir) {
  $_tests_dir = '/tmp/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writing to STDERR in bootstrap before WP is loaded
  fwrite(STDERR, "Could not find WordPress tests bootstrap in " . $_tests_dir . "\n");
  exit(1);
}

// Preload Composer autoloader and Yoast PHPUnit Polyfills if available
$projectRoot = dirname(__DIR__, 2);
$vendorAutoload = $projectRoot . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
  require $vendorAutoload;
}
$yoastPolyfills = $projectRoot . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if (file_exists($yoastPolyfills) && !defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
  define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $yoastPolyfills);
}

require $_tests_dir . '/includes/functions.php';

if (function_exists('tests_add_filter')) {
  tests_add_filter('muplugins_loaded', function () use ($projectRoot) {
    require $projectRoot . '/pantheon-content-publisher-for-wordpress.php';
  });
}

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
