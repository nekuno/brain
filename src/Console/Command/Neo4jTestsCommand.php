<?php

namespace Console\Command;

use Model\User\Matching\MatchingModel;

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
        $testObject = new MatchingModel(
            $this->app['dispatcher'],
            $this->app['neo4j.client'],
            $this->app['users.content.model'],
            $this->app['users.answers.model'],
            $this->app['users.matching.normal_distribution.model']
        );

        try {
            $value1 = $testObject->getMatchingBetweenTwoUsersBasedOnAnswers(36, 39);
            $value2 = $testObject->getMatchingBetweenTwoUsersBasedOnSharedContent(36, 39);
        } catch (\Exception $e) {
            $output->writeln(
                'Error trying to execute test with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Tests run');
        $output->writeln('');
        $output->writeln('Matching(questions) users 36 and 39: ' . $value1);
        $output->writeln('Matching(content) users 36 and 39: ' . $value2);
    }
}
