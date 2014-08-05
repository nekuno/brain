<?php

namespace Console\Command;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Registry\Registry;
use ApiConsumer\Restful\Consumer\ConsumerFactory;
use ApiConsumer\Storage\DBStorage;
use Monolog\Logger;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchLinksCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('fetch:links')
             ->setDescription("Fetch links from given resource owner")
             ->setDefinition(
             array(
                 new InputOption(
                     'resource',
                     null,
                     InputOption::VALUE_REQUIRED,
                     'The resource owner which should fetch links'
                 ),
             )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $resource = $input->getOption('resource');

        $userProvider = new DBUserProvider($this->app['dbs']['mysql_social']);
        $users = $userProvider->getUsersByResource($resource);

        $fetcher = $this->app['api_consumer.fetcher'];
        
        foreach ($users as $user) {
            try {
                
                $fetcher->fetch($user['id'], $resource);
                $output->writeln(sprintf('Fetched links for user %s from resource %s', $user['id'], $resource));

            } catch (\Exception $e) {
                $output->writeln(
                       sprintf(
                           'Error fetching links for user %s with message: ' . $e->getMessage(),
                           $user['id']
                       )
                );
                continue;
            }
        }

        $output->writeln('Success!');
    }
}
