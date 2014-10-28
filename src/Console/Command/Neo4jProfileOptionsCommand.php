<?php

namespace Console\Command;

use Model\Neo4j\ProfileOptions;

use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
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

        try {
            $profileOptions->load();
        } catch (\Exception $e) {
            $output->writeln(
                'Error loading neo4j profile options with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Profile options created');
    }
}