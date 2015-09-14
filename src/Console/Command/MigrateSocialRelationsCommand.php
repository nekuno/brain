<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User\RelationsModel;
use Silex\Application;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateSocialRelationsCommand extends ApplicationAwareCommand
{
    protected function configure()
    {
        $this->setName('migrate:social-relations')
            ->setDescription('Migrate relations from social to brain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $sm Connection */
        $sm = $this->app['dbs']['mysql_social'];

        $qb = $sm->createQueryBuilder()->select('u.*')->from('user_block', 'u');
        $blocks = $qb->execute()->fetchAll();
        $output->writeln(count($blocks) . ' blocks found');
        foreach ($blocks as $block) {
            $this->migrateRelation($block['user_from'], $block['user_to'], RelationsModel::BLOCKS, array('timestamp' => $block['createdAt']), $output);
        }

        $qb = $sm->createQueryBuilder()->select('u.*')->from('user_favorite', 'u');
        $favorites = $qb->execute()->fetchAll();
        $output->writeln(count($favorites) . ' favorites found');
        foreach ($favorites as $favorite) {
            $this->migrateRelation($favorite['user_from'], $favorite['user_to'], RelationsModel::FAVORITES, array('timestamp' => $favorite['createdAt']), $output);
        }

        $qb = $sm->createQueryBuilder()->select('u.*')->from('user_like', 'u');
        $likes = $qb->execute()->fetchAll();
        $output->writeln(count($likes) . ' likes found');
        foreach ($likes as $like) {
            $this->migrateRelation($like['user_from'], $like['user_to'], RelationsModel::LIKES, array('timestamp' => $like['createdAt']), $output);
        }

        $qb = $sm->createQueryBuilder()->select('u.*')->from('user_report', 'u');
        $reports = $qb->execute()->fetchAll();
        $output->writeln(count($reports) . ' reports found');
        foreach ($reports as $report) {
            $this->migrateRelation($report['user_from'], $report['user_to'], RelationsModel::REPORTS, array('timestamp' => $report['createdAt'], 'description' => $report['description']), $output);
        }

        $output->writeln('Done');
    }

    protected function migrateRelation($from, $to, $relation, array $data, OutputInterface $output)
    {

        /* @var $rm RelationsModel */
        $rm = $this->app['users.relations.model'];

        try {
            $rm->create($from, $to, $relation, $data);
            $output->writeln(sprintf('Relation %s between %s and %s migrated', $relation, $from, $to));
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            $output->writeln(sprintf('Relation %s between %s and %s not migrated', $relation, $from, $to));
        }
    }
}