<?php

namespace Console\Command;

use Model\Neo4j\ProfileOptions;
use Psr\Log\LogLevel;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jProfileOptionsCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:profile-options')
            ->setDescription("Load neo4j profile options");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $profileOptions = new ProfileOptions($this->app['neo4j.client']);

        $verbosityLevelMap = array(
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
        );
        $logger = new ConsoleLogger($output, $verbosityLevelMap);
        $profileOptions->setLogger($logger);

        try {
            $result = $profileOptions->load();
        } catch (\Exception $e) {
            $output->writeln(
                'Error loading neo4j profile options with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln(sprintf('%d new profile options processed.', $result->getTotal()));
        $output->writeln(sprintf('%d new profile options updated.', $result->getUpdated()));
        $output->writeln(sprintf('%d new profile options created.', $result->getCreated()));
    }
}