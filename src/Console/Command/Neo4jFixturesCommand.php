<?php
/**
 * Created by PhpStorm.
 * User: zaski
 * Date: 8/21/14
 * Time: 3:01 AM
 */

namespace Console\Command;


class Neo4jFixturesCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:fixtures')
            ->setDescription("Load neo4j database fixtures");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fixtures = new Fixtures($this->app['neo4j.client']);

        try {
            $fixtures->load();
        } catch (\Exception $e) {
            $output->writeln(
                'Error loading neo4j fixtures with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Fixtures created');
    }
}