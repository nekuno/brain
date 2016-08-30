<?php

namespace Console\Command;

use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
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
                    new InputArgument('limit', InputArgument::OPTIONAL, 'Items limit', 9999999)

                )
            )
            ->addOption('all', null, InputOption::VALUE_NONE, 'Process again all links, not only unprocessed ones')
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Links to skip from oldest', 0)
            ->addOption('url-contains', null, InputOption::VALUE_REQUIRED, 'Condition to filter url');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $linksModel LinkModel */
        $linksModel = $this->app['links.model'];

        $maxLimit = $input->getArgument('limit');
        $all = $input->getOption('all');
        $urlContains = $input->getOption('url-contains');
        $offset = $input->getOption('offset');

        $conditions = array();
        if (!$all) {
            $conditions[] = 'link.processed = 0';
        }
        if ($urlContains) {
            $conditions[] = 'link.url CONTAINS "' . $urlContains . '"';
        }

        $limit = 1000;
        do {
            $output->writeln(sprintf('Getting and analyzing %d urls from offset %d.', $limit, $offset));

            $links = $linksModel->getLinks($conditions, $offset, $limit);

            /* @var $preprocessedLinks PreprocessedLink[] */
            $preprocessedLinks = array();
            foreach ($links as $link) {
                $preprocessedLink = new PreprocessedLink($link['url']);
                $preprocessedLink->setLink($link);
                $preprocessedLinks[] = $preprocessedLink;
            }

            $twitterParser = new TwitterUrlParser();
            $twitterProfiles = array();

            foreach ($preprocessedLinks as $preprocessedLink) {

                try {
                    /* @var LinkProcessor $processor */
                    $processor = $this->app['api_consumer.link_processor'];

                    if ($twitterParser->getUrlType($preprocessedLink->getFetched()) === TwitterUrlParser::TWITTER_PROFILE){
                        $twitterProfiles[] = $preprocessedLink;
                        if (count($twitterProfiles) >= 100){
                            $processedLinks= $this->app['api_consumer.link_processor.processor.twitter']->processMultipleProfiles($twitterProfiles);
                            $twitterProfiles = array();
                        } else {
                            continue;
                        }
                    } else {
                        $processedLinks = array($processor->process($preprocessedLink, $all));
                    }

                    foreach ($processedLinks as $processedLink){
                        $processed = array_key_exists('processed', $processedLink) ? $processedLink['processed'] : 1;
                        if ($processed) {
                            $output->writeln(sprintf('Success: Link %s processed', $preprocessedLink->getFetched()));

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

                        } else {
                            $output->writeln(sprintf('Failed request: Link %s not processed', $preprocessedLink->getFetched()));
                        }
                    }

                } catch (\Exception $e) {
                    $output->writeln(sprintf('Error: %s', $e->getMessage()));
                    $output->writeln(sprintf('Error: Link %s not processed', $preprocessedLink->getFetched()));
                    $processedLink = $preprocessedLink->getLink();
                    $processedLink['url'] = $preprocessedLink->getFetched();
                    $processedLink['processed'] = 0;
                    continue;
                }
            }

            $offset += $limit;
        } while ($offset < $maxLimit && !empty($links));
    }
}
