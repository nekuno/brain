<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 11:36 AM
 */

namespace Console\Command;

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

    protected function execute(InputInterface $input, OutputInterface $output) {

        $resource = $input->getOption('resource');

        if(!$resource){
            $output->writeln('Error: --resource=<resource> option is needed.');
            exit;
        }

        $FQNClassName = '\\Social\\Consumer\\' . ucfirst($resource) . 'FeedConsumer';
        $consumer = new $FQNClassName($this->app);

        try {
            $consumer->fetchLinks();
            $output->writeln('Success!');
        } catch(\Exception $e) {
            $output->writeln(sprintf('Error: %s', $e->getMessage()));
        }
    }

} 