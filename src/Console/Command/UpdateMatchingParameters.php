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
        $modelObject = $this->app['users.matching.normal_distribution.model'];

        try {
            $contentData = $modelObject->updateContentNormalDistributionVariables();
            $questionsData = $modelObject->updateQuestionsNormalDistributionVariables();
        } catch (\Exception $e) {
            $output->writeln(
               'Error trying to update parameters for the Normal Distributions with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Parameters set:');
        $output->writeln('Average(content) = ' . $contentData['average']);
        $output->writeln('Standard Deviation(content) = ' . $contentData['stdev']);
        $output->writeln('Average(questions) = ' . $questionsData['average']);
        $output->writeln('Standard Deviation(questions) = ' . $questionsData['stdev']);

    }
}
