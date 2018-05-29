<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Parser\LinkedinParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UsersSocialMediaDebug extends ApplicationAwareCommand
{
    protected static $defaultName = 'users:social-media:debug';

    /**
     * @var LinkedinParser
     */
    protected $linkedinParser;

    public function __construct(LoggerInterface $logger, LinkedinParser $linkedinParser)
    {
        parent::__construct($logger);
        $this->linkedinParser = $linkedinParser;
    }

    protected function configure()
    {
        $this
            ->setDescription('Debug linkedin')
            ->addArgument('url', InputArgument::REQUIRED, 'Linkedin url', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');
        $result = $this->linkedinParser->parse($url);
        var_dump($result);
    }

}