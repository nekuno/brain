<?php

namespace Provider;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Core\User\UserChecker;
use Security\Http\ResourceOwnerMap;

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

	    // Create ResourceOwner's services
	    foreach ($app['hwi_oauth']['resource_owners'] as $name => $checkPath) {
		    $app['hwi_oauth.resource_owner.' . $name] = $app->share(
			    function ($app) use ($name) {
					$options = $app['hwi_oauth']['resource_owners'][$name];
				    $type = $options['type'];
				    $class = "HWI\\Bundle\\OAuthBundle\\OAuth\\ResourceOwner\\" . ucfirst($type) . "ResourceOwner";

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
			    $resourceOwnerMap =  new ResourceOwnerMap($app['hwi_oauth']['resource_owners'], $resourceOwnersMap, $app);

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
