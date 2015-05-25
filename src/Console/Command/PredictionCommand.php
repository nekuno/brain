<?php

namespace Console\Command;

use Everyman\Neo4j\Query\ResultSet;
use Model\Entity\EmailNotification;
use Model\LinkModel;
use Model\User\Affinity\AffinityModel;
use Model\UserModel;
use Service\EmailNotifications;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PredictionCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('prediction:calculate')
            ->setDescription('Calculate the predicted high affinity links for a user.')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'The id of the user')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Max links to calculate per user')
            ->addOption('recalculate', null, InputOption::VALUE_OPTIONAL, 'Include already calculated affinities')
            ->addOption('notify', null, InputOption::VALUE_OPTIONAL, 'Email users who get 90%+ affinity contents');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $userModel UserModel */
        $userModel = $this->app['users.model'];
        /* @var $linkModel LinkModel */
        $linkModel = $this->app['links.model'];
        /* @var $affinityModel AffinityModel */
        $affinityModel = $this->app['users.affinity.model'];

        $user = $input->getOption('user');
        $limit = $input->getOption('limit');
        $recalculate = $input->getOption('recalculate');
        $notify = $input->getOption('notify');

        try {

            $users = null === $user ? $userModel->getAll() : array($userModel->getById($user));

            $limit = $limit ?: 10;

            $recalculate = $recalculate ? true : false;

            $notify = $notify ? true : false;

            foreach ($users as $user) {

                $userId = $user['qnoow_id'];
                /* @var $links ResultSet */
                $links = $linkModel->getPredictedContentForAUser($userId, $limit, $recalculate);

                $linkNotifications = array();
                foreach ($links as $link) {

                    $linkId = $link['id'];
                    $affinity = $affinityModel->getAffinity($userId, $linkId);

                    if (OutputInterface::VERBOSITY_NORMAL <= $output->getVerbosity()) {
                        $output->writeln(sprintf('User: %d --> Link: %d (Affinity: %f)', $userId, $linkId, $affinity['affinity']));
                    }

                    if ($notify && ($affinity['affinity'] > 0.9)) {
                        $output->writeln('Found exceptional link: ' . $linkId);
                        $wasNotified = $linkModel->setLinkNotified($userId, $linkId);
                        if (!$wasNotified) {
                            $linkNotifications[] = $linkId;
                        }
                    }

                }
                if (!empty($linkNotifications)) {
                    $notification = EmailNotification::create()
                        ->setType(EmailNotification::EXCEPTIONAL_LINKS)
                        ->setSubject($this->app['translator']->trans('notifications.links.exceptional.subject'))
                        ->setRecipient($user['email'])
                        ->setInfo(array(
                            'links'=> implode(', ', $linkNotifications), //TODO: Cambiar a nombres
                            'amount' => count($linkNotifications),
                            'username' => $user['username'],
                        ));
                    $notificationsService = $this->app['emailNotification.service'];
                    /* @var $notificationsService EmailNotifications */
                    $notificationsService->send($notification);
                    $output->writeln('Sent email to user : ' . $userId . ' with notifications for links: ' . $linkIds);
                }
            }

        } catch (\Exception $e) {

            $output->writeln('Error trying to recalculate predicted links with message: ' . $e->getMessage());

            return;
        }

        $output->writeln('Done.');

    }
}
