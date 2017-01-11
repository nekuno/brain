<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User;
use Model\User\Filters\FilterContent;
use Model\User\Filters\FilterUsers;
use Manager\UserManager;
use Model\User\Thread\Thread;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jConsistencyThreadsCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:consistency:threads')
            ->setDescription('Ensures threads database consistency')
            ->addOption('filters', null, InputOption::VALUE_NONE, 'Check consistency of filters', null)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Solve problems where possible', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filters = $input->getOption('filters');
        $force = $input->getOption('force');

        $output->writeln('Getting user list.');
        /** @var UserManager $userManager */
        $users = $this->app['users.manager']->getAll();

        foreach ($users as $user) {
            $threads = $this->app['users.threads.manager']->getByUser($user->getId());
            foreach ($threads as $thread) {
                $output->writeln('---------------------------------------------------------------------------------');
                $output->writeln('Checking thread ' . $thread->getId() . ' from user ' . $user->getId());

                $this->checkThread($thread, $force, $output);
                if ($thread instanceof User\Thread\UsersThread) {
                    if ($filters) {
                        $this->checkFilterUsers($thread->getFilterUsers(), $force, $output);
                    }

                } else if ($thread instanceof User\Thread\ContentThread) {
                    if ($filters) {
                        $filter = $thread->getFilterContent();
                        $this->checkFilterContent($filter, $force, $output);
                    }
                }
            }
        }

        $output->writeln('Finished.');
    }

    protected function checkThread(Thread $thread, $force, OutputInterface $output)
    {
        $name = $thread->getName();
        if (!$name) {
            $output->writeln('Thread with id ' . $thread->getId() . ' has no name.');
            if ($force) {
                $thread->setName('Default');
                $output->writeln('Thread with id ' . $thread->getId() . ' set name: Default.');
            }
        }
    }

    protected function checkFilterUsers(FilterUsers $filterUsers, $force, $output)
    {

    }

    protected function checkFilterContent(FilterContent $filterContent, $force, OutputInterface $output)
    {
        $changed = false;
        $type = $filterContent->getType();
        if (is_string($type)) {
            try {
                $json = \GuzzleHttp\json_decode($type);
                if (!is_array($json)) {
                    $output->writeln('Content filter with id ' . $filterContent->getId() . ' has a non-array type.');
                    if ($force) {
                        $filterContent->setType(array($type));
                        $changed = true;
                        $output->writeln('Wrapped type filter '.$type.' in an array.');
                    }
                }
            } catch (\Exception $e) {
                $output->writeln('Could not decode type array from content filter ' . $filterContent->getId() . '.');
                if ($force) {
                    $filterContent->setType(array($type));
                    $changed = true;
                    $output->writeln('Wrapped type filter '.$type.' in an array.');
                }
            }
        } else {
            $output->writeln('Content filter with id ' . $filterContent->getId() . ' has a non-string type filter.');
            if ($force) {
                $filterContent->setType(array('Link'));
                $changed = true;
                $output->writeln('Unable to fix type filter. Changed to Link');
            }
        }

        if ($changed) {
            $this->app['users.filtercontent.manager']->updateFiltersContent($filterContent);
            $output->writeln('Changes on filter ' . $filterContent->getId() . ' saved to database.');
        }
    }
}