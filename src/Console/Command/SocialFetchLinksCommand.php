<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 11:36 AM
 */

namespace Console\Command;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Restful\Consumer\FacebookConsumer;
use ApiConsumer\Restful\Consumer\GoogleConsumer;
use ApiConsumer\Restful\Consumer\TwitterConsumer;
use ApiConsumer\Storage\DBStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SocialFetchLinksCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('social:fetch:links')
            ->setDescription("Fetch data from given provider")
            ->setDefinition(
                array(
                    new InputOption('resource', null, InputOption::VALUE_REQUIRED, 'Resource owner'),
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

        $storage      = new DBStorage($this->app['content.model']);
        $userProvider = new DBUserProvider($this->app['db']);
        $httpClient   = $this->app['guzzle.client'];

        $options = array();

        if ($resource == 'twitter') {
            $options = array(
                'oauth_consumer_key'    => $this->app['twitter.consumer_key'],
                'oauth_consumer_secret' => $this->app['twitter.consumer_secret'],
            );
        }

        switch($resource){
            case 'twitter':
                $consumer = new TwitterConsumer($userProvider, $httpClient, $options);
                break;
            case 'facebook':
                $consumer = new FacebookConsumer($userProvider, $httpClient);
                break;
            case 'google':
                $consumer = new GoogleConsumer($userProvider, $httpClient);
                break;
            default:
                throw new \Exception('Invalid consumer');
        }

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
