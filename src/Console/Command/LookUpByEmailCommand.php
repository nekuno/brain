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
use Service\LookUpByEmail;

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
        $this->errorStyle = new OutputFormatterStyle('red', 'black', array('bold', 'blink'));
        $email = $input->getOption('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->displayError('Invalid email format');
            exit;
        }

        /** @var $lookUpByEmail LookUpByEmail */
        $lookUpByEmail = $this->app['lookUpByEmail.service'];

        try {
            $this->displayMessage('Getting from FullContact');
            $fullContactData = $lookUpByEmail->getFromFullContact($email);
                foreach($fullContactData as $socialNetwork => $url) {
                    $this->displayMessage('Social Network: ' . $socialNetwork);
                    $this->displayMessage('Url: ' . $url);
                }
                $this->displaySuccess();

        } catch (\Exception $e) {
            $this->displayError('<error>Error trying to look up: ' . $e->getMessage() . '</error>');
        }

        try {
            $this->displayMessage('Getting from PeopleGraph');
            $peopleGraphData = $lookUpByEmail->getFromPeopleGraph($email);
            foreach($peopleGraphData as $socialNetwork => $url) {
                $this->displayMessage('Social Network: ' . $socialNetwork);
                $this->displayMessage('Url: ' . $url);
            }
            $this->displaySuccess();

        } catch (\Exception $e) {
            $this->displayError('<error>Error trying to look up: ' . $e->getMessage() . '</error>');
        }

        $output->writeln('Done.');

    }

    private function displayError($message)
    {
        $style = $this->errorStyle;
        $this->output->getFormatter()->setStyle('error', $style);
        $this->output->writeln('<error>' . $message . '</error>');
        $this->output->writeln('<error>FAIL</error>');
    }

    private function displayMessage($message)
    {
        $style = $this->successStyle;
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
