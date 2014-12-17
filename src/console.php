<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

$console = new Application('Qnoow Brain', '0.1');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->setDispatcher($app['dispatcher']);

$console->addCommands(array(
    new \Console\Command\FetchLinksCommand($app),
    new \Console\Command\WorkerRabbitMQConsumeCommand($app),
    new \Console\Command\ProcessLinksMetadataCommand($app),
    new \Console\Command\Neo4jConstraintsCommand($app),
    new \Console\Command\Neo4jFixturesCommand($app),
    new \Console\Command\Neo4jProfileOptionsCommand($app),
    new \Console\Command\Neo4jTestsCommand($app),
    new \Console\Command\UpdateMatchingParameters($app),
    new \Console\Command\RecalculateMatching($app),
    new \Console\Command\EnqueueFetchingCommand($app),
));

return $console;
