<?php
/**
 * @author Roberto Martinez yawmoght@gmail.com>
 */

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\UserModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jConsistencyUsersCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:consistency:users')
            ->setDescription('Ensures users database consistency')
            ->addOption('status', null, InputOption::VALUE_NONE, 'Check users status', null)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Solve problems where possible', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $status = $input->getOption('status');

        $output->writeln('Getting user list.');
        /** @var UserModel $userModel */
        $userModel = $this->app['users.model'];

        $users = $userModel->getAll();
        $output->writeln('Got ' . count($users) . ' users.');

        //checking status

        if ($status) {
            $this->checkStatus($users, $force, $output);
        }

        $output->writeln('Finished.');
    }

    /**
     * @param $users array
     * @param $force boolean
     * @param $output OutputInterface
     */
    private function checkStatus($users, $force, $output)
    {
        /** @var UserModel $userModel */
        $userModel = $this->app['users.model'];

        $output->writeln('Checking users status.');

        $userStatusChanged = array();
        foreach ($users as $user) {
            try {
                $status = $userModel->calculateStatus($user['qnoow_id'], $force);

                if ($status->getStatusChanged()) {

                    $userStatusChanged[$user['qnoow_id']] = $status->getStatus();

                }
            } catch (\Exception $e) {
                $output->writeln(sprintf('ERROR: Fail to calculate status for user %d', $user['qnoow_id']));
            }

        }

        if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
            foreach ($userStatusChanged as $userId => $newStatus) {
                if ($force) {
                    $output->writeln(sprintf('SUCCESS: User %d had their status changed to %s', $userId, $newStatus));
                } else {
                    $output->writeln(sprintf('User %d needs their status to be changed to %s', $userId, $newStatus));
                }
            }

        }

        if ($force) {
            $output->writeln(sprintf('%d new statuses updated', count($userStatusChanged)));
        } else {
            $output->writeln(sprintf('%d new statuses need to be updated', count($userStatusChanged)));
        }

    }

}