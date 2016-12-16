<?php

namespace Console\Command;

use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use Console\ApplicationAwareCommand;
use Model\Link;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
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
            ->addOption('url-contains', null, InputOption::VALUE_REQUIRED, 'Condition to filter url')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Extra label of links')
            ->addOption('batch', null, InputOption::VALUE_NONE, 'Use batch processing instead of processing service');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $linksModel = $this->app['links.model'];

        $maxLimit = $input->getArgument('limit');
        $all = $input->getOption('all');
        $urlContains = $input->getOption('url-contains');
        $label = $input->getOption('label');
        $offset = $input->getOption('offset');
        $batch = $input->getOption('batch');

        $conditions = array();
        if (!$all) {
            $conditions[] = 'link.processed = 0';
        }
        if ($urlContains) {
            $conditions[] = 'link.url CONTAINS "' . $urlContains . '"';
        }
        if ($label) {
            $conditions[] = 'link:' . $label;
        }

        $limit = 1000;
        do {
            $output->writeln(sprintf('Getting and analyzing %d urls from offset %d.', $limit, $offset));

            $links = $linksModel->getLinks($conditions, $offset, $limit);

            /* @var $preprocessedLinks PreprocessedLink[] */
            $preprocessedLinks = array();
            foreach ($links as $link) {
                $preprocessedLink = new PreprocessedLink($link['url']);
                $preprocessedLink->addLink(Link::buildFromArray($link));
                $preprocessedLinks[] = $preprocessedLink;
            }

            if ($batch) {
                $this->batchProcess($preprocessedLinks, $output);
            } else {
                $this->serviceProcess($preprocessedLinks, $output);
            }

            $offset += $limit;
        } while ($offset < $maxLimit && !empty($links));

    }

    private function batchProcess(array $preprocessedLinks, OutputInterface $output)
    {
        $twitterParser = new TwitterUrlParser();
        $twitterProfiles = array();
        $twitterIntents = array();

        $linksModel = $this->app['links.model'];

        foreach ($preprocessedLinks as $preprocessedLink) {

            try {
                /* @var LinkProcessor $processor */
                $processor = $this->app['api_consumer.link_processor'];

                $cleanUrl = LinkAnalyzer::cleanUrl($preprocessedLink->getFetched());

                switch ($twitterParser->getUrlType($cleanUrl)) {
                    case TwitterUrlParser::TWITTER_PROFILE:
                        $preprocessedLink->setCanonical($cleanUrl);
                        $twitterProfiles[] = $preprocessedLink;
                        if (count($twitterProfiles) >= 100) {
                            $processedLinks = $this->app['api_consumer.processor.factory']->build(TwitterUrlParser::TWITTER_PROFILE)->processMultipleProfiles($twitterProfiles);
                            $twitterProfiles = array();
                        } else {
                            $processedLinks = array();
                            continue;
                        }
                        break;
                    case TwitterUrlParser::TWITTER_INTENT:
                        $preprocessedLink->setCanonical($cleanUrl);
                        $twitterIntents[] = $preprocessedLink;
                        if (count($twitterIntents) >= 100) {
                            $processedLinks = $this->app['api_consumer.processor_factory']->build(TwitterUrlParser::TWITTER_INTENT)->processMultipleIntents($twitterIntents);
                            $twitterIntents = array();
                        } else {
                            $processedLinks = array();
                            continue;
                        }
                        break;
                    default:
                        $processedLinks = array($processor->process($preprocessedLink));
                        break;
                }

                foreach ($processedLinks as $processedLink) {
                    $processed = array_key_exists('processed', $processedLink) ? $processedLink['processed'] : 1;
                    if ($processed) {
                        $output->writeln(sprintf('Success: Link %s processed', $processedLink['url']));

                        try {
                            $linksModel->addOrUpdateLink($processedLink);

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
    }

    private function serviceProcess(array $preprocessedLinks, OutputInterface $output){
        $processorService = $this->app['api_consumer.processor'];

        $processorService->setLogger(new ConsoleLogger($output, array(LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL)));
        $processorService->reprocess($preprocessedLinks);
    }
}
