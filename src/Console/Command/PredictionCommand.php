<?php

namespace Console\Command;

use Everyman\Neo4j\Query\ResultSet;
use Model\LinkModel;
use Model\User\Affinity\AffinityModel;
use Model\UserModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PredictionCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('prediction:calculate')
            ->setDescription('Calculate the predicted high affinity links for a user.')
            ->addArgument('user', InputArgument::OPTIONAL, 'the id of the user')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Max links to calculate per user');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $userModel UserModel */
        $userModel = $this->app['users.model'];
        /* @var $linkModel LinkModel */
        $linkModel = $this->app['links.model'];
        /* @var $affinityModel AffinityModel */
        $affinityModel = $this->app['users.affinity.model'];

        $user = $input->getArgument('user');
        $limit = $input->getOption('limit');

        try {

            if (null === $user) {
                $users = $userModel->getAll();
            } else {
                $users = $userModel->getById($user);
            }

            $limit = $limit ?: 10;

            foreach ($users as $user) {

                $userId = $user['qnoow_id'];
                /* @var $links ResultSet */
                $links = $linkModel->getPredictedContentForAUser($userId, $limit);

                foreach ($links as $link) {

                    $linkId = $link['id'];
                    $affinity = $affinityModel->getAffinity($userId, $linkId);

                    if (OutputInterface::VERBOSITY_NORMAL <= $output->getVerbosity()) {
                        $output->writeln(sprintf('User: %d --> Link: %d (Affinity: %f)', $userId, $linkId, $affinity['affinity']));
                    }
                }
            }

        } catch (\Exception $e) {
            $output->writeln('Error trying to recalculate predicted links with message: ' . $e->getMessage());

            return;
        }

        $output->writeln('Done.');

    }
}
