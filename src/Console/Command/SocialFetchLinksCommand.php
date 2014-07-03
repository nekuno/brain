<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 11:36 AM
 */

namespace Console\Command;

use Social\API\Consumer\Auth\DBUserProvider;
use Social\API\Consumer\Storage\DBStorage;
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

        $FQNClassName = 'Social\\API\\Consumer\\' . ucfirst($resource) . 'Consumer';

        $storage      = new DBStorage($this->app['content.model']);
        $userProvider = new DBUserProvider($this->app['db']);
        $httpClient   = $this->app['guzzle.client'];

        /** @var $FQNClassName $consumer */
        $consumer = new $FQNClassName($storage, $userProvider, $httpClient);

        try {
            $consumer->fetchLinks();
            $output->writeln('Success!');
        } catch (\Exception $e) {
            $output->writeln(sprintf('Error: %s', $e->getMessage()));
        }
    }

} 
