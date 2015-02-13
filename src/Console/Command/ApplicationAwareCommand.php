<?php

namespace Console\Command;

use Knp\Command\Command;
use Silex\Application;

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

    public function formatBytes($size, $precision = 2)
    {

        $base = log($size, 1024);
        $suffixes = array('', ' kB', ' MB', ' GB', ' TB');

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }
}
