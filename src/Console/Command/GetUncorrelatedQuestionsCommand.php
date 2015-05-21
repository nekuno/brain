<?php

namespace Console\Command;

use Model\Questionnaire\QuestionModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class GetUncorrelatedQuestionsCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('questions:get-uncorrelated')
            ->setDescription("Get a selection of uncorrelated questions groups.")
            ->addArgument('preselect', InputArgument::OPTIONAL, 'How many top ranking questions are analyzed', 50);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $model QuestionModel */
        $model = $this->app['questionnaire.questions.model'];

        $preselected = $input->getArgument('preselect');

        $result = $model->getUncorrelatedQuestions($preselected);

        if (array() === $result['questions']) {
            $output->writeln('We couldn´t get the questions');
            return;
        }

        //for debugging, modify return to appropriate array in QuestionModel.php
//        $this->outputPercentages($result, $output);
//        $this->outputDistributions($result, $output);
//        $this->outputCorrelations($result, $output);

        $this->outputResult($result, $output);

    }

    /**
     * @param $result array
     * @param $output OutputInterface
     */
    protected function outputCorrelations($result, $output)
    {
        $size = 0;
        foreach ($result as $question1 => $questions2) {
            foreach ($questions2 as $question2 => $correlation) {
                $output->writeln(sprintf('Correlation %f between question %s and question %s ', $correlation, $question1, $question2));
                $size++;
            }
        }

        $output->writeln($size);
    }

    /**
     * @param $result array
     * @param $output OutputInterface
     */
    protected function outputPercentages($result, $output)
    {
        foreach ($result as $q1 => $q1array) {
            foreach ($q1array as $q2 => $q2array) {
                foreach ($q2array as $a1 => $a1array) {
                    foreach ($a1array as $a2 => $a2array) {
                        $output->writeln('Respuesta ' . $a1 . ' y ' . $a2 . ': ' . $a2array);
                    }
                }
            }
        }
    }

    /**
     * @param $result array
     * @param $output OutputInterface
     */
    protected function outputDistributions($result, $output)
    {
        foreach ($result as $question1 => $dist1) {
            foreach ($dist1 as $question2 => $dist2) {
                $output->writeln('Escribiendo ' . $question1 . ' y ' . $question2);
                $output->writeln($dist2);
            }
        }
    }

    /**
     * @param $result array
     * @param $output OutputInterface
     */
    protected function outputResult($result, $output)
    {
        try {

            $output->writeln(sprintf('Total correlation %s with questions %s, %s, %s and %s',
                $result['totalCorrelation'],
                $result['questions']['q1'],
                $result['questions']['q2'],
                $result['questions']['q3'],
                $result['questions']['q4']
            ));
            $output->writeln('Total correlation: ' . $result['totalCorrelation']);

        } catch (\Exception $e) {

            $output->writeln(sprintf('Error trying to get the uncorrelated questions: %s', $e->getMessage()));

            return;
        }
    }

}