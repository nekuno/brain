<?php


namespace Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LinksRemoveDuplicatesCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('links:remove-duplicates')
          ->setDescription("Remove duplicate links")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gm = $this->app['neo4j.graph_manager'];
        $qb = $gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->with('l.url AS url, COLLECT(ID(l)) AS ids, COUNT(*) AS count')
            ->where('count > 1')
            ->returns('url, ids');

        $query = $qb->getQuery();
        $duplicates =  $query->getResultSet();

        $numDuplicates = count($duplicates);

        if ($numDuplicates > 0) {
            $output->writeln(sprintf('%d duplicated links found.', $numDuplicates));

            $linkModel = $this->app['links.model'];

            foreach ($duplicates as $duplicate) {
                $url = $duplicate->offsetGet('url');
                $ids = $duplicate->offsetGet('ids');

                $output->writeln(sprintf('Removing %d duplicates of %s', (count($ids) - 1), $url));

                for ($i=1;$i<count($ids);$i++) {
                    $linkModel->removeLink($ids[$i]);
                }
            }

        } else {
            $output->writeln('No duplicates links found.');
        }

        $output->writeln('Done.');
    }
}