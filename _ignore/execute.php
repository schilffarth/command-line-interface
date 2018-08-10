#!/usr/bin/php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (version_compare(PHP_VERSION, '7.2', '<')) {
    echo PHP_EOL . PHP_EOL . 'Could not start the CLI. Your PHP version must be 7 or higher. Your current version: ' . PHP_VERSION . PHP_EOL . PHP_EOL;
    exit;
} elseif (PHP_SAPI !== 'cli') {
    echo PHP_EOL . PHP_EOL . 'Could not start the CLI. It must be run as CLI application.' . PHP_EOL . PHP_EOL;
    exit;
}

try {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    if (basename($argv[0]) === basename(__FILE__)) {
        // First entry is the called file (here it would be $argv[0] = "example-executable.php")
        unset($argv[0]);
    }
    $objectManager = new \Schilffarth\Console\DependencyInjection\ObjectManager();
    /** @var \Schilffarth\Console\Source\App\Application $executor */
    $executor = $objectManager->getSingleton(\Schilffarth\Console\Source\App\Application::class);
    $executor->includeCommandDir(__DIR__ . '/Command', 'Test\Commands');
    $executor->execute($argv);
} catch (Exception $e) {
    echo PHP_EOL . PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL;
    exit;
}

