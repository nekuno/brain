<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Psr\Log\LoggerInterface;
use Service\EnqueueLinksService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RabbitMQEnqueueLinksReprocessCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'rabbitmq:enqueue:links-reprocess';

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
            ->setDescription('Reprocess links with processed to 0.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->enqueueLinksService->enqueueLinksReprocess($output);
        $output->writeln('Done!');
    }
}
