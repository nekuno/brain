<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Psr\Log\LoggerInterface;
use Service\EnqueueLinksService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RabbitMQEnqueueLinksCheckCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'rabbitmq:enqueue:links-check';

    /**
     * @var EnqueueLinksService
     */
    protected $enqueueLinksService;

    public function __construct(LoggerInterface $logger, EnqueueLinksService $enqueueLinksService)
    {
        parent::__construct($logger);
        $this->enqueueLinksService = $enqueueLinksService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check links and set processed to 0 for those with errors.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->enqueueLinksService->enqueueLinksCheck($output);
        $output->writeln('Done!');
    }
}
