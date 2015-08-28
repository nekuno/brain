<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

$console = new Application('Nekuno Brain', '0.*');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
/* @var $app Silex\Application */
$console->setDispatcher($app['dispatcher']);
$app->post('/lookUp/webHook', 'lookUp.controller:setFromWebHookAction')->bind('setLookUpFromWebHook');

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
        new \Console\Command\LinksFindDuplicatesCommand($app),
        new \Console\Command\LinksFuseCommand($app),
        new \Console\Command\GetUncorrelatedQuestionsCommand($app),
        new \Console\Command\SendChatMessagesNotificationsCommand($app),
        new \Console\Command\SwiftMailerSpoolSendCommand($app),
        new \Console\Command\MigrateSocialInvitationsCommand($app),
        new \Console\Command\MigrateSocialProfilesCommand($app),
        new \Console\Command\LookUpByEmailCommand($app),
        new \Console\Command\LookUpAllUsersCommand($app),
    )
);

return $console;
