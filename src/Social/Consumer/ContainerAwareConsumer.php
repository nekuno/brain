<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 1:26 PM
 */

namespace Social\Consumer;

use Silex\Application;

abstract class ContainerAwareConsumer {

    /**
     * @var \Silex\Application
     */
    protected $app;

    function __construct(Application $container)
    {
        $this->app = $container;
    }

} 