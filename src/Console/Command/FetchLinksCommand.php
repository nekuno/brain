<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 11:36 AM
 */

namespace Console\Command;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\History\Registry;
use ApiConsumer\Restful\Consumer\ConsumerFactory;
use ApiConsumer\Scraper\Scraper;
use ApiConsumer\Storage\DBStorage;
use ApiConsumer\TempFakeService;
use Goutte\Client;
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

        $storage      = new DBStorage($this->app['links.model']);
        $consumer = $this->getConsumer($this->app, $resource);

        try {
            $linksGroupByUser = $consumer->fetchLinks();

            foreach ($linksGroupByUser as $userId => $userLinks) {
                $registry = new Registry($this->app['orm.ems']['mysql_brain']);
                $registry->recordFetchAttempt($userId, $resource);
            }
        } catch (\Exception $e) {
            $this->app['monolog']
                ->addError(sprintf('Error fetching user for userId %d from resource %s', $userId, $resource));
            $output->writeln($e->getMessage());
            exit;
        }

        try {
            $scraper = new Scraper(new Client());
            $tempFakeService = new TempFakeService($scraper);

            $processedLinks = $tempFakeService->processLinks($linksGroupByUser);

            $storage->storeLinks($processedLinks);

            $errors = $storage->getErrors();
            if (array() !== $errors) {
                foreach ($errors as $error) {
                    $this->app['monolog']->addError($error);
                }
            }

            $output->writeln('Success!');
        } catch (\Exception $e) {
            $output->writeln(sprintf('Error: %s', $e->getMessage()));
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
        $httpClient = $app['guzzle.client'];

        $options = array();

        if ($resource == 'twitter') {
            $options = array(
                'oauth_consumer_key' => $app['twitter.consumer_key'],
                'oauth_consumer_secret' => $app['twitter.consumer_secret'],
            );
        }

        return ConsumerFactory::create($resource, $userProvider, $httpClient, $options);
    }

}
