<?php
/**
 * @author Roberto Martinez yawmoght@gmail.com>
 */

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User\RateModel;
use Model\UserModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jConsistencyLinksCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:consistency:links')
            ->setDescription('Ensures links database consistency')
            ->addOption('likes', null, InputOption::VALUE_NONE, 'Check LIKES relationships', null)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Solve problems where possible', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $likesOption = $input->getOption('likes');

        /** @var RateModel $rateModel */
        $rateModel = $this->app['users.rate.model'];

        //checking likes

        if ($likesOption) {


            $output->writeln('Getting likes list.');

            /** @var UserModel $userModel */
            $userModel = $this->app['users.model'];
            $users = $userModel->getAll();

            $likes = array();
            foreach ($users as $user) {
                $likes = array_merge($likes, $rateModel->getRatesByUser($user['qnoow_id'], RateModel::LIKE));
            }

            $output->writeln(sprintf('Got %d likes', count($likes)));

            $this->checkLikes($likes, $force, $output);
        }

        $output->writeln('Finished.');
    }

    /**
     * @param $likes array
     * @param $force boolean
     * @param $output OutputInterface
     */
    private function checkLikes($likes, $force, $output)
    {
        /** @var RateModel $rateModel */
        $rateModel = $this->app['users.rate.model'];

        $emptyLikes = array();
        foreach ($likes as $like) {
            if (count($like['resources']) == 0) {
                if ($force) {
                    try {
                        $rateModel->completeLikeById($like['id']);
                        if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                            $output->writeln(sprintf('SUCCESS: Empty like with id %d has been updated', $like['id']));
                            $emptyLikes [] = $like;
                        }
                    } catch (\Exception $e) {
                        $output->writeln(sprintf('ERROR: Cannot update like with id %d', $like['id']));
                    }
                } else {
                    if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                        $output->writeln(sprintf('Empty like with id %d need to be updated', $like['id']));
                        $emptyLikes [] = $like;
                    }
                }
            }
        }

        if ($force) {
            $output->writeln(sprintf('%d empty links updated', count($emptyLikes)));
        } else {
            $output->writeln(sprintf('%d empty likes need to be updated', count($emptyLikes)));
        }

    }

}