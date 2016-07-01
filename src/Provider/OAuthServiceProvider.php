<?php

namespace Provider;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Core\User\UserChecker;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Symfony\Component\Security\Http\HttpUtils;

class OAuthServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {
	    $app['user.checker'] = $app->share(
		    function () {

			    return new UserChecker();
		    }
	    );

	    $app['oauth.httpUtils'] = $app->share(
		    function () {

			    return new HttpUtils();
		    }
	    );

	    $app['oauth.resorcer_owner_map'] = $app->share(
		    function ($app) {

			    $resourceOwnersMap = array();
			    foreach ($app['hwi_oauth.resource_owners'] as $name => $checkPath) {
				    $resourceOwnersMap[$name] = "";
			    }

			    return new ResourceOwnerMap($app['oauth.httpUtils'], $app['hwi_oauth.resource_owners'], $resourceOwnersMap);
		    }
	    );

        $app['oauth.service'] = $app->share(
            function ($app) {

                return new OAuthProvider($app['security.users_provider'], $app['oauth.resorcer_owner_map'], $app['user.checker']);
            }
        );
    }

    /**
     * { @inheritdoc }
     */
    public function boot(Application $app)
    {

    }

}
