<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Exception\ValidationException;
use Manager\UserManager;
use Silex\Application;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MigrateSocialUsersCommand extends ApplicationAwareCommand
{
    protected function configure()
    {
        $this->setName('migrate:social-users')
            ->setDescription('Migrate users from social to brain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $sm Connection */
        $sm = $this->app['dbs']['mysql_social'];

        $qb = $sm->createQueryBuilder()
            ->select('*')
            ->from('users')
            ->orderBy('id');

        $users = $qb->execute()->fetchAll();

        $output->writeln(count($users) . ' users found');

        foreach ($users as $user) {
            $this->migrateUser($user, $output);
        }

        $output->writeln(count($users) . ' users processed');

        $output->writeln('Done');
    }

    protected function migrateUser(array $user, OutputInterface $output)
    {

        /* @var $um UserManager */
        $um = $this->app['users.manager'];

        $id = (int)$user['id'];
        unset($user['id']);
        $user['usernameCanonical'] = $user['username_canonical'];
        unset($user['username_canonical']);
        $user['emailCanonical'] = $user['email_canonical'];
        unset($user['email_canonical']);
        $user['enabled'] = (bool)$user['enabled'];
        $user['lastLogin'] = $user['last_login'];
        unset($user['last_login']);
        $user['locked'] = (bool)$user['locked'];
        $user['expired'] = (bool)$user['expired'];
        $user['expiresAt'] = $user['expires_at'];
        unset($user['expires_at']);
        $user['confirmationToken'] = $user['confirmation_token'];
        unset($user['confirmation_token']);
        $user['passwordRequestedAt'] = $user['password_requested_at'];
        unset($user['password_requested_at']);
        unset($user['roles']);
        unset($user['credentials_expired']);
        unset($user['credentials_expire_at']);
        unset($user['rating']);
        $user['confirmed'] = (bool)$user['confirmed'];

        try {

            $um->getById($id);

        } catch (NotFoundHttpException $e) {

            $output->writeln('User ' . $id . ' do not exists');

            return;
        }

        try {

            $um->save($id, $user);
            $output->writeln('User ' . $id . ' updated');

        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            if ($e instanceof ValidationException) {
                $output->writeln(print_r($e->getErrors(), true));
            }
        }

    }
}