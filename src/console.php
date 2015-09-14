<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

$console = new Application('Nekuno Brain', '0.*');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
/* @var $app Silex\Application */
$console->setDispatcher($app['dispatcher']);

$commands = array();
foreach (scandir(__DIR__ . '/Console/Command') as $file) {

    if (!in_array($file, array('.', '..'))) {
        $class = 'Console\\Command\\' . str_replace('.php', '', $file);
        $commands[] = new $class($app);
    }
}

$console->addCommands($commands);

return $console;
