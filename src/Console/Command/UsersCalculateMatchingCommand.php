<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Neo4j\Neo4jException;
use Model\Matching\MatchingManager;
use Model\User\UserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class UsersCalculateMatchingCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'users:calculate:matching';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var MatchingManager
     */
    protected $matchingManager;

    public function __construct(LoggerInterface $logger, UserManager $userManager, MatchingManager $matchingManager)
    {
        parent::__construct($logger);
        $this->userManager = $userManager;
        $this->matchingManager = $matchingManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Recalculate the matching between two users.')
            ->addArgument('userA', InputArgument::OPTIONAL, 'id of the first user?')
            ->addArgument('userB', InputArgument::OPTIONAL, 'id of the second user?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userA = $input->getArgument('userA');
        $userB = $input->getArgument('userB');

        $combinations = array(
            array(
                0 => $userA,
                1 => $userB
            )
        );

        if (null === $userA || null === $userB) {
            $combinations = $this->userManager->getAllCombinations(false);
        }

        foreach ($combinations AS $users) {

            $userA = $users[0];
            $userB = $users[1];

            try {
                $oldQuestionMatching = $this->matchingManager->getMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                $this->matchingManager->calculateMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                $newQuestionMatching = $this->matchingManager->getMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
            } catch (\Exception $e) {

                $output->writeln(sprintf('[%s] Error trying to recalculate matching between user %d - %d with message %s', date('Y-m-d H:i:s'), $userA, $userB, $e->getMessage()));
                if ($e instanceof Neo4jException) {
                    $output->writeln(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                }
                continue;
            }
            $output->writeln(sprintf('Matching between users %d - %d old: %s new: %s', $userA, $userB, $oldQuestionMatching->getMatching(), $newQuestionMatching->getMatching()));
        }

        $output->writeln('Done.');

    }
}
