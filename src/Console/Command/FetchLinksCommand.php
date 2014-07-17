<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 11:36 AM
 */

namespace Console\Command;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Restful\Consumer\ConsumerFactory;
use ApiConsumer\Storage\DBStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchLinksCommand extends ContainerAwareCommand
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

        if (!$resource) {
            $output->writeln('Error: --resource=<resource> option is needed.');
            exit;
        }

        $storage      = new DBStorage($this->app['links.model']);
        $userProvider = new DBUserProvider($this->app['dbs']['mysql_social']);
        $httpClient   = $this->app['guzzle.client'];

        $options = array();

        if ($resource == 'twitter') {
            $options = array(
                'oauth_consumer_key'    => $this->app['twitter.consumer_key'],
                'oauth_consumer_secret' => $this->app['twitter.consumer_secret'],
            );
        }

        $consumer = ConsumerFactory::create($resource, $userProvider, $httpClient, $options);

        try {
            $links = $consumer->fetchLinks();

            $storage->storeLinks($links);

            $errors = $storage->getErrors();

            if (array() !== $errors) {
                foreach ($errors as $error) {
                    $output->writeln($error);
                }
            }

            $output->writeln('Success!');
        } catch (\Exception $e) {
            $output->writeln(sprintf('Error: %s', $e->getMessage()));
        }
    }
}
