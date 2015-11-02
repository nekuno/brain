<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 29/10/15
 * Time: 15:03
 */

namespace Console\Command;


use Console\ApplicationAwareCommand;
use Model\User\LookUpModel;
use Model\User\Placeholder\PlaceholderUserManager;
use Model\User\SocialNetwork\SocialProfile;
use Model\User\SocialNetwork\SocialProfileManager;
use Model\UserModel;
use Service\AMQPManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersSocialMediaAddCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('users:social-media:add')
            ->setDescription('Creates a social profile for an user')
            ->addArgument('resource' , InputArgument::REQUIRED, 'Socialnetwork to add')
            ->addArgument('url' , InputArgument::REQUIRED, 'The url of the social media')
            ->addOption('id' , null, InputOption::VALUE_REQUIRED, 'Social network to add', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $url = $input->getArgument('url');
        $resource = $input->getArgument('resource');
        $id = $input->getOption('id');

        /** @var SocialProfileManager $socialProfileManager */
        $socialProfileManager = $this->app['users.socialprofile.manager'];
        $socialProfiles = $socialProfileManager->getByUrl($url);

        if (count($socialProfiles) == 0) {

            $output->writeln('Creating new social profile with that url');

            if ($id) {
                /** @var UserModel $userModel */
                $userModel = $this->app['users.model'];
                $user = $userModel->getById((integer)$id);
                $id = $user['qnoow_id'];
                $output->writeln('SUCCESS: Found user with that id.');
            } else {
                /** @var PlaceholderUserManager $placeholderManager */
                $placeholderManager = $this->app['users.placeholder.manager'];
                $user = $placeholderManager->create();
                $id = $user->getId();
                $output->writeln('SUCCESS: Created ghost user with id:'.$id);
            }

            /** @var LookUpModel $lookupModel */
            $lookupModel = $this->app['users.lookup.model'];

            $lookupModel->setSocialProfiles(array(
                $resource => $url,
            ), $id);

            /** @var SocialProfileManager $socialProfileManager $sps */
            $socialProfiles = $socialProfileManager->getByUrl($url);

        } else {
            $output->writeln('Found an already existing social profile with that url');
        }

        $output->writeln('Enqueuing fetching');

        /* @var $amqpManager AMQPManager */
        $amqpManager = $this->app['amqpManager.service'];

        /** @var SocialProfile $socialProfile */
        foreach ($socialProfiles as $socialProfile) {
            $amqpManager->enqueueMessage(array(
                'userId' => $socialProfile->getUserId(),
                'resourceOwner' => $socialProfile->getResource(),
                'public' => true,
            ), 'brain.fetching.links');
        }

        $output->writeln('Success!');
    }
}