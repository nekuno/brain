<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Neo4j\Neo4jException;
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

        foreach ($combinations AS $users) {

            $userA = $users[0];
            $userB = $users[1];

            try {
                $oldQuestionMatching = $modelObject->getMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                $modelObject->calculateMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                $newQuestionMatching = $modelObject->getMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
            } catch (\Exception $e) {

                $output->writeln(sprintf('[%s] Error trying to recalculate matching between user %d - %d with message %s', date('Y-m-d H:i:s'), $userA, $userB, $e->getMessage()));
                if ($e instanceof Neo4jException) {
                    $output->writeln(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                }
                continue;
            }
            $output->writeln(sprintf('Matching between users %d - %d old: %s new: %s', $userA, $userB, $oldQuestionMatching['matching'], $newQuestionMatching['matching']));
        }

        $output->writeln('Done.');

    }
}
