<?php


namespace Console\Command;

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
            ->setDescription("Return links with identical URLs")
            ->addOption('fuse', null, InputOption::VALUE_NONE, 'Automatically fuse found duplicates')
            ->addOption('pseudoduplicates', null, InputOption::VALUE_NONE, 'Find nearly identical URLs instead of totally identical URLs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $linkModel LinkModel */
        $linkModel = $this->app['links.model'];

        if ($input->getOption('pseudoduplicates')) {
            $duplicates = $linkModel->findPseudoduplicates();
        } else {
            $duplicates = $linkModel->findDuplicates();
        }

        $numDuplicates = count($duplicates);

        if ($numDuplicates > 0) {
            $output->writeln(sprintf('%d duplicated links found.', $numDuplicates));

            /* @var $gm GraphManager */
            $gm = $this->app['neo4j.graph_manager'];

            foreach ($duplicates as $duplicate) {
                $mainurl = $duplicate['main']['url'];
                $mainid = (integer)$duplicate['main']['id'];
                $duplicateurl = $duplicate['duplicate']['url'];
                $duplicateid = (integer)$duplicate['duplicate']['id'];

                $output->writeln('Link with id ' . $duplicateid . ' and url ' . $duplicateurl .
                    ' is a duplicate of link with id ' . $mainid . ' and url ' . $mainurl);

                if ($input->getOption('fuse')) {
                    $output->writeln('Fusing duplicate into main node');

                    $fusion = $gm->fuseNodes($duplicateid, $mainid);
                    /** @var ResultSet $deletionrs */
                    $deletionrs = $fusion['deleted'];
                    if ($deletionrs->count() > 0) {
                        $output->writeln('Duplicate and main node successfully fused');
                    } else {
                        $output->writeln('Nodes were not fused');
                    }
                    $output->writeln('Cleaning inconsistencies');
                    $cleaned = $linkModel->cleanInconsistencies($mainid);
                    if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                        $output->writeln('Like-dislike conflicts solved: ' . $cleaned['dislikes']);
                        $output->writeln('Like-affinity conflicts solved: ' . $cleaned['affinities']);
                    }
                }
            }

        } else {
            $output->writeln('No duplicated links found.');
        }

        $output->writeln('Done.');
    }
}