<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 11:58 AM
 */

namespace Console\Command;

use Knp\Command\Command;
use Silex\Application;

abstract class ContainerAwareCommand extends Command
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
