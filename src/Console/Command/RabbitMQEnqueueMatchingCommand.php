<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\UserModel;
use Service\AMQPManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Worker\MatchingCalculatorWorker;
use Worker\PredictionWorker;

class RabbitMQEnqueueMatchingCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('rabbitmq:enqueue:matching')
            ->setDescription('Enqueues a matching taks for all users')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $usersModel UserModel */
        $usersModel = $this->app['users.model'];

        $combinations = $usersModel->getAllCombinations();
        
        /* @var $amqpManager AMQPManager */
        $amqpManager = $this->app['amqpManager.service'];

        $routingKey = 'brain'.
                    '.'.AMQPManager::MATCHING .
                    '.'.MatchingCalculatorWorker::TRIGGER_PERIODIC;

        foreach ($combinations as $combination){
            if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                $output->writeln(sprintf('Enqueuing matching and similarity task for users %d an %d', $combination[0], $combination[1]));
                $data = array(
                    'user_1_id' => $combination[0],
                    'user_2_id' => $combination[1]);
                $amqpManager->enqueueMessage($data, $routingKey);
            }
        }

    }
}