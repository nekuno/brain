<?php


namespace Console\Command;

use Everyman\Neo4j\Relationship;
use Model\LinkModel;
use Model\Neo4j\GraphManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LinksFuseCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('links:fuse')
            ->setDescription("Move relationships from first node to second one and delete the first one.")
            ->addArgument('id 1', InputArgument::REQUIRED, 'The id of the link to be deleted')
            ->addArgument('id 2', InputArgument::REQUIRED, 'The id of the link to receive relationships');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $id1 = (integer)$input->getArgument('id 1');
        $id2 = (integer)$input->getArgument('id 2');

        /* @var $gm GraphManager */
        $gm = $this->app['neo4j.graph_manager'];

        $qb = $gm->createQueryBuilder();
        $qb->match('(l1), (l2)')
            ->where('id(l1)={id1} and id(l2)={id2}')
            ->returns('labels(l1) AS labels1, labels(l2) AS labels2');
        $qb->setParameters(array(
            'id1' => $id1,
            'id2' => $id2
        ));
        $rs = $qb->getQuery()->getResultSet();

        if (count($rs) === 0) {
            $output->writeln('At least one node was non-existent. Check the ids and try again');
        } else {
            $labels1 = $rs->current()['labels1'][0];
            $labels2 = $rs->current()['labels2'][0];
            if ($labels1 !== $labels2) {
                $output->writeln('Nodes have different labels. Check the ids and try again.');
            } else {

                $output->writeln('Fusing nodes.');
                $result = $gm->fuseNodes($id1, $id2);

                $output->writeln($result['deleted'][0]['amount'] . ' relationships were deleted from node 1');

                if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                    $output->writeln('node with id ' . $id1 . ' had labels: ' . $labels1);
                    $output->writeln('node with id ' . $id2 . ' has labels: ' . $labels2);
                    if (isset($result['relationships']['incoming'])) {
                        /** @var Relationship[] $relationship */
                        foreach ($result['relationships']['incoming'] as $relationship) {
                            $output->writeln('Incoming relationship id ' . $relationship['id'] . ' deleted.');
                            foreach ($relationship['r']->getProperties() as $property => $value) {
                                $output->writeln('This relationship had property ' . $property . ' with value ' . $value);
                            }
                        }
                    }
                    if (isset($result['relationships']['outgoing'])) {
                        foreach ($result['relationships']['outgoing'] as $relationship) {
                            $output->writeln('Outgoing relationship id ' . $relationship['id'] . ' deleted.');
                            foreach ($relationship['r']->getProperties() as $property => $value) {
                                $output->writeln('This relationship had property ' . $property . ' with value ' . $value);
                            }
                        }
                    }
                }

                $output->writeln('Cleaning inconsistencies');

                /* @var $lm LinkModel */
                $lm = $this->app['links.model'];
                $cleaned = $lm->cleanInconsistencies($id2);
                if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                    $output->writeln('Like-dislike conflicts solved: ' . $cleaned['dislikes']);
                    $output->writeln('Like-affinity conflicts solved: ' . $cleaned['affinities']);
                }
            }
        }

        $output->writeln('Done.');
    }
}