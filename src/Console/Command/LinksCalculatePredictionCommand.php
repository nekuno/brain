<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Link\Link;
use Model\Link\LinkManager;
use Model\User\User;
use Model\Affinity\AffinityManager;
use Model\User\UserManager;
use Psr\Log\LoggerInterface;
use Service\AffinityRecalculations;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LinksCalculatePredictionCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'links:calculate:prediction';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var LinkManager
     */
    protected $linkManager;

    /**
     * @var AffinityManager
     */
    protected $affinityManager;

    /**
     * @var AffinityRecalculations
     */
    protected $affinityRecalculations;

    /**
     * @var \Swift_Spool
     */
    protected $mailerSpool;

    /**
     * @var \Swift_Transport
     */
    protected $mailerTransport;

    public function __construct(LoggerInterface $logger, UserManager $userManager, LinkManager $linkManager, AffinityManager $affinityManager, AffinityRecalculations $affinityRecalculations, \Swift_Spool $mailerSpool, \Swift_Transport $mailerTransport)
    {
        parent::__construct($logger);
        $this->userManager = $userManager;
        $this->linkManager = $linkManager;
        $this->affinityManager = $affinityManager;
        $this->affinityRecalculations = $affinityRecalculations;
        $this->mailerSpool = $mailerSpool;
        $this->mailerTransport = $mailerTransport;
    }

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
        $user = $input->getOption('user');
        $limitContent = $input->getOption('limitContent') ?: 40;
        $limitUsers = $input->getOption('limitUsers') ?: 10;
        $recalculate = $input->getOption('recalculate');
        $notify = $input->getOption('notify');

        try {

            $users = null === $user ? $this->userManager->getAll() : array($this->userManager->getById($user, true));

            $recalculate = $recalculate ? true : false;

            $notify = $notify ?: 99999;

            if (!$recalculate) {
                foreach ($users as $user) {
                    /* @var $user User */
                    $filters = array('affinity' => false);
                    $linkIds = $this->linkManager->getPredictedContentForAUser($user->getId(), $limitContent, $limitUsers, $filters);
                    foreach ($linkIds as $link) {

                        $linkId = $link->getId();
                        $affinity = $this->affinityManager->getAffinity($user->getId(), $linkId);
                        if (OutputInterface::VERBOSITY_NORMAL <= $output->getVerbosity()) {
                            $output->writeln(sprintf('User: %d --> Link: %d (Affinity: %f)', $user->getId(), $linkId, $affinity->getAffinity()));
                        }
                    }
                }
            } else {
                foreach ($users as $user) {
                    /* @var $user User */
                    $count = $this->affinityManager->countPotentialAffinities($user->getId(), $limitUsers);
                    $estimatedTime = $this->affinityRecalculations->estimateTime($count);
                    $targetTime = AffinityManager::numberOfSecondsToCalculateAffinity;
                    if ($estimatedTime > $targetTime) {
                        $usedLimitUsers = max(
                            AffinityManager::minimumUsersToPredict,
                            intval($limitUsers * sqrt($targetTime / $estimatedTime))
                        );
                    } else {
                        $usedLimitUsers = $limitUsers;
                    }
                    $output->writeln(sprintf('%s potential affinities for user %s', $count, $user->getId()));
                    $output->writeln($estimatedTime . '  ' . $usedLimitUsers);
                    $result = $this->affinityRecalculations->recalculateAffinities($user->getId(), $limitContent, $limitUsers, $notify);

                    foreach ($result['affinities'] as $linkId => $affinity) {
                        $output->writeln(sprintf('User: %d --> Link: %d (Affinity: %f)', $user->getId(), $linkId, $affinity));
                    }
                    if (!empty($result['emailInfo'])) {
                        $emailInfo = $result['emailInfo'];
                        $linkIds = array();
                        /** @var Link $link */
                        foreach ($emailInfo['links'] as $link) {
                            $linkIds[] = $link->getId();
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

        $this->mailerSpool->flushQueue($this->mailerTransport);
        $output->writeln('Spool sent.');
        $output->writeln('Done.');
    }
}
