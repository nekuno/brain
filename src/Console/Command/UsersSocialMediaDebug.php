<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UsersSocialMediaDebug extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('users:social-media:debug')
            ->setDescription('Debug linkedin')
            ->addArgument('url', InputArgument::REQUIRED, 'Linkedin url', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $url = $input->getArgument('url');
        $parser = $this->app['parser.linkedin'];
        $result = $parser->parse($url);
        var_dump($result);

    }

}