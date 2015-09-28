<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\LinkModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class LinksCalculatePopularity extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('links:calculate:popularity')
            ->setDescription('Calculate the popularity of the links.')
            ->setDefinition(
                array(
                    new InputOption(
                        'limit',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Maximum number of links to recalculate'
                    ),
                    new InputOption(
                        'user',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'ID of the user to recalculate links from'
                    ),
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $modelObject LinkModel */
        $modelObject = $this->app['links.model'];

        $filters = array();

        $limit = $input->getOption('limit');
        if (null !== $limit) {
            $filters['limit'] = $limit;
        }

        $userId = $input->getOption('user');
        if (null !== $userId) {
            $filters['userId'] = $userId;
        }

        try {
            $modelObject->updatePopularity($filters);

        } catch (\Exception $e) {
            $output->writeln(
                'Error trying to recalculate popularity with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Done.');

    }
}
