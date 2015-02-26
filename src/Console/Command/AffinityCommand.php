<?php

namespace Console\Command;

use Model\User\Affinity\AffinityModel;
use Model\LinkModel;
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
                InputArgument::OPTIONAL,
                'id of the link?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $linkModel LinkModel */
        $linkModel = $this->app['links.model'];

        $userId = $input->getArgument('user');
        $linkId = $input->getArgument('link');

        if (null === $linkId) {
            $affineLinks = $linkModel->findLinksByUser($userId, 'AFFINITY');

            foreach ($affineLinks as $link) {
                $output->write('Link: ' . $link['id'] . ' (' . $link['url'] . ') - ');

                $this->calculateAffinity($userId, $link['id'], $output);
            }
        } else {
            $this->calculateAffinity($userId, $linkId, $output);
        }

        $output->writeln('Done.');

    }

    private function calculateAffinity($userId, $linkId, OutputInterface $output)
    {
        /* @var $affinityModel AffinityModel */
        $affinityModel = $this->app['users.affinity.model'];

        try {
            $affinity = $affinityModel->getAffinity($userId, $linkId);

            $output->writeln('Affinity: ' . $affinity['affinity'] . ' - Last Updated: ' . $affinity['updated']);

        } catch (\Exception $e) {
            $output->writeln(
              'Error trying to recalculate affinity with message: ' . $e->getMessage()
            );

            return;
        }
    }
}
