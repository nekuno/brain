<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User\TokensModel;
use Service\UserAggregator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersSocialMediaFetchFromFollowingCommand extends ApplicationAwareCommand
{
    protected function configure()
    {
        $this->setName('users:social-media:fetch-from-following')
            ->setDescription('Fetches content from users that given user follows')
            ->addArgument('id', InputArgument::REQUIRED, 'Id of user who is following people')
            ->addOption('resource', null, InputOption::VALUE_REQUIRED, 'Limit users to those who given user are following in this social network', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');
        $resource = $input->getOption('resource');
        $resources = $resource ? array($resource) : array();

        $output->writeln('Finding users.');
        $users = $this->app['users.manager']->getFollowingFrom($id, $resources);
        $output->writeln(sprintf('User %d is following %d users in our database.', $id, count($users)));

        //TODO: Optimize this with one query getSocialProfilesFromUsers(array($userId), $resource);
        foreach ($users as $user)
        {
            $socialProfiles = $this->app['users.socialprofile.manager']->getSocialProfiles($user->getId(), $resource);
            if ($socialProfiles){
                $this->app['userAggregator.service']->enqueueChannel($socialProfiles, $user->getUsername(), true);
            }
        }

        $output->writeln('All users enqueued for fetching as channels.');

    }

}