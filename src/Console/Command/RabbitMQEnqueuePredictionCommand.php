<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User\UserManager;
use Model\User\User;
use Psr\Log\LoggerInterface;
use Service\AMQPManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Worker\PredictionWorker;

class RabbitMQEnqueuePredictionCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'rabbitmq:enqueue:prediction';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var AMQPManager
     */
    protected $AMQPManager;

    public function __construct(LoggerInterface $logger, UserManager $userManager, AMQPManager $AMQPManager)
    {
        parent::__construct($logger);
        $this->userManager = $userManager;
        $this->AMQPManager = $AMQPManager;
    }

    protected function configure()
    {

        $this
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

        if ($userId == null) {
            $users = $this->userManager->getAll();
        } else {
            $users = array($this->userManager->getById($userId, true));
        }
        if (empty($users)) {
            $output->writeln(sprintf('Not user found with id %d ', $userId));
            exit;
        }

        $mode = $input->getOption('mode');

        if (!in_array($mode, array(PredictionWorker::TRIGGER_LIVE, PredictionWorker::TRIGGER_RECALCULATE))) {
            throw new \Exception('Mode not supported');
        }

        foreach ($users as $user) {
            /* @var $user User */
            $output->writeln('Enqueuing prediction for user ' . $user->getId());
            $data = array('userId' => $user->getId());
            $this->AMQPManager->enqueuePrediction($data, $mode);
        }
    }
}