<?php

namespace Console\Command;

use Model\User\Matching\MatchingModel;

use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RecalculatePopularity extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('popularity:recalculate')
            ->setDescription("Recalculate the popularity of the links.")
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'Number of links to recalculate'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var \Model\LinkModel $modelObject */
        $modelObject = $this->app['links.model'];

        $limit = $input->getArgument('limit');

        try {
            $modelObject->updatePopularity($limit);

        } catch (\Exception $e) {
            $output->writeln(
               'Error trying to recalculate popularity with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Done.');

    }
}
