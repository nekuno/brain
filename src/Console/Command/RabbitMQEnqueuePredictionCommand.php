<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Manager\UserManager;
use Model\User;
use Service\AMQPManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Worker\PredictionWorker;

class RabbitMQEnqueuePredictionCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('rabbitmq:enqueue:prediction')
            ->setDescription('Enqueues a prediction task for all users')
            ->addOption(
                'user',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, only will enqueue process for given user'
            )->addOption(
                'mode',
                null,
                InputOption::VALUE_OPTIONAL,
                'Prediction mode',
                PredictionWorker::TRIGGER_RECALCULATE
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $userId = $input->getOption('user');

        /* @var $usersModel UserManager */
        $usersModel = $this->app['users.manager'];

        if ($userId == null) {
            $users = $usersModel->getAll();
        } else {
            $users = array($usersModel->getById($userId, true));
        }
        if (empty($users)) {
            $output->writeln(sprintf('Not user found with id %d ', $userId));
            exit;
        }

        $mode = $input->getOption('mode');

        switch ($mode) {
            case PredictionWorker::TRIGGER_RECALCULATE:
            case PredictionWorker::TRIGGER_LIVE:
                $routingKey = 'brain.prediction.' . $mode;
                break;
            default:
                throw new \Exception('Mode not supported');
        }

        /* @var $amqpManager AMQPManager */
        $amqpManager = $this->app['amqpManager.service'];

        foreach ($users as $user) {
            /* @var $user User */
            $output->writeln('Enqueuing prediction for user '.$user->getId());
            $data = array('userId' => $user->getId());
            $amqpManager->enqueueMessage($data, $routingKey);
        }
    }
}