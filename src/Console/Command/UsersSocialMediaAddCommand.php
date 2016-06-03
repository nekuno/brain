<?php

namespace Console\Command;


use ApiConsumer\EventListener\FetchLinksInstantSubscriber;
use ApiConsumer\EventListener\FetchLinksSubscriber;
use Console\ApplicationAwareCommand;
use Model\User\TokensModel;
use Psr\Log\LogLevel;
use Service\UserAggregator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UsersSocialMediaAddCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('users:social-media:add')
            ->setDescription('Creates a social profile for an user')
            ->addArgument('resource', InputArgument::OPTIONAL, 'Social network to add')
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the user in the social media')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Id or name of user to add the social network to', null)
            ->addOption('url', null, InputOption::VALUE_OPTIONAL, 'Url of social network to add to user', null)
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Name of CSV file to read users from', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $username = $input->getArgument('username');
        $resource = $input->getArgument('resource');
        $id = $input->getOption('id');
        $url = $input->getOption('url');
        $file = $input->getOption('file');

        if (!$file) {
            if (!($username && $resource)) {
                $output->writeln('Please add an user identifier and a resource.');
                $output->writeln('Optionally use the file option to load a CSV file with users and resources');
                return;
            } else {
                $toLoad = array(array($username, $resource, $id, $url));
            }
        } else {
            $toLoad = $this->loadCSV($file);
        }

        foreach ($toLoad as $userToLoad)
        {
            $output->writeln('--------');
            $username = $userToLoad[0];
            $resource = $userToLoad[1];
            $id = $userToLoad[2];
            $url = $userToLoad[3];

            $output->writeln(sprintf('Loading user %s in %s', $username, $resource));

            /** @var UserAggregator $userAggregator */
            $userAggregator = $this->app['userAggregator.service'];

            $socialProfiles = $userAggregator->addUser($username, $resource, $id, $url);

            if (!$socialProfiles){
                $output->writeln(sprintf('Error while creating user with name %s to the resource %s and with the id %s and url %s',
                                    $username, $resource, $id, $url));
                continue;
            }

            $output->writeln('Enqueuing fetching from that resource as channel');
            $userAggregator->enqueueChannel($socialProfiles, $username);

            $amqpManager = $this->app['amqpManager.service'];

            /* @var $socialProfile \Model\User\SocialNetwork\SocialProfile */
            foreach ($socialProfiles as $socialProfile) {
                if ($socialProfile->getResource() == TokensModel::TWITTER) {
                    $output->writeln('Enqueuing fetching followers from that twitter account');

                    $data = array(
                        'userId' => $socialProfile->getUserId(),
                        'resourceOwner' => $socialProfile->getResource(),
                        'public' => true,
                        'exclude' => array('twitter_links', 'twitter_favorites'),
                    );
                    $amqpManager->enqueueMessage($data, 'brain.fetching.links');
                }
            }

            $output->writeln('Success!');
        }

        $output->writeln('Finished.');

    }

    private function loadCSV($file)
    {

        $users = array();
        $first = true;

        if (($handle = fopen($file, 'r')) !== false) {

            while (($data = fgetcsv($handle, 0, ';')) !== false) {

                if ($first) {
                    $first = false;
                    continue;
                }

                $users[] = array($data[1], $data[2], $data[3]);

            }
            fclose($handle);
        }

        return $users;

    }
}