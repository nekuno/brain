<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User\Affinity\AffinityModel;
use Model\LinkModel;
use Manager\UserManager;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersCalculateAffinityCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('users:calculate:affinity')
            ->setDescription('Calculate the affinity between a user an a link.')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'id of the user?')
            ->addOption('link', null, InputOption::VALUE_OPTIONAL, 'id of the link?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $userManager UserManager */
        $userManager = $this->app['users.model'];
        /* @var $linkModel LinkModel */
        $linkModel = $this->app['links.model'];

        $user = $input->getOption('user');
        $linkId = $input->getOption('link');

        $users = null === $user ? $userManager->getAll() : array($userManager->getById($user, true));

        try {

            foreach ($users as $user) {

                $userId = $user['qnoow_id'];

                $output->writeln(sprintf('Calculating affinity for user %d', $userId));

                if (null === $linkId) {

                    $affineLinks = $linkModel->findLinksByUser($userId, 'AFFINITY');

                    foreach ($affineLinks as $link) {

                        $output->write('Link: ' . $link['id'] . ' (' . $link['url'] . ') - ');

                        $this->calculateAffinity($userId, $link['id'], $output);
                    }

                } else {

                    $this->calculateAffinity($userId, $linkId, $output);

                }
            }

        } catch (\Exception $e) {

            $output->writeln('Error trying to recalculate affinity with message: ' . $e->getMessage());
        }

        $output->writeln('Done.');

    }

    private function calculateAffinity($userId, $linkId, OutputInterface $output)
    {
        /* @var $affinityModel AffinityModel */
        $affinityModel = $this->app['users.affinity.model'];

        $affinity = $affinityModel->getAffinity($userId, $linkId);

        $output->writeln('Affinity: ' . $affinity['affinity'] . ' - Last Updated: ' . $affinity['updated']);

    }
}
