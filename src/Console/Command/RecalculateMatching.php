<?php

namespace Console\Command;

use Model\User\Matching\MatchingModel;

use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RecalculateMatching extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('matching:recalculate')
            ->setDescription("Recalculate the matching between two users.")
            ->addArgument(
                'user1',
                InputArgument::REQUIRED,
                'id of the first user?'
            )
            ->addArgument(
                'user2',
                InputArgument::REQUIRED,
                'id of the second user?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var \Model\User\Matching\MatchingModel $modelObject */
        $modelObject = $this->app['users.matching.model'];

        $id1 = $input->getArgument('user1');
        $id2 = $input->getArgument('user2');

        try {
            $oldQuestionMatching = $modelObject->getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2);

            $modelObject->calculateMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2);

            $newQuestionMatching = $modelObject->getMatchingBetweenTwoUsersBasedOnAnswers($id1, $id2);

        } catch (\Exception $e) {
            $output->writeln(
               'Error trying to recalculate matching with parameters: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Old Questions Matching: ' . $oldQuestionMatching['matching']);
        $output->writeln('New Questions Matching: ' . $newQuestionMatching['matching']);

    }
}
