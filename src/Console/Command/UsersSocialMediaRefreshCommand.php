<?php

namespace Console\Command;

use ApiConsumer\Factory\ResourceOwnerFactory;
use ApiConsumer\ResourceOwner\FacebookResourceOwner;
use Console\BaseCommand;
use Model\Exception\ValidationException;
use Model\User\User;
use Model\Token\TokenManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Model\User\UserManager;

class UsersSocialMediaRefreshCommand extends BaseCommand
{
    protected static $defaultName = 'users:social-media:refresh';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var ResourceOwnerFactory
     */
    protected $resourceOwnerFactory;

    /**
     * @var TokenManager
     */
    protected $tokensManager;

    public function __construct(LoggerInterface $logger, UserManager $userManager, ResourceOwnerFactory $resourceOwnerFactory, TokenManager $tokensManager)
    {
        parent::__construct($logger);
        $this->userManager = $userManager;
        $this->resourceOwnerFactory = $resourceOwnerFactory;
        $this->tokensManager = $tokensManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Refresh access tokens and refresh tokens for users whether or not there are refresh tokens.')
            ->addArgument('resource', InputArgument::REQUIRED, 'The social media to be refreshed')
            ->addOption('user', 'user', InputOption::VALUE_OPTIONAL, 'If there is only one target user, id of that user');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->setFormat($output);

        if ($input->getOption('user')) {
            $users = array($this->userManager->getById($input->getOption('user')));
        } else {
            $users = $this->userManager->getAll();
        }

        $resource = $input->getArgument('resource');
        $resourceOwner = $this->resourceOwnerFactory->build($resource);

        foreach ($users as $user) {

            /* @var $user User */
            if ($user->getId()) {

                try {
                    $token = $this->tokensManager->getByIdAndResourceOwner($user->getId(), $input->getArgument('resource'));

                    if ($resourceOwner instanceof FacebookResourceOwner){
                        $resourceOwner->forceRefreshAccessToken($token);
                    } else {
                        $resourceOwner->refreshAccessToken($token);
                    }

                    $this->displayMessage('Refreshed ' . $input->getArgument('resource') . ' token for user ' . $user->getId());

                } catch (\Exception $e) {

                    $style = $this->errorStyle;
                    $this->output->getFormatter()->setStyle('error', $style);
                    if ($e instanceof ValidationException) {
                        $this->output->writeln('<error>' . print_r($e->getErrors(), true) . '</error>');
                    }
                    $this->displayError($e->getMessage());
                }
            }
        }

        $output->writeln('Done.');
    }
}
