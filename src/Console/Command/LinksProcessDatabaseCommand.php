<?php

namespace Console\Command;

use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Console\ApplicationAwareCommand;
use Model\LinkModel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LinksProcessDatabaseCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('links:process:database')
            ->setDescription('Reprocess already saved and unprocessed links')
            ->setDefinition(
                array(
                    new InputArgument('limit', InputArgument::OPTIONAL, 'Items limit', 100)

                )
            )
            ->addOption('all', null, InputOption::VALUE_NONE, 'Process again all links, not only unprocessed ones');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $linksModel LinkModel */
        $linksModel = $this->app['links.model'];

        $limit = $input->getArgument('limit');
        $all = $input->getOption('all');

        if ($all){
            $links = $linksModel->findAllLinks();
            foreach ($links as &$link){
                if (!isset($link['url'])){
                    continue;
                }
                $link['tempId'] = $link['url'];
            }
        } else {
            $links = $linksModel->getUnprocessedLinks($limit);
        }

        /* @var $preprocessedLinks PreprocessedLink[] */
        $preprocessedLinks = $linksModel->buildPreprocessedLinks($links);

        $output->writeln('Got '.count($links).' links to process');

        foreach ($preprocessedLinks as $preprocessedLink) {

            try {
                /* @var LinkProcessor $processor */
                $processor = $this->app['api_consumer.link_processor'];
                $processedLink = $processor->process($preprocessedLink, $all);

                $processed = array_key_exists('processed', $processedLink)? $processedLink['processed'] : 1;
                if ($processed){
                    $output->writeln(sprintf('Success: Link %s processed', $preprocessedLink->getFetched()));
                } else {
                    $output->writeln(sprintf('Failed request: Link %s not processed', $preprocessedLink->getFetched()));
                }

            } catch (\Exception $e) {
                $output->writeln(sprintf('Error: %s', $e->getMessage()));
                $output->writeln(sprintf('Error: Link %s not processed', $preprocessedLink->getFetched()));
                $processedLink = $preprocessedLink->getLink();
                $processedLink['url'] = $preprocessedLink->getFetched();
                $processedLink['processed'] = 0;
                continue;
            }

            try {
                $linksModel->addOrUpdateLink($processedLink);

                if (isset($processedLink['tags'])) {
                    foreach ($processedLink['tags'] as $tag) {
                        $linksModel->createTag($tag);
                        $linksModel->addTag($processedLink, $tag);
                    }
                }

                $output->writeln(sprintf('Success: Link %s saved', $preprocessedLink->getFetched()));

            } catch (\Exception $e) {
                $output->writeln(sprintf('Error: Link %s not saved', $preprocessedLink->getFetched()));
                $output->writeln($e->getMessage());
            }
        }
    }
}
