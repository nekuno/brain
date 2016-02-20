<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Manager\UserManager;
use Service\AMQPManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Worker\MatchingCalculatorWorker;

class RabbitMQEnqueueMatchingCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('rabbitmq:enqueue:matching')
            ->setDescription('Enqueues a matching taks for all users')
            ->addArgument('userA', InputArgument::OPTIONAL, 'id of the first user?')
            ->addArgument('userB', InputArgument::OPTIONAL, 'id of the second user?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $userA = $input->getArgument('userA');
        $userB = $input->getArgument('userB');

        if ($userA == $userB){
            $output->writeln('The two users must be different.');
            return;
        }
        $combinations = array(
            array(
                0 => $userA,
                1 => $userB
            )
        );

        if (null === $userA || null === $userB) {
            /* @var $userManager UserManager */
            $userManager = $this->app['users.manager'];
            $combinations = $userManager->getAllCombinations(false);
        }

        /* @var $amqpManager AMQPManager */
        $amqpManager = $this->app['amqpManager.service'];

        $routingKey = 'brain' .
            '.' . AMQPManager::MATCHING .
            '.' . MatchingCalculatorWorker::TRIGGER_PERIODIC;

        foreach ($combinations as $combination) {
            if ($combination[0] == $combination[1]){
                continue;
            }
            $output->writeln(sprintf('Enqueuing matching and similarity task for users %d and %d', $combination[0], $combination[1]));
            $data = array(
                'user_1_id' => $combination[0],
                'user_2_id' => $combination[1]
            );
            $amqpManager->enqueueMessage($data, $routingKey);
        }

    }
}