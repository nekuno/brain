<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Neo4j\Constraints;

use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jConstraintsCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:constraints')
             ->setDescription('Load neo4j database constraints');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $constraints = new Constraints($this->app['neo4j.client']);

        try {
            $constraints->load();
        } catch (\Exception $e) {
            $output->writeln(
               'Error loading neo4j constraints with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Constraints created');
    }
}
