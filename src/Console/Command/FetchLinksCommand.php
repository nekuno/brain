<?php

namespace Console\Command;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Fetcher\FetcherService;
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
        $resourceOwners = $this->app['api_consumer.config']['resource_owner'];
        $availableResourceOwners = implode(', ', array_keys($resourceOwners));

        if (!$resource) {
            $output->writeln(sprintf('Resource owner is needed, available resource owners: %s.', $availableResourceOwners));
            return;
        }

        if (!isset($resourceOwners[$resource])) {
            $output->writeln(sprintf('Resource ownner %s not found, available resource owners: %s.', $resource, $availableResourceOwners));
            return;
        }

        $userProvider = $this->app['api_consumer.user_provider'];
        /* @var $userProvider DBUserProvider */
        $users = $userProvider->getUsersByResource($resource);

        /** @var FetcherService $fetcher */
        $fetcher = $this->app['api_consumer.fetcher'];

        foreach ($users as $user) {
            try {
                $fetcher->fetch($user['id'], $resource);
                $output->writeln(sprintf('Fetched links for user %s from resource %s', $user['id'], $resource));

            } catch (\Exception $e) {
                $output->writeln(
                    sprintf(
                        'Error fetching links for user %s with message: %s',
                        $user['id'],
                        $e->getMessage()
                    )
                );
                continue;
            }
        }

        $output->writeln('Success!');
    }
}
