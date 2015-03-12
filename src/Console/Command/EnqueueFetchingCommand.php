<?php

namespace Console\Command;

use Model\UserModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EnqueueFetchingCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('rabbitmq:enqueue:fetching')
             ->setDescription('Enqueue an fetching task for all users')
             ->addOption(
                 'user',
                 null,
                 InputOption::VALUE_OPTIONAL,
                 'If set, only will enqueue process for given user'
             )->addOption(
                'resource',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, only will enqueue process for given resource owner'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $userId        = $input->getOption('user');
        $resourceOwner = $input->getOption('resource');

        $availableResourceOwners = $this->app['api_consumer.config']['resource_owner'];
        if($resourceOwner && !array_key_exists($resourceOwner, $availableResourceOwners)) {
            $output->writeln(sprintf('%s is not an valid resource owner', $resourceOwner));
            exit;
        }

        /** @var UserModel $usersModel */
        $usersModel = $this->app['users.model'];

        if ($userId == null) {
            $users = $usersModel->getAll();
        } else {
            $users = $usersModel->getById($userId);
        }

        if(empty($users)){
            $output->writeln(sprintf('Not user found with %d and resource %s connected', $userId, $resourceOwner));
            exit;
        }

        if ($resourceOwner == null) {
            $resourceOwners = array();
            foreach ($availableResourceOwners as $name => $config) {
                $resourceOwners[] = $name;
            }
        } else {
            $resourceOwners[] = $resourceOwner;
        }

        foreach ($users as $user) {
            foreach ($resourceOwners as $name) {
                $data = array(
                    'userId'        => $user['qnoow_id'],
                    'resourceOwner' => $name,
                );
                $this->enqueueFetchingProcess($data);
            }
        }
    }

    /**
     * @param $data
     */
    private function enqueueFetchingProcess($data)
    {

        /** @var AMQPStreamConnection $connection */
        $connection = $this->app['amqp'];
        $message    = new AMQPMessage(json_encode($data, JSON_UNESCAPED_UNICODE));

        $exchangeName = 'brain.direct';
        $exchangeType = 'direct';
        $routingKey   = 'brain.fetching.links';
        $queueName    = 'brain.fetching';

        $channel = $connection->channel();
        $channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $exchangeName);
        $channel->basic_publish($message, $exchangeName, $routingKey);
    }
}