<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Console\Command;

use Model\Exception\ValidationException;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Model\User\LookUpModel;
use Model\UserModel;

class LookUpAllUsersCommand extends ApplicationAwareCommand
{

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var OutputFormatterStyle
     */
    protected $successStyle;

    /**
     * @var OutputFormatterStyle
     */
    protected $messageStyle;

    /**
     * @var OutputFormatterStyle
     */
    protected $errorStyle;

    protected function configure()
    {
        $this->setName('look-up-all-users')
            ->setDescription('Look up all users information using fullContact and peopleGraph and set relationships with SocialNetwork`s. It is recommended to execute this command again after 5 minutes.')
            ->addOption('start', 'start', InputOption::VALUE_REQUIRED, 'user id to start with (as an offset)');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->successStyle = new OutputFormatterStyle('green', 'black', array('bold', 'blink'));
        $this->messageStyle = new OutputFormatterStyle('yellow', 'black', array('bold', 'blink'));
        $this->errorStyle = new OutputFormatterStyle('red', 'black', array('bold', 'blink'));

        /** @var $usersModel UserModel */
        $usersModel = $this->app['users.model'];
        $users = $usersModel->getAll();

        /** @var $lookUpModel LookUpModel */
        $lookUpModel = $this->app['users.lookup.model'];

        foreach($users as $user) {
            if(isset($user['qnoow_id']) && isset($user['email']) && $user['qnoow_id'] > $input->getOption('start')) {
                try {
                    $this->displayTitle('Looking up user ' . $user['qnoow_id']);
                    $lookUpData = $lookUpModel->setByEmail($user['qnoow_id'], $user['email']);
                    $this->displayData($lookUpData);
                    $this->displayMessage('waiting...');
                    sleep(1);
                } catch(ValidationException $e) {
                    /** @var $error \Exception */
                    foreach($e->getErrors() as $error) {
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
        foreach($data as $socialNetwork => $url) {
            $this->displayMessage('Social Network: ' . $socialNetwork);
            $this->displayMessage('Url: ' . $url);
        }
        $this->displaySuccess();
    }

    private function displayError($message)
    {
        $style = $this->errorStyle;
        $this->output->getFormatter()->setStyle('error', $style);
        $this->output->writeln('<error>' . $message . '</error>');
        $this->output->writeln('<error>FAIL</error>');
    }

    private function displayTitle($title)
    {
        $style = $this->successStyle;
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>' . $title . '</success>');
    }

    private function displayMessage($message)
    {
        $style = $this->messageStyle;
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>' . $message . '</success>');
    }

    private function displaySuccess()
    {
        $style = $this->successStyle;
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>SUCCESS</success>');
    }
}
