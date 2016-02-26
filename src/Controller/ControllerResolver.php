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
            if ($param->getClass() && $param->getClass()->isSubclassOf('Symfony\Component\Security\Core\User\UserInterface')) {

                if (!$this->app['user']) {
                    if (is_array($controller)) {
                        $repr = sprintf('%s::%s()', get_class($controller[0]), $controller[1]);
                    } elseif (is_object($controller)) {
                        $repr = get_class($controller);
                    } else {
                        $repr = $controller;
                    }
                    throw new \RuntimeException(sprintf('Controller "%s" uses User "$%s" but user is not authenticated (missing jwt).', $repr, $param->name));
                }

                $request->attributes->set($param->getName(), $this->app['user']);

                break;
            }
        }

        return parent::doGetArguments($request, $controller, $parameters);
    }
}