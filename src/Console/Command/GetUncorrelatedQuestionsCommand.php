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
            ->addArgument('size', InputArgument::REQUIRED, 'How many questions do you want in each group')
            ->addArgument('preselect', InputArgument::OPTIONAL, 'How many top rating questions are analyzed', 50);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $model QuestionModel */
        $model = $this->app['questionnaire.questions.model'];

        $preselected = $input->getArgument('preselect');
        $size = $input->getArgument('size');

        $questions = $model->getUncorrelatedQuestions($size, $preselected);

//        $output->writeln($questions);
//        return;

        if (array() === $questions) {
            $output->writeln('We couldn´t get the questions');

            return;
        }

        foreach ($questions as $question1=>$questions2) {
            foreach($questions2 as $question2=>$correlation){
                $output->writeln(sprintf('Correlation %f between question %s and question %s ', $correlation, $question1, $question2));

            }
        }



        try {

//            /* @var $questionGroup array */
//            foreach ($questions AS $rating => $questionGroup) {
//                $output->writeln(sprintf('Rating %f for question group of ids: %s ', $rating, $questionGroup));
//            }

        } catch (\Exception $e) {

            $output->writeln(sprintf('Error trying to get the uncorrelated questions: %s', $e->getMessage()));

            return;
        }

    }
}