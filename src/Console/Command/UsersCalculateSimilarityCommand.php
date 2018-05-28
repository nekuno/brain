<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\GhostUser\GhostUserManager;
use Model\Neo4j\Neo4jException;
use Model\Similarity\SimilarityManager;
use Model\User\UserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersCalculateSimilarityCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'users:calculate:similarity';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var GhostUserManager
     */
    protected $ghostUserManager;

    /**
     * @var SimilarityManager
     */
    protected $similarityManager;

    protected $realSimilarities = 800;
    protected $ghostSimilarities = 200;

    protected $allRealIds = null;
    protected $allGhostIds = null;

    public function __construct(LoggerInterface $logger, UserManager $userManager, GhostUserManager $ghostUserManager, SimilarityManager $similarityManager)
    {
        parent::__construct($logger);
        $this->userManager = $userManager;
        $this->ghostUserManager = $ghostUserManager;
        $this->similarityManager = $similarityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Calculate the similarity of users combinations.')
            ->addArgument('userA', InputArgument::OPTIONAL, 'id of the first user?')
            ->addArgument('userB', InputArgument::OPTIONAL, 'id of the second user?')
            ->addOption('groupId', null, InputOption::VALUE_REQUIRED, 'Group id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userA = $input->getArgument('userA');
        $userB = $input->getArgument('userB');
        $groupId = $input->getOption('groupId');

        if ($userA && $userB) {
            $this->similarityManager->getSimilarity($userA, $userB);

        } else if ($userA) {
            $output->writeln('Calculating similarities by questions for '.$userA);
            $this->similarityManager->recalculateSimilaritiesByQuestions($userA);
            $output->writeln('Calculating similarities by interests for '.$userA);
            $this->similarityManager->recalculateSimilaritiesByInterests($userA);

        } else if ($groupId) {
            $output->writeln(sprintf('Calculating for all users in group %d', $groupId));
            $combinations = $this->userManager->getAllCombinations(true, $groupId);
            $this->calculateSimilarities($combinations, $output);

        } else {
            $output->writeln('Calculating for all users');
            $userIds = $this->userManager->getAllIds();

            foreach ($userIds as $userId) {
                $output->writeln(sprintf('Calculating similarities by questions for %d', $userId));
                $this->similarityManager->recalculateSimilaritiesByQuestions($userId);
                $output->writeln(sprintf('Calculating similarities by interests for %d', $userId));
                $this->similarityManager->recalculateSimilaritiesByInterests($userId);
            }
        }

        $output->writeln('Done.');
    }

    protected function getTable($similarity)
    {
        $questionsUpdated = new \DateTime();
        $questionsUpdated->setTimestamp($similarity['questionsUpdated'] / 1000);
        $interestsUpdated = new \DateTime();
        $interestsUpdated->setTimestamp($similarity['interestsUpdated'] / 1000);
        $skillsUpdated = new \DateTime();
        $skillsUpdated->setTimestamp($similarity['interestsUpdated'] / 1000);
        $similarityUpdated = new \DateTime();
        $similarityUpdated->setTimestamp($similarity['similarityUpdated'] / 1000);

        /* @var $table Table */
        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Type', 'Value', 'Last Updated'))
            ->setRows(
                array(
                    array('Questions', $similarity['questions'], $questionsUpdated->format('Y-m-d H:i:s')),
                    array('Interests', $similarity['interests'], $interestsUpdated->format('Y-m-d H:i:s')),
                    array('Skills', $similarity['skills'], $skillsUpdated->format('Y-m-d H:i:s')),
                    array('Similarity', $similarity['similarity'], $similarityUpdated->format('Y-m-d H:i:s')),
                )
            );

        return $table;
    }

    protected function getCombinations($userId, $combinationsByUser = array())
    {
        $similarRealIds = $this->userManager->getMostSimilarIds($userId, 800);
        $similarGhostIds = $this->ghostUserManager->getMostSimilarIds($userId, 200);

        $missingRealIds = $this->realSimilarities - count($similarRealIds);
        if ($missingRealIds > 0) {
            $allIds = $this->getRealIds();

            foreach ($allIds as $newId) {
                if (!in_array($newId, $similarRealIds)) {
                    array_push($similarRealIds, $newId);
                }
                if (count($similarRealIds) >= $this->realSimilarities) {
                    break;
                }
            }
        }

        $missingGhostIds = $this->ghostSimilarities - count($similarGhostIds);
        if ($missingGhostIds > 0) {
            $allIds = $this->getGhostIds();

            foreach ($allIds as $newId) {
                if (!in_array($newId, $similarGhostIds)) {
                    array_push($similarGhostIds, $newId);
                }
                if (count($similarGhostIds) >= $this->ghostSimilarities) {
                    break;
                }
            }
        }

        $allIds = $similarRealIds + $similarGhostIds;

        $combinations = array();
        foreach ($allIds as $id) {
            if (!(isset($combinationsByUser[$id]) && in_array(array($id, $userId), $combinationsByUser[$id]))) {
                $combinations[] = array($userId, $id);
            }
        }
        return $combinations;
    }

    protected function getRealIds()
    {
        if (!$this->allRealIds) {
            $this->allRealIds = $this->userManager->getAllIds();
        }
        return $this->allRealIds;
    }

    protected function getGhostIds()
    {
        if (!$this->allGhostIds) {
            $this->allGhostIds = $this->ghostUserManager->getAllIds();
        }
        return $this->allGhostIds;
    }

    protected function calculateSimilarities(\ArrayAccess $combinations, OutputInterface $output) {
        foreach ($combinations AS $users) {

            $userA = $users[0];
            $userB = $users[1];

            if ($userA == $userB) {
                continue;
            }

            try {
                $similarity = $this->similarityManager->getSimilarity($userA, $userB);
            } catch (\Exception $e) {

                $output->writeln(sprintf('[%s] Error trying to recalculate similarity between user %d - %d with message %s', date('Y-m-d H:i:s'), $userA, $userB, $e->getMessage()));
                if ($e instanceof Neo4jException) {
                    $output->writeln(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                }
                continue;
            }

            if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                $output->writeln(sprintf('[%s] Similarity between user %d - %d', date('Y-m-d H:i:s'), $userA, $userB));
                $this->getTable($similarity)->render();
            }
        }
    }
}
