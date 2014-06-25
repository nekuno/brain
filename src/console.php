<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

$console = new Application('My Silex Application', 'n/a');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->setDispatcher($app['dispatcher']);
$console
    ->register('social:fetch:links')
    ->setDefinition(array(
         new InputOption('resource', null, InputOption::VALUE_REQUIRED, 'Resource owner'),
    ))
    ->setDescription('Fetch links shared by user')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {

        $resource = $input->getOption('resource');
        $FQNClassName = '\\Social\\Consumer\\' . ucfirst($resource) . 'FeedConsumer';
        $consumer = new $FQNClassName($app);

        try {
            $consumer->fetch();
            $output->writeln('Success!');
        } catch(\Exception $e) {
            $output->writeln(sprintf('Error: %s', $e->getMessage()));
        }

    })
;

return $console;
