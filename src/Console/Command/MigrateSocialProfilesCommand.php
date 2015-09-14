<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User\PrivacyModel;
use Silex\Application;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MigrateSocialProfilesCommand extends ApplicationAwareCommand
{
    protected function configure()
    {
        $this->setName('migrate:social-profiles')
            ->setDescription('Migrate profiles from social to brain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $sm Connection */
        $sm = $this->app['dbs']['mysql_social'];

        $qb = $sm->createQueryBuilder('p')
            ->select(
                'up.user_id',
                'profile.code AS profile',
                'description.code AS description',
                'questions.code AS questions',
                'gallery.code AS gallery',
                'messages.code AS messages'
            )
            ->from('user_profiles', 'up')
            ->leftJoin('up', 'user_privacy_options', 'profile', 'up.privacy_profile_access = profile.id')
            ->leftJoin('up', 'user_privacy_options', 'description', 'up.privacy_profile_description = description.id')
            ->leftJoin('up', 'user_privacy_options', 'questions', 'up.privacy_profile_questions = questions.id')
            ->leftJoin('up', 'user_privacy_options', 'gallery', 'up.privacy_profile_gallery = gallery.id')
            ->leftJoin('up', 'user_privacy_options', 'messages', 'up.privacy_receive_messages = messages.id');

        $profiles = $qb->execute()->fetchAll();

        $output->writeln(count($profiles) . ' profiles found');

        foreach ($profiles as $profile) {
            $this->migrateProfile($profile, $output);
        }

        $output->writeln('Done');
    }

    protected function migrateProfile(array $profile, OutputInterface $output)
    {

        /* @var $pm PrivacyModel */
        $pm = $this->app['users.privacy.model'];

        try {
            $pm->getById($profile['user_id']);
        } catch (NotFoundHttpException $e) {

            $data = array(
                'profile' => $profile['profile'] ? $profile['profile'] : 'all',
                'description' => $profile['description'] ? $profile['description'] : 'all',
                'questions' => $profile['questions'] ? $profile['questions'] : 'all',
                'gallery' => $profile['gallery'] ? $profile['gallery'] : 'all',
                'messages' => $profile['messages'] ? $profile['messages'] : 'all',
            );

            try {
                $pm->create($profile['user_id'], $data);
                $output->writeln('Profile ' . $profile['user_id'] . ' migrated');
                $output->writeln(print_r($data, true));
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                $output->writeln('Profile ' . $profile['user_id'] . ' not migrated');
                $output->writeln(print_r($data, true));
            }

        }

    }
}