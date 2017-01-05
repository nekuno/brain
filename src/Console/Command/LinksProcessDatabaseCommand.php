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
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Extra label of links');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $linksModel = $this->app['links.model'];

        $maxLimit = $input->getArgument('limit');
        $all = $input->getOption('all');
        $urlContains = $input->getOption('url-contains');
        $label = $input->getOption('label');
        $offset = $input->getOption('offset');

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
                $preprocessedLink->setFirstLink(Link::buildFromArray($link));
                $preprocessedLinks[] = $preprocessedLink;
            }

            $processorService = $this->app['api_consumer.processor'];
            $processorService->setLogger(new ConsoleLogger($output, array(LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL)));

            $processorService->reprocess($preprocessedLinks);

            $offset += $limit;
        } while ($offset < $maxLimit && !empty($links));

    }
}
