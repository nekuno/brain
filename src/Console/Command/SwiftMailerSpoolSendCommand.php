<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SwiftMailerSpoolSendCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'swiftmailer:spool:send';

    /**
     * @var \Swift_Spool
     */
    protected $mailerSpool;

    /**
     * @var \Swift_Transport
     */
    protected $mailerTransport;

    public function __construct(LoggerInterface $logger, \Swift_Spool $mailerSpool, \Swift_Transport $mailerTransport)
    {
        parent::__construct($logger);
        $this->mailerSpool = $mailerSpool;
        $this->mailerTransport = $mailerTransport;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send spool messages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mailerSpool->flushQueue($this->mailerTransport);

        $output->writeln('Spool sent.');
    }

}
