<?php

namespace Console\Command;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\EventListener\FetchLinksSubscriber;
use ApiConsumer\Fetcher\FetcherService;
use Psr\Log\LogLevel;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Exception\MissingOptionsException;

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
                        InputOption::VALUE_OPTIONAL,
                        'The resource owner which should fetch links'
                    ),
                    new InputOption(
                        'user',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'ID of the user to fetch links from'
                    ),
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $resource = $input->getOption('resource', null);
        $userId = $input->getOption('user', null);

        if (null === $resource && null === $userId) {
            throw new MissingOptionsException ("You must provide the user or the resource to fetch links from", array("resource", "user"));
        }

        if (null !== $resource) {
            $resourceOwners = $this->app['api_consumer.config']['resource_owner'];
            $availableResourceOwners = implode(', ', array_keys($resourceOwners));

            if (!isset($resourceOwners[$resource])) {
                $output->writeln(sprintf('Resource ownner %s not found, available resource owners: %s.', $resource, $availableResourceOwners));

                return;
            }
        }

        $userProvider = $this->app['api_consumer.user_provider'];

        /* @var $userProvider DBUserProvider */
        $users = $userProvider->getUsersByResource($resource, $userId);

        /* @var FetcherService $fetcher */
        $fetcher = $this->app['api_consumer.fetcher'];

        $logger = new ConsoleLogger($output, array(LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL));
        $fetcher->setLogger($logger);

        $fetchLinksSubscriber = new FetchLinksSubscriber($output);
        $dispatcher = $this->app['dispatcher'];
        /* @var $dispatcher EventDispatcher */
        $dispatcher->addSubscriber($fetchLinksSubscriber);

        foreach ($users as $user) {
            try {

                $fetcher->fetch($user['id'], $user['resourceOwner']);

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
        $output->writeln(sprintf('Memory consumed: %s', $this->formatBytes(memory_get_peak_usage(true))));
    }
}
