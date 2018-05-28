<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User\User;
use Model\Affinity\AffinityManager;
use Model\User\UserManager;
use Psr\Log\LoggerInterface;
use Service\LinkService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersCalculateAffinityCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'users:calculate:affinity';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var LinkService
     */
    protected $linkService;

    /**
     * @var AffinityManager
     */
    protected $affinityManager;

    public function __construct(LoggerInterface $logger, UserManager $userManager, LinkService $linkService, AffinityManager $affinityManager)
    {
        parent::__construct($logger);
        $this->userManager = $userManager;
        $this->linkService = $linkService;
        $this->affinityManager = $affinityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Calculate the affinity between a user an a link.')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'id of the user?')
            ->addOption('link', null, InputOption::VALUE_OPTIONAL, 'id of the link?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $input->getOption('user');
        $linkId = $input->getOption('link');

        $users = null === $user ? $this->userManager->getAll() : array($this->userManager->getById($user, true));

        try {

            foreach ($users as $user) {

                /* @var $user User */
                $userId = $user->getId();

                $output->writeln(sprintf('Calculating affinity for user %d', $userId));

                if (null === $linkId) {

                    $affineLinks = $this->linkService->findAffineLinks($userId);

                    foreach ($affineLinks as $link) {

                        $linkId = $link->getId();
                        $linkUrl = $link->getUrl();

                        $output->write('Link: ' . $linkId . ' (' . $linkUrl . ') - ');

                        $this->calculateAffinity($userId, $linkId, $output);
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
        $affinity = $this->affinityManager->getAffinity($userId, $linkId);

        $output->writeln('Affinity: ' . $affinity->getAffinity() . ' - Last Updated: ' . $affinity->getUpdated());
    }
}
