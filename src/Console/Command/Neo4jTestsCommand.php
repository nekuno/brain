<?php

namespace Console\Command;

use Model\User\MatchingModel;

use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jTestsCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:test')
             ->setDescription("Load test for Neo4j development. Intended for quick creation of tests only; behavior may change");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $testObject = new MatchingModel($this->app['neo4j.client'], $this->app['users.content.model'], $this->app['users.answer.model']);

        try {
            $result = $testObject->getMatchingBetweenTwoUsersBasedOnAnswers(5, 7);
        } catch (\Exception $e) {
            $output->writeln(
               'Error trying to execute test with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Tests run. Matching: ' . array_values($result)[0]);
    }
}
