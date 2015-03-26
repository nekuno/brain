<?php


namespace Console\Command;

use Model\Neo4j\GraphManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LinksFindPseudoduplicatesCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('links:find-pseudoduplicates')
          ->setDescription("Return links with very similar URLs")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting the database search.');

        /** @var GraphManager $gm */
        $gm = $this->app['neo4j.graph_manager'];
        $qb = $gm->createQueryBuilder();

        $qb->match('(l1:Link), (l2:Link)')
            ->where('l2.url=l1.url+"/" OR l2.url=l1.url+"?" OR l2.url=l1.url+"&"')
            ->returns('id(l1) AS id1, l1.url AS url1, id(l2) AS id2, l2.url AS url2');
        $duplicates=$qb->getQuery()->getResultSet();

        if (count($duplicates)==0){
            $output->writeln('No pseudoduplicate links found');
        } else {
            foreach ($duplicates AS $duplicate){
                $output->writeln('Link with id '.$duplicate['id2'].' and url '.$duplicate['url2'].
                    ' is a pseudoduplicate of link with id '.$duplicate['id1'].' and url '.$duplicate['url1']);
            }
        }

        $output->writeln('Done.');
    }
}