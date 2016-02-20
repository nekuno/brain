<?php
/**
 * @author Roberto Martinez yawmoght@gmail.com>
 */

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User\ProfileModel;
use Manager\UserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Neo4jConsistencyUsersCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:consistency:users')
            ->setDescription('Ensures users database consistency')
            ->addOption('status', null, InputOption::VALUE_NONE, 'Check users status', null)
            ->addOption('profile', null, InputOption::VALUE_NONE, 'Solve profile-related problems', null)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Solve problems where possible', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $status = $input->getOption('status');
        $profile = $input->getOption('profile');

        $output->writeln('Getting user list.');
        /** @var UserManager $userManager */
        $userManager = $this->app['users.manager'];

        $users = $userManager->getAll();
        $output->writeln('Got ' . count($users) . ' users.');

        //checking status

        if ($status) {
            $this->checkStatus($users, $force, $output);
        }

        if ($profile) {
            $this->checkProfile($users, $force, $output);
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
        /** @var UserManager $userManager */
        $userManager = $this->app['users.manager'];

        $output->writeln('Checking users status.');

        $userStatusChanged = array();
        foreach ($users as $user) {
            try {
                $status = $userManager->calculateStatus($user['qnoow_id'], $force);

                if ($status->getStatusChanged()) {

                    $userStatusChanged[$user['qnoow_id']] = $status->getStatus();

                }
            } catch (\Exception $e) {
                $output->writeln(sprintf('ERROR: Fail to calculate status for user %d', $user['qnoow_id']));
            }

        }

        foreach ($userStatusChanged as $userId => $newStatus) {
            if ($force) {
                $output->writeln(sprintf('SUCCESS: User %d had their status changed to %s', $userId, $newStatus));
            } else {
                $output->writeln(sprintf('User %d needs their status to be changed to %s', $userId, $newStatus));
            }
        }

        if ($force) {
            $output->writeln(sprintf('%d new statuses updated', count($userStatusChanged)));
        } else {
            $output->writeln(sprintf('%d new statuses need to be updated', count($userStatusChanged)));
        }

    }

    /**
     * @param $users array
     * @param $force boolean
     * @param $output OutputInterface
     */
    private function checkProfile($users, $force, $output)
    {
        /** @var ProfileModel $profileModel */
        $profileModel = $this->app['users.profile.model'];
        foreach ($users as $user) {
            try {
                $profile = $profileModel->getById($user['qnoow_id']);
            } catch (NotFoundHttpException $e) {
                $output->writeln(sprintf('Profile for user with id %d not found.', $user['qnoow_id']));
                if ($force) {
                    $output->writeln(sprintf('Creating profile for user %d.', $user['qnoow_id']));
                    $profile = $profileModel->create($user['qnoow_id'], array(
                        'birthday' => '1970-01-01',
                        'gender' => 'male',
                        'orientation' => 'heterosexual',
                        'interfaceLanguage' => 'es',
                        'location' => array(
                            'latitude' => 40.4167754,
                            'longitude' => -3.7037902,
                            'address' => 'Madrid',
                            'locality' => 'Madrid',
                            'country' => 'Spain'
                        )
                    ));
                    $output->writeln(sprintf('SUCCESS: Created profile for user %d.', $user['qnoow_id']));
                }
            }

            if (isset($profile) && is_array($profile)) {
                $output->writeln(sprintf('Found profile for user %d.', $user['qnoow_id']));
            }

        }
    }

}