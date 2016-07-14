<?php

namespace Provider;

use Buzz\Client\Curl;
use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorage\SessionStorage;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider;
use Security\Http\ResourceOwnerMap;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserChecker;
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

        $app['hwi_oauth.http_client'] = $app->share(
            function() {

                return new Curl();
            }
        );

        $app['security.http_utils'] = $app->share(
            function() {

                return new HttpUtils();
            }
        );

        $app['hwi_oauth.storage.session'] = $app->share(
            function() {

                $session = new Session();
                return new SessionStorage($session);
            }
        );

	    // Create ResourceOwner's services
        foreach ($app['hwi_oauth']['resource_owners'] as $name => $options) {
            $app['hwi_oauth.resource_owner.' . $name] = $app->share(
                function ($app) use ($name, $options) {
                    unset($options['type']);
                    $class = 'ApiConsumer\\ResourceOwner\\' . ucfirst($name) . 'ResourceOwner';

                    return new $class($app['hwi_oauth.http_client'], $app['security.http_utils'], $options, $name, $app['hwi_oauth.storage.session'], $app['dispatcher']);
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
