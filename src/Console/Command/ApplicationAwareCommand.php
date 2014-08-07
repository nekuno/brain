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
}
