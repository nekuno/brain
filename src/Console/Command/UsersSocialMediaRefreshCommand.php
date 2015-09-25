<?php
/**
 * @author Roberto Martinez <@yawmoght>
 */
namespace Console\Command;

use Console\BaseCommand;
use Http\OAuth\ResourceOwner\AbstractResourceOwner;
use Model\Exception\ValidationException;
use Model\User\TokensModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Model\UserModel;

class UsersSocialMediaRefreshCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('users:social-media:refresh')
            ->setDescription('Refresh access tokens and refresh tokens for users whether or not there are refresh tokens.')
            ->addArgument('service', InputArgument::REQUIRED, 'The social media to be refreshed')
            ->addOption('userId', 'userId', InputOption::VALUE_OPTIONAL, 'If there is only one target user, id of that user');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setFormat($output);

        /* @var $usersModel UserModel */
        $usersModel = $this->app['users.model'];
        if ($input->getOption('userId')) {
            $users = array($usersModel->getById($input->getOption('userId')));
        } else {
            $users = $usersModel->getAll();
        }

        /* @var $resourceOwner AbstractResourceOwner */
        $resourceOwner = $this->app['api_consumer.resource_owner.' . $input->getArgument('service')];

        /* @var $tm TokensModel */
        $tm = $this->app['users.tokens.model'];

        foreach ($users as $user) {

            if (isset($user['qnoow_id'])) {

                try {
                    $tokens = $tm->getByUserOrResource($user['qnoow_id'], $input->getArgument('service'));
                    if (!$tokens) {
                        continue;
                    } else {
                        $token = current($tokens);
                    }
                    $resourceOwner->forceRefreshAccessToken($token);
                    $this->displayMessage('Refreshed ' . $input->getArgument('service') . ' token for user ' . $user['qnoow_id']);

                } catch (ValidationException $e) {
                    $this->displayError($e->getMessage());
                }
            }
        }

        $output->writeln('Done.');
    }
}
