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

	    // Create ResourceOwner's services
	    foreach ($app['hwi_oauth']['resource_owners'] as $name => $checkPath) {
		    $app['hwi_oauth.resource_owner.' . $name] = $app->share(
			    function ($app) use ($name) {
					$options = $app['hwi_oauth']['resource_owners'][$name];
				    $type = $options['type'];
				    $class = "Http\\OAuth\\ResourceOwner\\" . ucfirst($type) . "ResourceOwner";

				    return new $class($app['guzzle.client'], $app['dispatcher'], array(
					    'consumer_key' => $options['client_id'],
					    'consumer_secret' => $options['client_secret'],
					    'class' => $class

				    ));
		        }
	        );
	    }

	    $app['oauth.resorcer_owner_map'] = $app->share(
		    function ($app) {

			    $resourceOwnersMap = array();
			    foreach ($app['hwi_oauth']['resource_owners'] as $name => $checkPath) {
				    $resourceOwnersMap[$name] = "";
			    }
			    $resourceOwnerMap =  new ResourceOwnerMap($app['oauth.httpUtils'], $app['hwi_oauth']['resource_owners'], $resourceOwnersMap);
			    /* TODO: Symfony $container is needed for getting the resource owner by name from ResourceOwnerMap (getResourceOwnerByName)
			       Of course, this throws an error because the container is expected. How can we solve it? */
			    $resourceOwnerMap->setContainer($app);

			    return $resourceOwnerMap;
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
