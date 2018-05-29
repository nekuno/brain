<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Popularity\PopularityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LinksCalculatePopularity extends ApplicationAwareCommand
{
    protected static $defaultName = 'links:calculate:popularity';

    /**
     * @var PopularityManager
     */
    protected $popularityManager;

    public function __construct(LoggerInterface $logger, PopularityManager $popularityManager)
    {
        parent::__construct($logger);
        $this->popularityManager = $popularityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Calculate the popularity of the links.')
            ->setDefinition(
                array(
                    new InputArgument(
                        'user',
                        null,
                        'ID of the user to recalculate links from'
                    ),
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = $input->getArgument('user');
        
        try {
            $this->popularityManager->updatePopularityByUser((integer)$userId);

        } catch (\Exception $e) {
            $output->writeln(
                'Error trying to recalculate popularity with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Done.');

    }
}
