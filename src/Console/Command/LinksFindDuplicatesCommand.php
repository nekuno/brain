<?php


namespace Console\Command;

use Console\ApplicationAwareCommand;
use Everyman\Neo4j\Query\ResultSet;
use Model\LinkModel;
use Model\Neo4j\GraphManager;
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
            ->addOption('step', null, InputOption::VALUE_NONE, 'Obtain, output and/or fuse duplicate by duplicate')
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Links to skip if using step option', 0)
            ->addOption('pseudoduplicates', null, InputOption::VALUE_NONE, 'Find nearly identical URLs instead of totally identical URLs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Starting database search.');

        /* @var $linkModel LinkModel */
        $linkModel = $this->app['links.model'];

        if ($input->getOption('pseudoduplicates') && $input->getOption('step')) {
            $moreLinksExist = true;
            $offset = $input->getOption('offset');
            while ($moreLinksExist) {
                $nextDuplicates = $linkModel->findPseudoduplicatesFromOffset((integer)$offset);
                if ($nextDuplicates === null) {
                    $output->writeln('No more links to analyze.');
                    return;
                }
                if (empty($nextDuplicates)) {
                    $output->writeln(sprintf('No pseudoduplicates found for link with offset %d', $offset));
                } else {
                    $output->writeln(sprintf('%d duplicated links found for links with id offset.', count($nextDuplicates), $offset));
                    $this->analyzeDuplicates($nextDuplicates, $input, $output);
                }
                $offset++;
            }
            return;
        }

        if ($input->getOption('pseudoduplicates')) {
            $duplicates = $linkModel->findPseudoduplicates();
        } else {
            $duplicates = $linkModel->findDuplicates();
        }

        $numDuplicates = count($duplicates);

        if ($numDuplicates > 0) {
            $output->writeln(sprintf('%d duplicated links found.', $numDuplicates));

            $this->analyzeDuplicates($duplicates, $input, $output);

        } else {
            $output->writeln('No duplicated links found.');
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

        foreach ($duplicates as $duplicate) {
            $mainURL = $duplicate['main']['url'];
            $mainId = (integer)$duplicate['main']['id'];
            $duplicateURL = $duplicate['duplicate']['url'];
            $duplicateId = (integer)$duplicate['duplicate']['id'];

            $output->writeln('Link with id ' . $duplicateId . ' and url ' . $duplicateURL .
                ' is a duplicate of link with id ' . $mainId . ' and url ' . $mainURL);

            if ($input->getOption('fuse')) {
                $output->writeln('Fusing duplicate into main node');

                $fusion = $gm->fuseNodes($duplicateId, $mainId);
                /* @var ResultSet $deletionRS */
                $deletionRS = $fusion['deleted'];
                if ($deletionRS->count() > 0) {
                    $output->writeln('Duplicate and main node successfully fused');
                } else {
                    $output->writeln('Nodes were not fused');
                }
                $output->writeln('Cleaning inconsistencies');
                $cleaned = $linkModel->cleanInconsistencies($mainId);
                if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                    $output->writeln('Like-dislike conflicts solved: ' . $cleaned['dislikes']);
                    $output->writeln('Like-affinity conflicts solved: ' . $cleaned['affinities']);
                }
            }
        }
    }
}