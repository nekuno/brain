<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User\Matching\MatchingModel;
use Model\UserModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class UsersCalculateMatchingCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('users:calculate:matching')
            ->setDescription('Recalculate the matching between two users.')
            ->addArgument('userA', InputArgument::OPTIONAL, 'id of the first user?')
            ->addArgument('userB', InputArgument::OPTIONAL, 'id of the second user?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $modelObject MatchingModel */
        $modelObject = $this->app['users.matching.model'];

        $userA = $input->getArgument('userA');
        $userB = $input->getArgument('userB');

        $combinations = array(
            array(
                0 => $userA,
                1 => $userB
            )
        );

        if (null === $userA || null === $userB) {
            /* @var $userModel UserModel */
            $userModel = $this->app['users.model'];
            $combinations = $userModel->getAllCombinations();
        }

        try {

            foreach ($combinations AS $users) {

                $userA = $users[0];
                $userB = $users[1];

                $oldQuestionMatching = $modelObject->getMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                $modelObject->calculateMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                $newQuestionMatching = $modelObject->getMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);

                $output->writeln(sprintf('Matching between users %d - %d old: %s new: %s', $userA, $userB, $oldQuestionMatching['matching'], $newQuestionMatching['matching']));
            }

        } catch (\Exception $e) {

            $output->writeln(sprintf('Error trying to recalculate matching with parameters: %s', $e->getMessage()));

            return;
        }

    }
}
