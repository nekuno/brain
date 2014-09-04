<?php

namespace Console\Command;

use ApiConsumer\LinkProcessor\LinkProcessor;
use Model\LinkModel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScrapLinksMetadataCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('scrap:links')
            ->setDescription("Scrap links metadata")
            ->setDefinition(
                array(
                    new InputArgument('limit', InputArgument::OPTIONAL, 'Items limit', 100)
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var LinkModel $linksModel */
        $linksModel = $this->app['links.model'];

        $limit = $input->getArgument('limit');

        $unprocessedLinks = $linksModel->getUnprocessedLinks($limit);

        foreach ($unprocessedLinks as $link) {

            try {
                /** @var LinkProcessor $processor */
                $processor = $this->app['api_consumer.link_processor'];
                $processedLink = $processor->process($link);
                $output->writeln(sprintf('Success: Link %s processed', $link['url']));
            } catch (\Exception $e) {
                $output->writeln(sprintf('Error: %s', $e->getMessage()));
                $output->writeln(sprintf('Error: Link %s not processed', $link['url']));
                $linksModel->updateLink($link, true);
                continue;
            }

            try {
                $linksModel->updateLink($processedLink, true);

                if (isset($processedLink['tags'])) {
                    foreach ($processedLink['tags'] as $tag) {
                        $linksModel->createTag($tag);
                        $linksModel->addTag($processedLink, $tag);
                    }
                }

                $output->writeln(sprintf('Success: Link %s saved', $processedLink['url']));
            } catch (\Exception $e) {
                $output->writeln(sprintf('Error: Link %s not saved', $processedLink['url']));
                $output->writeln($e->getMessage());
            }
        }
    }
}
