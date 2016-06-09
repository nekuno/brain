<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Neo4j\Neo4jException;
use Model\Popularity\PopularityManager;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
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
        /* @var $modelObject PopularityManager */
        $modelObject = $this->app['popularity.manager'];

        $userId = $input->getArgument('user');
        
        try {
            $modelObject->updatePopularityByUser((integer)$userId);

        } catch (\Exception $e) {
            $output->writeln(
                'Error trying to recalculate popularity with message: ' . $e->getMessage()
            );
            if ($e instanceof Neo4jException){
                $output->writeln('Query: '.$e->getQuery());
            }

            return;
        }

        $output->writeln('Done.');

    }
}
