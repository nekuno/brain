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

        /** @var $logger Logger */
        $logger = $this->app['monolog'];

        $registry     = new Registry($this->app['orm.ems']['mysql_brain']);
        $storage      = new DBStorage($this->app['links.model']);
        $consumer = $this->getConsumer($this->app, $resource);

        $userProvider = new DBUserProvider($this->app['dbs']['mysql_social']);
        $users        = $userProvider->getUsersByResource($resource);

        foreach ($users as $user) {
            try {
                $consumer = $this->getConsumer($this->app, $resource);

                $logger->debug(sprintf('Fetch attempt for user %d, resource %s', $user['id'], $resource));

                $userSharedLinks = $consumer->fetchLinksFromUserFeed($user['id']);

                $storage->storeLinks($user['id'], $userSharedLinks);
                foreach ($storage->getErrors() as $error) {
                    $logger->error(sprintf('Error saving link: ' . $error));
                }

                $lastItemId = $userSharedLinks[count($userSharedLinks) - 1]['resourceItemId'];
                $registry->registerFetchAttempt(
                    $user['id'],
                    $resource,
                    $lastItemId,
                    false
                );
                $output->writeln('Success!');

            } catch (\Exception $e) {
                $logger->addError(sprintf('Error fetching from resource %s', $resource));
                $logger->error(sprintf('%s', $e->getMessage()));

                $registry->registerFetchAttempt(
                    $user['id'],
                    $resource,
                    null,
                    true
                );
                $output->writeln($e->getMessage());
            }
        }
    }

    /**
     * @param Application $app
     * @param $resource
     * @return \ApiConsumer\Restful\Consumer\LinksConsumerInterface
     * @throws \Exception
     */
    private function getConsumer(Application $app, $resource)
    {

        $userProvider = new DBUserProvider($app['dbs']['mysql_social']);
        $httpClient   = $app['guzzle.client'];

        $options = array();
        if (isset($app[$resource])) {
            $options = $app[$resource];
        }

        return ConsumerFactory::create($resource, $userProvider, $httpClient, $options);
    }

}
