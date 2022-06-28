<?php
/**
 * @link https://robo.li/framework/#creating-a-standalone-phar-with-robo
 */

const WORKING_DIR = __DIR__;

$pharPath = Phar::running('returnPhar');

$appName              = "WPLib CLI";
$appVersion           = trim(file_get_contents(__DIR__ . '/VERSION'));
$commandClasses       = [\WPLib_CLI\Commands\RoboFile::class];
$selfUpdateRepository = 'clubdeuce/wplib-cli';
$configurationFile    = 'wplib.yml';

// If we're running from phar load the phar autoload file.
$pharPath = \Phar::running(true);

do {
    if ($pharPath) {
        $autoloaderPath = "$pharPath/vendor/autoload.php";
        break;
    }

    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        $autoloaderPath = __DIR__ . '/vendor/autoload.php';
        break;
    }

    die("Could not find autoloader. Run 'composer install'.");
} while (false);

$classLoader    = require_once $autoloaderPath;
$statusCode     = (new \Robo\Runner($commandClasses))
    ->setClassLoader($classLoader)
    ->execute(
        $argv,
        $appName,
        $appVersion,
        new \Symfony\Component\Console\Output\ConsoleOutput()
    );
exit($statusCode);
