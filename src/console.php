<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

$console = new Application('My Silex Application', 'n/a');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->setDispatcher($app['dispatcher']);

$console->addCommands(array(
    new \Console\Command\FetchLinksCommand($app),
    new \Console\Command\ScrapLinksMetadataCommand($app),
));

return $console;
