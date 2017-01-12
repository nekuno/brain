<?php
namespace Console\Command;

use Console\BaseCommand;
use Model\Exception\ValidationException;
use Model\User;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Model\User\LookUpModel;
use Manager\UserManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use EventListener\LookUpSocialNetworkSubscriber;

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

        /* @var $usersModel UserManager */
        $usersModel = $this->app['users.manager'];
        $users = $usersModel->getAll();

        /* @var $lookUpModel LookUpModel */
        $lookUpModel = $this->app['users.lookup.model'];

        foreach ($users as $user) {
            /* @var $user User */
            if ($user->getId() && $user->getEmail() && $user->getId() >= $input->getOption('start')) {
                try {
                    $this->displayTitle('Looking up user ' . $user->getId());
                    $lookUpData = $lookUpModel->set($user->getId(), array('email' =>  $user->getEmail()), $output);
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
