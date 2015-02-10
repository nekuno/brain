<?php

namespace Console\Command;

use Model\User\Affinity\AffinityModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AffinityCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('affinity:calculate')
            ->setDescription('Calculate the affinity between a user an a link.')
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                'id of the user?'
            )
            ->addArgument(
                'link',
                InputArgument::REQUIRED,
                'id of the link?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $model AffinityModel */
        $model = $this->app['users.affinity.model'];

        $user = $input->getArgument('user');
        $link = $input->getArgument('link');

        try {

            $affinity = $model->getAffinity($user, $link);

            $output->writeln('Affinity: ' . $affinity['affinity']);
            $output->writeln('Last Updated: ' . $affinity['updated']);

        } catch (\Exception $e) {
            $output->writeln(
                'Error trying to recalculate affinity with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Done.');

    }
}
