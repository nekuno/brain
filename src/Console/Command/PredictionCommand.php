<?php

namespace Console\Command;

use Model\UserModel;
use Model\User\Affinity\AffinityModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PredictionCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('prediction:calculate')
            ->setDescription('Calculate the predicted high affinity links for a user.')
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                'id of the user?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $model UserModel */
        $usersModel = $this->app['users.model'];
        /* @var $model AffinityModel */
        $affinityModel = $this->app['users.affinity.model'];

        $user = $input->getArgument('user');

        try {

            $links = $usersModel->getPredictedContent($user);

            foreach($links as $link) {
                $affinity = $affinityModel->getAffinity($user, $link['id']);
                $output->writeln($link['id'] . ' -> ' . $affinity['affinity']);
            }

        } catch (\Exception $e) {
            $output->writeln(
                'Error trying to recalculate predicted links with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Done.');

    }
}
