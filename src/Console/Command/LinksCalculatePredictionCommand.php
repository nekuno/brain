<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\LinkModel;
use Model\User;
use Model\User\Affinity\AffinityModel;
use Manager\UserManager;
use Service\AffinityRecalculations;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LinksCalculatePredictionCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('links:calculate:prediction')
            ->setDescription('Calculate the predicted high affinity links for a user.')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'The id of the user')
            ->addOption('limitContent', null, InputOption::VALUE_OPTIONAL, 'Max links to calculate per user')
            ->addOption('limitUsers', null, InputOption::VALUE_OPTIONAL, 'Max similar users to get links from')
            ->addOption('recalculate', null, InputOption::VALUE_NONE, 'Include already calculated affinities (Updates those links)')
            ->addOption('notify', null, InputOption::VALUE_OPTIONAL, 'Email users who get links with more affinity than this value');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $userManager UserManager */
        $userManager = $this->app['users.manager'];
        /* @var $linkModel LinkModel */
        $linkModel = $this->app['links.model'];
        /* @var $affinityModel AffinityModel */
        $affinityModel = $this->app['users.affinity.model'];

        $user = $input->getOption('user');
        $limitContent = $input->getOption('limitContent') ?: 40;
        $limitUsers = $input->getOption('limitUsers') ?: 10;
        $recalculate = $input->getOption('recalculate');
        $notify = $input->getOption('notify');

        try {

            $users = null === $user ? $userManager->getAll() : array($userManager->getById($user, true));

            $recalculate = $recalculate ? true : false;

            $notify = $notify ?: 99999;

            if (!$recalculate) {
                foreach ($users as $user) {
                    /* @var $user User */
                    $filters = array('affinity' => false);
                    $linkIds = $linkModel->getPredictedContentForAUser($user->getId(), $limitContent, $limitUsers, $filters);
                    foreach ($linkIds as $link) {

                        $linkId = $link['id'];
                        $affinity = $affinityModel->getAffinity($user->getId(), $linkId);
                        if (OutputInterface::VERBOSITY_NORMAL <= $output->getVerbosity()) {
                            $output->writeln(sprintf('User: %d --> Link: %d (Affinity: %f)', $user->getId(), $linkId, $affinity['affinity']));
                        }
                    }
                }
            } else {
                /* @var $affinityRecalculations AffinityRecalculations */
                $affinityRecalculations = $this->app['affinityRecalculations.service'];
                foreach ($users as $user) {
                    /* @var $user User */
                    $count = $affinityModel->countPotentialAffinities($user->getId(), $limitUsers);
                    $estimatedTime = $affinityRecalculations->estimateTime($count);
                    $targetTime = AffinityModel::numberOfSecondsToCalculateAffinity;
                    if ($estimatedTime > $targetTime) {
                        $usedLimitUsers = max(
                            AffinityModel::minimumUsersToPredict,
                            intval($limitUsers * sqrt($targetTime / $estimatedTime))
                        );
                    } else {
                        $usedLimitUsers = $limitUsers;
                    }
                    $output->writeln(sprintf('%s potential affinities for user %s', $count, $user->getId()));
                    $output->writeln($estimatedTime . '  ' . $usedLimitUsers);
                    $result = $affinityRecalculations->recalculateAffinities($user->getId(), $limitContent, $limitUsers, $notify);

                    foreach ($result['affinities'] as $linkId => $affinity) {
                        $output->writeln(sprintf('User: %d --> Link: %d (Affinity: %f)', $user->getId(), $linkId, $affinity));
                    }
                    if (!empty($result['emailInfo'])) {
                        $emailInfo = $result['emailInfo'];
                        $linkIds = array();
                        foreach ($emailInfo['links'] as $link) {
                            $linkIds[] = $link['id'];
                        }
                        $output->writeln(sprintf('Email sent to %s users', $emailInfo['recipients']));
                        $output->writeln(sprintf('Email sent to user: %s with links: %s', $user->getId(), implode(', ', $linkIds)));
                    }
                }
            }

        } catch (\Exception $e) {
            $output->writeln('Error trying to recalculate predicted links with message: ' . $e->getMessage());

            return;
        }

        $this->app['swiftmailer.spooltransport']->getSpool()->flushQueue($this->app['swiftmailer.transport']);
        $output->writeln('Spool sent.');
        $output->writeln('Done.');

    }
}
