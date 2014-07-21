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
        $entityManager = $this->app['orm.ems']['mysql_brain'];
        $registry = new Registry($entityManager);
        $storage  = new DBStorage($this->app['links.model']);
        $consumer = $this->getConsumer($this->app, $resource);

        try {
            $linksGroupByUser = $consumer->fetchLinks();

            foreach ($linksGroupByUser as $userId => $userLinks) {
                $registry->registerFetchAttempt(
                    $userId,
                    $resource,
                    $userLinks,
                    false
                );
            }
        } catch (\Exception $e) {
            $logger->addError(sprintf('Error fetching from resource %s', $resource));
            $output->writeln($e->getMessage());
        }

        $storage->storeLinks($linksGroupByUser);

        $errors = $storage->getErrors();
        if (array() !== $errors) {
            foreach ($errors as $error) {
                $logger->addError(sprintf('Error: %s', $error));
            }
        }

        $output->writeln('Success!');
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

        if ($resource == 'twitter') {
            $options = array(
                'oauth_consumer_key'    => $app['twitter.consumer_key'],
                'oauth_consumer_secret' => $app['twitter.consumer_secret'],
            );
        }

        return ConsumerFactory::create($resource, $userProvider, $httpClient, $options);
    }

}
