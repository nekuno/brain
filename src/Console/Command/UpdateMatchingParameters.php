<?php

namespace Console\Command;

use Model\User\Matching\MatchingModel;

use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMatchingParameters extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('matching:updateMatchingParameters')
             ->setDescription("Update the Average and Standard Deviation for the Normal Distributions used in matchings");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modelObject = new MatchingModel($this->app['neo4j.client'], $this->app['users.content.model'], $this->app['users.answer.model']);

        try {
            $modelObject->updateContentNormalDistributionVariables();
            $modelObject->updateQuestionsNormalDistributionVariables();
        } catch (\Exception $e) {
            $output->writeln(
               'Error trying to update parameters for the Normal Distributions with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Parameters set:');
        $output->writeln('Average(content) = ' . MatchingModel::$ave_content);
        $output->writeln('Standard Deviation(content) = ' . MatchingModel::$stdev_content);
        $output->writeln('Average(questions) = ' . MatchingModel::$ave_questions);
        $output->writeln('Standard Deviation(questions) = ' . MatchingModel::$stdev_questions);

    }
}
