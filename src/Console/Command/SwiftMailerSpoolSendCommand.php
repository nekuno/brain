<?php

namespace Console\Command;

use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SwiftMailerSpoolSendCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('swiftmailer:spool:send')
            ->setDescription('Send spool messages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app['swiftmailer.spooltransport']->getSpool()->flushQueue($this->app['swiftmailer.transport']);

        $output->writeln('Spool sent.');

    }

}
