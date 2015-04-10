<?php

namespace Console\Command;

use Knp\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ApplicationAwareCommand extends Command
{

    /**
     * @var \Silex\Application
     */
    protected $app;

    public function __construct(Application $app, $name = null)
    {

        parent::__construct($name);
        $this->app = $app;
    }

    public static function formatBytes($size, $precision = 2)
    {

        $base = log($size, 1024);
        $suffixes = array('', ' kB', ' MB', ' GB', ' TB');

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[(integer)floor($base)];
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $statusCode = parent::run($input, $output);

        $this->memory($output);

        return $statusCode;
    }

    protected function memory(OutputInterface $output)
    {
        $output->writeln(sprintf('Current memory usage: %s', ApplicationAwareCommand::formatBytes(memory_get_usage(true))));
        $output->writeln(sprintf('Peak memory usage: %s', ApplicationAwareCommand::formatBytes(memory_get_peak_usage(true))));
    }

}
