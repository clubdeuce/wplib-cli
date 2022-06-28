#!/usr/bin/env php
<?php
/**
 * @link https://robo.li/framework/#creating-a-standalone-phar-with-robo
 */
$pharPath = Phar::running(true);

do {
    if ($pharPath) {
        $autoloaderPath = "$pharPath/vendor/autoload.php";
        break;
    }

    if (file_exists(__DIR__.'/vendor/autoload.php')) {
        $autoloaderPath = __DIR__.'/vendor/autoload.php';
        break;
    }

    if (file_exists(__DIR__.'/../../autoload.php')) {
        $autoloaderPath = __DIR__ . '/../../autoload.php';
        break;
    }

    die("Could not find autoloader. Run 'composer install'.");
} while (false);

$classLoader = require $autoloaderPath;

$argv                 = filter_input(INPUT_SERVER, 'argv', FILTER_DEFAULT);
$appName              = "WPLib CLI";
$appVersion           = trim(file_get_contents(__DIR__ . '/VERSION'));
$commandClasses       = [\wplibcli\Commands\RoboFile::class];
$selfUpdateRepository = 'clubdeuce/wplib-cli';
$configurationFile    = 'wplib.yml';

$runner = new \Robo\Runner($commandClasses);
$runner
    ->setSelfUpdateRepository($selfUpdateRepository)
    ->setConfigurationFilename($configurationFilename)
    ->setClassLoader($classLoader);

// Execute the command and return the result.
$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$statusCode = $runner->execute($argv, $appName, $appVersion, $output);

exit($statusCode);
