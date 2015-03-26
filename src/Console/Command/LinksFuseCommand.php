<?php


namespace Console\Command;

use Model\Neo4j\GraphManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LinksFuseCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('links:fuse')
          ->setDescription("Move relationships from first node to second one and delete the first one.")
          ->addArgument('id 1', InputArgument::REQUIRED, 'The id of the link to be deleted')
          ->addArgument('id 2', InputArgument::REQUIRED, 'The id of the link to receive relationships')
          ->addOption('debug', null, InputOption::VALUE_OPTIONAL, 'Detailed debug mode, disabled by default', 'no');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!($input->getOption('debug')==='yes' || $input->getOption('debug')==='no')){
            $output->writeln('Please enter "yes" or "no" as debug mode, or leave it blank');
        } else {
            /** @var GraphManager $gm */
            $gm = $this->app['neo4j.graph_manager'];

            $qb=$gm->createQueryBuilder();
            $qb->match('(l1), (l2)')
                ->where('id(l1)={id1} and id(l2)={id2}')
                ->returns('labels(l1) AS labels1, labels(l2) AS labels2');
            $qb->setParameters(array(
                'id1'=>(integer)$input->getArgument('id 1'),
                'id2'=>(integer)$input->getArgument('id 2')
            ));
            $rs=$qb->getQuery()->getResultSet();
            if (count($rs)===0){
                $output->writeln('At least one node was non-existent. Check the ids and try again');
            } else if ($rs[0]['labels1'][0]!==$rs[0]['labels2'][0]){
                $output->writeln('Nodes have different labels. Check the ids and try again.');
            } else {

                $output->writeln('Fusing nodes.');
                $result=$gm->fuseNodes( (integer)$input->getArgument('id 1'),
                                        (integer)$input->getArgument('id 2'));

                $output->writeln($result['deleted'][0]['amount'].' relationships were deleted from node 1');

                if ($input->getOption('debug')==='yes'){
                    $output->writeln('node with id'.$input->getArgument('id 1').' had labels: '.$rs[0]['labels1'][0]);
                    $output->writeln('node with id'.$input->getArgument('id 2').' has labels: '.$rs[0]['labels2'][0]);
                    if(isset($result['relationships']['incoming'])){
                        foreach($result['relationships']['incoming'] as $relationship){
                            $output->writeln('Incoming relationship id '.$relationship['id'].' deleted.');
                            foreach($relationship['r']->getProperties() as $property=>$value){
                                $output->writeln('This relationship had property '.$property.' with value '.$value);
                            }
                        }
                    }
                    if(isset($result['relationships']['outgoing'])){
                        foreach($result['relationships']['outgoing'] as $relationship){
                            $output->writeln('Outgoing relationship id '.$relationship['id'].' deleted.');
                            foreach($relationship['r']->getProperties() as $property=>$value){
                                $output->writeln('This relationship had property '.$property.' with value '.$value);
                            }
                        }
                    }
                }

                $output->writeln('Cleaning inconsistencies');

                $qb=$gm->createQueryBuilder();
                $qb->match('(l2:Link)')
                    ->where('id(l2)={id2}')
                    ->match('(l2)<-[r1:LIKES]-(u)')
                    ->optionalMatch('(l2)<-[r2:DISLIKES]-(u)')
                    ->optionalMatch('(l2)<-[r3:AFFINITY]-(u)')
                    ->delete('r2,r3')
                    ->returns('count(r2) AS dislikes, count(r3) AS affinities');
                $qb->setParameter('id2',(integer)$input->getArgument('id 2'));
                $rs=$qb->getQuery()->getResultSet();
                if ($input->getOption('debug')==='yes'){
                    $output->writeln('Like-dislike conflicts solved: '.$rs[0]['dislikes']);
                    $output->writeln('Like-affinity conflicts solved: '.$rs[0]['affinities']);
                }
            }
        }
        $output->writeln('Done.');
    }
}