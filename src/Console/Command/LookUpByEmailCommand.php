<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Console\Command;

use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Model\User\LookUpModel;

class LookUpByEmailCommand extends ApplicationAwareCommand
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
        $this->setName('look-up-by-email')
            ->setDescription('Look up user information using fullContact and peopleGraph')
            ->addOption('email', 'email', InputOption::VALUE_REQUIRED, 'Email to lookup', 'enredos@nekuno.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->successStyle = new OutputFormatterStyle('green', 'black', array('bold', 'blink'));
        $this->messageStyle = new OutputFormatterStyle('yellow', 'black', array('bold', 'blink'));
        $this->errorStyle = new OutputFormatterStyle('red', 'black', array('bold', 'blink'));
        $email = $input->getOption('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->displayError('Invalid email format');
            exit;
        }

        /** @var $lookUpModel LookUpModel */
        $lookUpModel = $this->app['users.lookup.model'];

        $this->displayTitle('Looking up...');

        $lookUpData = $lookUpModel->getByEmail($email);

        $this->displayData($lookUpData);

        $output->writeln('Done.');
    }

    private function displayData($data)
    {
        if(isset($data['socialProfiles']) && is_array($data['socialProfiles'])) {
            foreach($data['socialProfiles'] as $socialNetwork => $url) {

                $this->displayMessage('Social Network: ' . $socialNetwork);
                $this->displayMessage('Url: ' . $url);
            }
            $this->displaySuccess();
        }
        if(isset($data['name'])) {
            $this->displayMessage('Name: ' . $data['name']);
            $this->displaySuccess();
        }
        if(isset($data['email'])) {
            $this->displayMessage('Email: ' . $data['email']);
            $this->displaySuccess();
        }
        if(isset($data['gender'])) {
            $this->displayMessage('Gender: ' . $data['gender']);
            $this->displaySuccess();
        }
        if(isset($data['location'])) {
            $this->displayMessage('Location: ' . $data['location']);
            $this->displaySuccess();
        }
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
