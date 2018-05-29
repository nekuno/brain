<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Neo4j\Constraints;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jConstraintsCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'neo4j:constraints';

    /**
     * @var Constraints
     */
    protected $constraints;

    public function __construct(LoggerInterface $logger, Constraints $constraints)
    {
        parent::__construct($logger);
        $this->constraints = $constraints;
    }

    protected function configure()
    {
        $this->setName('neo4j:constraints')
            ->setDescription('Load neo4j database constraints');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->constraints->load();
        } catch (\Exception $e) {
            $output->writeln(
                'Error loading neo4j constraints with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Constraints created');
    }
}
