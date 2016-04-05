<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User;
use Model\User\Thread\ThreadManager;
use Manager\UserManager;
use Service\Recommendator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersThreadsCreateCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('users:threads:create')
            ->setDescription('Creates threads for users')
            ->addArgument('scenario', InputArgument::REQUIRED, sprintf('Set of threads to add. Options available: "%s"', implode('", "', ThreadManager::$scenarios)))
            ->addOption('all', null, InputOption::VALUE_NONE, 'Create them to all users', null)
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'Id of thread owner', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $scenario = $input->getArgument('scenario');
        $all = $input->getOption('all');
        $userId = $input->getOption('userId');

        if (!in_array($scenario, ThreadManager::$scenarios)) {
            $output->writeln(sprintf('Scenario not valid. Available scenarios: "%s".', implode('", "', ThreadManager::$scenarios)));

            return;
        }

        if (!($all || $userId)) {
            $output->writeln('Please specify userId or all users');

            return;
        }

        /* @var $userManager UserManager */
        $userManager = $this->app['users.manager'];

        $users = array();
        if ($all) {
            $users = $userManager->getAll();
        } else {
            if ($userId) {
                $users = array($userManager->getById($userId, true));
            }
        }

        /* @var $threadManager ThreadManager */
        $threadManager = $this->app['users.threads.manager'];
        /* @var $recommendator Recommendator */
        $recommendator = $this->app['recommendator.service'];

        foreach ($users as $user) {

            $threads = $threadManager->getDefaultThreads($user, $scenario);

            /* @var $user User */
            $createdThreads = $threadManager->createBatchForUser($user->getId(), $threads);
            foreach ($createdThreads as $createdThread) {

                $result = $recommendator->getRecommendationFromThread($createdThread);

                $threadManager->cacheResults(
                    $createdThread,
                    array_slice($result['items'], 0, 5),
                    $result['pagination']['total']
                );

            }
            $output->writeln('Added threads for scenario ' . $scenario . ' and user with id ' . $user->getId());
        }

    }

}