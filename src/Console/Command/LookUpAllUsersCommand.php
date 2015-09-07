<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Console\Command;

use Console\BaseCommand;
use Model\Exception\ValidationException;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Model\User\LookUpModel;
use Model\UserModel;

class LookUpAllUsersCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('look-up:all')
            ->setDescription('Look up all users information using fullContact and peopleGraph and set relationships with SocialNetwork`s.')
            ->addOption('start', 'start', InputOption::VALUE_REQUIRED, 'user id to start with (as an offset)');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setFormat($output);

        /* @var $usersModel UserModel */
        $usersModel = $this->app['users.model'];
        $users = $usersModel->getAll();

        /* @var $lookUpModel LookUpModel */
        $lookUpModel = $this->app['users.lookup.model'];

        foreach ($users as $user) {
            if (isset($user['qnoow_id']) && isset($user['email']) && $user['qnoow_id'] >= $input->getOption('start')) {
                try {
                    $this->displayTitle('Looking up user ' . $user['qnoow_id']);
                    $lookUpData = $lookUpModel->setByEmail($user['qnoow_id'], $user['email']);
                    $this->displayData($lookUpData);
                    $this->displayMessage('waiting...');
                    sleep(1);
                } catch (ValidationException $e) {
                    /* @var $error \Exception */
                    foreach ($e->getErrors() as $error) {
                        $this->displayError($error);
                    }
                    $this->displayMessage('waiting...');
                    sleep(1);
                }
            }
        }

        $output->writeln('Done.');
    }

    private function displayData($data)
    {
        foreach ($data as $socialNetwork => $url) {
            $this->displayMessage('Social Network: ' . $socialNetwork);
            $this->displayMessage('Url: ' . $url);
        }
        $this->displaySuccess();
    }
}
