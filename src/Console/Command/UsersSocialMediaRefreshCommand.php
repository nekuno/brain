<?php
/**
 * @author Roberto Martinez <@yawmoght>
 */
namespace Console\Command;

use Console\BaseCommand;
use Http\OAuth\ResourceOwner\AbstractResourceOwner;
use Model\Exception\ValidationException;
use Model\User;
use Model\User\TokensModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Manager\UserManager;

class UsersSocialMediaRefreshCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('users:social-media:refresh')
            ->setDescription('Refresh access tokens and refresh tokens for users whether or not there are refresh tokens.')
            ->addArgument('resource', InputArgument::REQUIRED, 'The social media to be refreshed')
            ->addOption('user', 'user', InputOption::VALUE_OPTIONAL, 'If there is only one target user, id of that user');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->setFormat($output);

        /* @var $usersModel UserManager */
        $usersModel = $this->app['users.manager'];
        if ($input->getOption('user')) {
            $users = array($usersModel->getById($input->getOption('user')));
        } else {
            $users = $usersModel->getAll();
        }

        /* @var $resourceOwner AbstractResourceOwner */
        $resourceOwner = $this->app['api_consumer.resource_owner.' . $input->getArgument('resource')];

        /* @var $tm TokensModel */
        $tm = $this->app['users.tokens.model'];

        foreach ($users as $user) {

            /* @var $user User */
            if ($user->getId()) {

                try {
                    $tokens = $tm->getByUserOrResource($user->getId(), $input->getArgument('resource'));
                    if (!$tokens) {
                        continue;
                    } else {
                        $token = current($tokens);
                    }
                    $resourceOwner->forceRefreshAccessToken($token);
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
