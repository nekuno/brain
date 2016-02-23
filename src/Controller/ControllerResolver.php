<?php

namespace Controller;

use Silex\Application;
use Silex\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adds User as a valid argument for controllers.
 */
class ControllerResolver extends BaseControllerResolver
{

    protected function doGetArguments(Request $request, $controller, array $parameters)
    {
        foreach ($parameters as $param) {
            /* @var $param \ReflectionParameter */
            if ($param->getClass() && $param->getClass()->isInstance($this->app['user'])) {
                $request->attributes->set($param->getName(), $this->app['user']);

                break;
            }
        }

        return parent::doGetArguments($request, $controller, $parameters);
    }
}