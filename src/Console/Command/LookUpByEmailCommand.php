<?php
namespace Console\Command;

use Console\BaseCommand;
use Manager\UserManager;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Model\User\LookUpModel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use EventListener\LookUpSocialNetworkSubscriber;

class LookUpByEmailCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('look-up:email')
            ->setDescription('Look up user information using fullContact and peopleGraph')
            ->addOption('id', 'id', InputOption::VALUE_OPTIONAL, 'User id for creating SocialNetwork relationships')
            ->addOption('email', 'email', InputOption::VALUE_REQUIRED, 'Email to lookup');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->setFormat($output);
        $id = $input->getOption('id');
        $email = $input->getOption('email');

        /* @var $lookUpModel LookUpModel */
        $lookUpModel = $this->app['users.lookup.model'];

        if ($id) {

            /* @var $userManager UserManager */
            $userManager = $this->app['users.manager'];

            try {
                $user = $userManager->getById($id, true);
            } catch (\Exception $e) {
                $this->displayError($e->getMessage());
                exit;
            }
            $this->displayTitle('Looking up user ' . $id);
            $lookUpData = $lookUpModel->set($id, array('email' => $user->getEmail()), $output);
            $this->displaySocialData($lookUpData);

        } else {

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->displayError('Invalid email format');
                exit;
            }
            $this->displayTitle('Looking up for email ' . $email);
            $lookUpData = $lookUpModel->completeUserData(array('email' => $email), $output);
            $this->displayFullData($lookUpData);
        }

        $output->writeln('Done.');
    }

    private function displayFullData($data)
    {
        if (isset($data['socialProfiles']) && is_array($data['socialProfiles'])) {
            foreach ($data['socialProfiles'] as $socialNetwork => $url) {

                $this->displayMessage('Social Network: ' . $socialNetwork);
                $this->displayMessage('Url: ' . $url);
            }
            $this->displaySuccess();
        }
        if (isset($data['name'])) {
            $this->displayMessage('Name: ' . $data['name']);
            $this->displaySuccess();
        }
        if (isset($data['email'])) {
            $this->displayMessage('Email: ' . $data['email']);
            $this->displaySuccess();
        }
        if (isset($data['gender'])) {
            $this->displayMessage('Gender: ' . $data['gender']);
            $this->displaySuccess();
        }
        if (isset($data['location'])) {
            $this->displayMessage('Location: ' . $data['location']);
            $this->displaySuccess();
        }
    }

    private function displaySocialData($data)
    {
        foreach ($data as $socialNetwork => $url) {
            $this->displayMessage('Social Network: ' . $socialNetwork);
            $this->displayMessage('Url: ' . $url);
        }
        $this->displaySuccess();
    }
}
