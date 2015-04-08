<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

$console = new Application('Nekuno Brain', '0.*');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->setDispatcher($app['dispatcher']);

$console->addCommands(
    array(
        new \Console\Command\FetchLinksCommand($app),
        new \Console\Command\WorkerRabbitMQConsumeCommand($app),
        new \Console\Command\ProcessLinksMetadataCommand($app),
        new \Console\Command\Neo4jConstraintsCommand($app),
        new \Console\Command\Neo4jFixturesCommand($app),
        new \Console\Command\Neo4jProfileOptionsCommand($app),
        new \Console\Command\Neo4jLoadQuestionsCommand($app),
        new \Console\Command\RecalculateMatching($app),
        new \Console\Command\RecalculatePopularity($app),
        new \Console\Command\SimilarityCommand($app),
        new \Console\Command\AffinityCommand($app),
        new \Console\Command\PredictionCommand($app),
        new \Console\Command\EnqueueFetchingCommand($app),
        new \Console\Command\LinksRemoveDuplicatesCommand($app),
        new \Console\Command\LinksFindPseudoduplicatesCommand($app),
        new \Console\Command\LinksFuseCommand($app),
        new \Console\Command\SendChatMessagesNotifications($app),
    )
);

return $console;
