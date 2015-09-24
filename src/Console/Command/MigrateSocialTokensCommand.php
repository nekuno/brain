<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Exception\ValidationException;
use Model\User\PrivacyModel;
use Model\User\TokensModel;
use Silex\Application;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MigrateSocialTokensCommand extends ApplicationAwareCommand
{
    protected function configure()
    {
        $this->setName('migrate:social-tokens')
            ->setDescription('Migrate tokens from social to brain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $sm Connection */
        $sm = $this->app['dbs']['mysql_social'];

        $qb = $sm->createQueryBuilder()
            ->select('*')
            ->from('user_access_tokens')
            ->orderBy('user_id')
            ->addOrderBy('resourceOwner');

        $tokens = $qb->execute()->fetchAll();

        $output->writeln(count($tokens) . ' tokens found');

        foreach ($tokens as $token) {
            $this->migrateToken($token, $output);
        }

        $output->writeln(count($tokens) . ' tokens processed');

        $output->writeln('Done');
    }

    protected function migrateToken(array $token, OutputInterface $output)
    {

        /* @var $tm TokensModel */
        $tm = $this->app['users.tokens.model'];

        try {

            $tm->getById($token['user_id'], $token['resourceOwner']);
            $output->writeln('Token ' . $token['user_id'] . ' ' . $token['resourceOwner'] . ' already migrated');

        } catch (NotFoundHttpException $e) {

            $data = $token;
            unset($data['user_id']);
            unset($data['resourceOwner']);
            $data['createdTime'] = (integer)$data['createdTime'];
            $data['expireTime'] = (integer)$data['expireTime'];

            try {
                $tm->create($token['user_id'], $token['resourceOwner'], $data);
                $output->writeln('Token ' . $token['user_id'] . ' ' . $token['resourceOwner'] . ' migrated');
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                if ($e instanceof ValidationException) {
                    $output->writeln(print_r($e->getErrors(), true));
                }
                $output->writeln('Token ' . $token['user_id'] . ' ' . $token['resourceOwner'] . ' not migrated');
                $output->writeln(print_r($data, true));
            }

        }

    }
}