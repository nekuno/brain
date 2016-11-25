<?php

namespace Console\Command;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\LinkAnalyzer;
use Console\ApplicationAwareCommand;
use Event\ConsistencyEvent;
use EventListener\ConsistencySubscriber;
use Everyman\Neo4j\Query\ResultSet;
use Model\Neo4j\Neo4jException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LinksFindDuplicatesCommand extends ApplicationAwareCommand
{
    protected function configure()
    {
        $this->setName('links:find-duplicates')
            ->setDescription('Return links with identical URLs')
            ->addOption('fuse', null, InputOption::VALUE_NONE, 'Automatically fuse found duplicates')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Amount of links analyzed', 99999999)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Links to skip from oldest', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting database search.');

        $linkModel = $this->app['links.model'];

        $maxLimit = $input->getOption('limit');
        $offset = $input->getOption('offset');

        $output->writeln('Finding duplicates');

        $limit = 10000;
        do {
            $output->writeln('--------------------------------------------------------------------------------');
            $output->writeln(sprintf('Getting and analyzing %d urls from offset %d.', $limit, $offset));

            $links = $linkModel->getLinks(array(), $offset, $limit);

            foreach ($links as &$link) {
                $link = $this->updateURL($link, $output);
            }

            $duplicates = $linkModel->findDuplicates($links);

            $numDuplicates = count($duplicates);

            if ($numDuplicates > 0) {
                $output->writeln(sprintf('%d duplicated links found.', $numDuplicates));

                $this->analyzeDuplicates($duplicates, $input, $output);
            }

            $offset += $limit;
        } while ($offset < $maxLimit && !empty($links));

        $output->writeln('Done.');
    }

    /**
     * @param array $duplicates
     * @param $input InputInterface
     * @param $output OutputInterface
     */
    private function analyzeDuplicates(array $duplicates, $input, $output)
    {
        $gm = $this->app['neo4j.graph_manager'];
        $linkModel = $this->app['links.model'];
        $dispatcher = $this->app['dispatcher'];
        $dispatcher->addSubscriber(new ConsistencySubscriber($this->app['consistency.service'], $this->app['popularity.manager']));

        $errors = array();
        foreach ($duplicates as $duplicate) {
            $mainId = (integer)$duplicate['main']['id'];
            $duplicateURL = $duplicate['duplicate']['url'];
            $duplicateId = (integer)$duplicate['duplicate']['id'];

            $output->writeln(sprintf('Link with id %d and url %s is a duplicate of link with id %d', $duplicateId, $duplicateURL, $mainId));

            if ($input->getOption('fuse')) {
                $output->writeln('Fusing duplicate into main node');

                try {
                    $fusion = $gm->fuseNodes($duplicateId, $mainId);

                    /* @var ResultSet $deletionRS */
                    $deletionRS = $fusion['deleted'];
                    if ($deletionRS->count() > 0) {
                        $dispatcher->dispatch(\AppEvents::CONSISTENCY_LINK, new ConsistencyEvent($mainId));
                        $output->writeln('Duplicate and main node successfully fused');
                    } else {
                        $output->writeln('Nodes were not fused');
                    }
                } catch (Neo4jException $e) {
                    $errors[] = array(
                        'duplicateId' => $duplicateId,
                        'mainId' => $mainId,
                        'reason' => $e->getMessage(),
                        'query' => $e->getQuery()
                    );
                } catch (\Exception $e) {
                    $errors[] = array(
                        'duplicateId' => $duplicateId,
                        'mainId' => $mainId,
                        'reason' => $e->getMessage()
                    );
                }

                $output->writeln('Cleaning inconsistencies');
                $cleaned = $linkModel->cleanInconsistencies($mainId);
                if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                    $output->writeln('Like-dislike conflicts solved: ' . $cleaned['dislikes']);
                    $output->writeln('Like-affinity conflicts solved: ' . $cleaned['affinities']);
                }
            }
        }

        foreach ($errors as $error) {
            $output->writeln(sprintf('Error fusing nodes %d and %d. Reason: %s', $error['duplicateId'], $error['mainId'], $error['reason']));
            if (isset($error['query'])) {
                $output->writeln(sprintf('Neo4j Query: %s', $error['query']));
            }
        }

        $output->writeln('Finished.');
    }

    private function updateURL($link, OutputInterface $output)
    {
        if (!isset($link['url'])) {
            return false;
        }

        try {
            $cleanUrl = LinkAnalyzer::cleanUrl($link['url']);
        } catch (UrlNotValidException $e) {
            //TODO: log
            $output->writeln(sprintf('Could not clean URL %s', $link['url']));
            return false;
        }

        $linkModel = $this->app['links.model'];

        if ($cleanUrl !== $link['url']) {
            $output->writeln('Changing ' . $link['url'] . ' to ' . $cleanUrl);
            $linkModel->changeUrl($link['url'], $cleanUrl);
        }

        return $link;
    }
}