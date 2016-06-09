<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Neo4j\Neo4jException;
use Model\User\Similarity\SimilarityModel;
use Manager\UserManager;
use Silex\Application;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersCalculateSimilarityCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('users:calculate:similarity')
            ->setDescription('Calculate the similarity of two users.')
            ->addArgument('userA', InputArgument::OPTIONAL, 'id of the first user?')
            ->addArgument('userB', InputArgument::OPTIONAL, 'id of the second user?')
            ->addOption('groupId', null, InputOption::VALUE_REQUIRED, 'Group id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $model SimilarityModel */
        $model = $this->app['users.similarity.model'];

        $userA = $input->getArgument('userA');
        $userB = $input->getArgument('userB');
        $groupId = $input->getOption('groupId');

        $combinations = array(
            array(
                0 => $userA,
                1 => $userB
            )
        );

        if (null === $userA || null === $userB) {

            if ($groupId) {
                $output->writeln(sprintf('Calculating for all users in group %d, including ghost users.', $groupId));
            } else {
                $output->writeln('Calculating for all users, including ghost users.');
            }
            /* @var $userManager UserManager */
            $userManager = $this->app['users.manager'];
            $combinations = $userManager->getAllCombinations(true, $groupId);
        }

        foreach ($combinations AS $users) {

            $userA = $users[0];
            $userB = $users[1];

            if ($userA == $userB){
                continue;
            }

            try {
                $similarity = $model->getSimilarity($userA, $userB);
            } catch (\Exception $e) {

                $output->writeln(sprintf('[%s] Error trying to recalculate similarity between user %d - %d with message %s', date('Y-m-d H:i:s'), $userA, $userB, $e->getMessage()));
                if ($e instanceof Neo4jException) {
                    $output->writeln(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                }
                continue;
            }

            if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                $output->writeln(sprintf('[%s] Similarity between user %d - %d', date('Y-m-d H:i:s'), $userA, $userB));
                $this->getTable($similarity)->render($output);
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
        $similarityUpdated = new \DateTime();
        $similarityUpdated->setTimestamp($similarity['similarityUpdated'] / 1000);

        /* @var $table TableHelper */
        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Type', 'Value', 'Last Updated'))
            ->setRows(
                array(
                    array('Questions', $similarity['questions'], $questionsUpdated->format('Y-m-d H:i:s')),
                    array('Interests', $similarity['interests'], $interestsUpdated->format('Y-m-d H:i:s')),
                    array('Similarity', $similarity['similarity'], $similarityUpdated->format('Y-m-d H:i:s')),
                )
            );

        return $table;
    }
}
