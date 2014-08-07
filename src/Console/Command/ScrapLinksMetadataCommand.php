<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 7/22/14
 * Time: 6:33 PM
 */

namespace Console\Command;

use ApiConsumer\Scraper\LinkProcessor;
use ApiConsumer\Scraper\Scraper;
use Goutte\Client;
use Model\LinkModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScrapLinksMetadataCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('scrap:links')
             ->setDescription("Scrap links metadata")
             ->setDefinition(
                 array()
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var LinkModel $linksModel */
        $linksModel       = $this->app['links.model'];
        $unprocessedLinks = $linksModel->getUnprocessedLinks();

        if (count($unprocessedLinks) > 0) {
            foreach ($unprocessedLinks as $link) {

                try {
                    /** @var LinkProcessor $processor */
                    $processor = $this->app['link_processor'];
                    $processedLink = $processor->processLink($link);
                    $output->writeln(sprintf('Success: Link %s processed', $link['url']));
                } catch (\Exception $e) {
                    $output->writeln(sprintf('Error: %s', $e->getMessage()));
                    $output->writeln(sprintf('Error: Link %s not processed', $link['url']));
                    $linksModel->updateLink($link, true);
                    continue;
                }

                try {
                    $linksModel->updateLink($processedLink, true);
                    foreach ($processedLink['tags'] as $tag) {
                        $tag = array('name' => $tag);
                        $linksModel->createTag($tag);
                        $linksModel->addTag($processedLink, $tag);
                    }

                    $output->writeln(sprintf('Success: Link %s saved', $processedLink['url']));
                } catch (\Exception $e) {
                    $output->writeln(sprintf('Error: Link %s not saved', $processedLink['url']));
                }

            }
            call_user_func_array(array($this, 'execute'), array($input, $output));
        }
    }
}
