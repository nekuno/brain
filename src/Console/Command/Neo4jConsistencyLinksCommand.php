<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Link;
use Model\User;
use Model\User\RateModel;
use Manager\UserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jConsistencyLinksCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:consistency:links')
            ->setDescription('Ensures links database consistency')
            ->addOption('likes', null, InputOption::VALUE_NONE, 'Check LIKES relationships', null)
            ->addOption('processed', null, InputOption::VALUE_NONE, 'Disable processed status when recommended', null)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Links to skip from oldest', 0)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Solve problems where possible', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $offset = $input->getOption('offset');

        $likesOption = $input->getOption('likes');
        $processedOption = $input->getOption('processed');

        if ($likesOption) {

            /** @var RateModel $rateModel */
            $rateModel = $this->app['users.rate.model'];

            $output->writeln('Getting likes list.');

            /** @var UserManager $userManager */
            $userManager = $this->app['users.manager'];
            $users = $userManager->getAll(true);

            $likes = array();
            foreach ($users as $user) {
                /* @var $user User */
                $likes = array_merge($likes, $rateModel->getRatesByUser($user->getId(), RateModel::LIKE));
            }

            $output->writeln(sprintf('Got %d likes', count($likes)));

            $this->checkLikes($likes, $force, $output);
        }

        if ($processedOption) {

            $linkModel = $this->app['links.model'];

            $output->writeln('Checking processed status.');

            $maxLimit = 99999999;
            $limit = 1000;
            do {
                $output->writeln('-----------------------------------------------------------------');
                $output->writeln(sprintf('Getting and analyzing %d links from offset %d.', $limit, $offset));

                $links = $linkModel->getLinks(array(), $offset, $limit);

                foreach ($links as $linkArray)
                {
                    $link = Link::buildFromArray($linkArray);
                    if (!$link->isComplete() && $link->getProcessed()) {
                        $linkModel->setProcessed($link->getUrl(), false);
                        $output->writeln(sprintf('Corrected processed status of link %s', $link->getUrl()));
                    }
                }
                $offset += $limit;

            } while ($offset < $maxLimit && !empty($links));
            
        }

        $output->writeln('Finished.');
    }

    /**
     * @param $likes array
     * @param $force boolean
     * @param $output OutputInterface
     */
    private function checkLikes($likes, $force, $output)
    {
        /** @var RateModel $rateModel */
        $rateModel = $this->app['users.rate.model'];

        $emptyLikes = array();
        foreach ($likes as $like) {
            if (count($like['resources']) == 0) {
                if ($force) {
                    try {
                        $rateModel->completeLikeById($like['id']);
                        if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                            $output->writeln(sprintf('SUCCESS: Empty like with id %d has been updated', $like['id']));
                            $emptyLikes [] = $like;
                        }
                    } catch (\Exception $e) {
                        $output->writeln(sprintf('ERROR: Cannot update like with id %d', $like['id']));
                    }
                } else {
                    if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                        $output->writeln(sprintf('Empty like with id %d need to be updated', $like['id']));
                        $emptyLikes [] = $like;
                    }
                }
            }
        }

        if ($force) {
            $output->writeln(sprintf('%d empty links updated', count($emptyLikes)));
        } else {
            $output->writeln(sprintf('%d empty likes need to be updated', count($emptyLikes)));
        }

    }

}