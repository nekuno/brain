<?php

namespace Console;

use Silex\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

abstract class BaseCommand extends ApplicationAwareCommand
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

    protected function setFormat($output)
    {
        $this->output = $output;
        $this->successStyle = new OutputFormatterStyle('green', 'black', array('bold', 'blink'));
        $this->messageStyle = new OutputFormatterStyle('yellow', 'black', array('bold', 'blink'));
        $this->errorStyle = new OutputFormatterStyle('red', 'black', array('bold', 'blink'));
    }

    protected function displayError($message)
    {
        $style = $this->errorStyle;
        $this->output->getFormatter()->setStyle('error', $style);
        $this->output->writeln('<error>' . $message . '</error>');
        $this->output->writeln('<error>FAIL</error>');
    }

    protected function displayTitle($title)
    {
        $style = $this->successStyle;
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>' . $title . '</success>');
    }

    protected function displayMessage($message)
    {
        $style = $this->messageStyle;
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>' . $message . '</success>');
    }

    protected function displaySuccess()
    {
        $style = $this->successStyle;
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>SUCCESS</success>');
    }
}
