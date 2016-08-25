<?php


namespace Console\Command;

use ApiConsumer\LinkProcessor\LinkProcessor;
use Console\ApplicationAwareCommand;
use Everyman\Neo4j\Query\ResultSet;
use Model\LinkModel;
use Model\Neo4j\GraphManager;
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

        /* @var $linkModel LinkModel */
        $linkModel = $this->app['links.model'];

        $maxlimit = $input->getOption('limit');
        $offset = $input->getOption('offset');

        $output->writeln('Updating link URLs');
        $this->updateURLs($output, $linkModel);

        $output->writeln('Finding duplicates');

        $limit = 1000;
        while ($offset < $maxlimit) {

            $output->writeln(sprintf('Getting and analyzing %d urls from offset %d.', $limit, $offset));

            $duplicates = $linkModel->findDuplicates($offset, $limit);

            $numDuplicates = count($duplicates);

            if ($numDuplicates > 0) {
                $output->writeln(sprintf('%d duplicated links found.', $numDuplicates));

                $this->analyzeDuplicates($duplicates, $input, $output);
            }

            $offset += $limit;
        }

        $output->writeln('Done.');
    }

    /**
     * @param array $duplicates
     * @param $input InputInterface
     * @param $output OutputInterface
     */
    private function analyzeDuplicates(array $duplicates, $input, $output)
    {
        /* @var $gm GraphManager */
        $gm = $this->app['neo4j.graph_manager'];
        /* @var $linkModel LinkModel */
        $linkModel = $this->app['links.model'];

        $errors = array();
        foreach ($duplicates as $duplicate) {
            $mainURL = $duplicate['main']['url'];
            $mainId = (integer)$duplicate['main']['id'];
            $duplicateURL = $duplicate['duplicate']['url'];
            $duplicateId = (integer)$duplicate['duplicate']['id'];

            $output->writeln('Link with id ' . $duplicateId . ' and url ' . $duplicateURL .
                ' is a duplicate of link with id ' . $mainId . ' and url ' . $mainURL);

            if ($input->getOption('fuse')) {
                $output->writeln('Fusing duplicate into main node');

                try {
                    $fusion = $gm->fuseNodes($duplicateId, $mainId);

                    /* @var ResultSet $deletionRS */
                    $deletionRS = $fusion['deleted'];
                    if ($deletionRS->count() > 0) {
                        $popularityManager = $this->app['popularity.manager'];
                        $popularityManager->deleteOneByLink($mainId);
                        $popularityManager->updatePopularity($mainId);
                        $output->writeln('Duplicate and main node successfully fused');
                    } else {
                        $output->writeln('Nodes were not fused');
                    }
                } catch (Neo4jException $e) {
                    $errors[] = array(
                        'duplicateId' => $duplicateId,
                        'mainId' => $mainId,
                        'reason' => $e->getMessage(),
                        'query' => $e->getQuery());
                } catch (\Exception $e) {
                    $errors[] = array(
                        'duplicateId' => $duplicateId,
                        'mainId' => $mainId,
                        'reason' => $e->getMessage());
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

    /**
     * @param OutputInterface $output
     * @param LinkModel $linkModel
     */
    private function updateURLs($output, $linkModel)
    {

        $links = $linkModel->findLinks(array(),0,100);

        /** @var $linkProcessor LinkProcessor */
        $linkProcessor = $this->app['api_consumer.link_processor'];

        foreach ($links as $link) {
            $cleanUrl = $linkProcessor->cleanURL($link['url']);

            if ($cleanUrl !== $link['url']) {
                $output->writeln('Changing ' . $link['url'] . ' to ' . $cleanUrl);
                $link['tempId'] = $link['url'];
                $link['url'] = $cleanUrl;
                $processed = isset($link['processed']) ? $link['processed'] : 0;
                $linkModel->updateLink($link, $processed);
            }
        }

    }
}