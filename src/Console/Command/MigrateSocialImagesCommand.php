<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Silex\Application;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateSocialImagesCommand extends ApplicationAwareCommand
{
    protected function configure()
    {
        $this->setName('migrate:social-images')
            ->setDescription('Migrate images from social to brain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $sm Connection */
        $sm = $this->app['dbs']['mysql_social'];

        $qb = $sm->createQueryBuilder()
            ->select('*')
            ->from('gallery_image')
            ->orderBy('user_id');

        $images = $qb->execute()->fetchAll();

        $output->writeln(count($images) . ' images found');

        foreach ($images as $image) {
            $this->migrateImage($image, $output);
        }

        $output->writeln(count($images) . ' images processed');

        $output->writeln('Done');
    }

    protected function migrateImage(array $image, OutputInterface $output)
    {

        $id = $image['user_id'];
        $createdAt = $image['createdAt'];
        $path = $image['image_path'];

        $qm = $this->app['neo4j.graph_manager'];
        $qb = $qm->createQueryBuilder();
        $qb->match('(u:User)<-[:PHOTO_OF]-(i:Photo)')
            ->where('u.qnoow_id = { id }', 'i.path = { path }')
            ->setParameter('id', (integer)$id)
            ->setParameter('path', $path)
            ->returns('u', 'i');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) > 0) {

            $output->writeln(sprintf('Photo with path "%s" already migrated for user "%s"', $path, $id));

        } else {

            $output->write(sprintf('Migrating photo with path "%s" for user "%s"... ', $path, $id));

            $qb = $qm->createQueryBuilder();
            $qb->match('(u:User {qnoow_id: { id }})')
                ->with('u')
                ->create('(u)<-[:PHOTO_OF]-(i:Photo)')
                ->set('i.createdAt = { createdAt }', 'i.path = { path }')
                ->setParameters(
                    array(
                        'id' => (int)$id,
                        'createdAt' => $createdAt,
                        'path' => $path,
                    )
                )
                ->returns('u', 'i');

            $result = $qb->getQuery()->getResultSet();

            if (count($result) < 1) {
                throw new \Exception('Could not create Photo');
            }

            $output->writeln('done!');
        }
    }

}