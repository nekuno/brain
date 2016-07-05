<?php

namespace Security\Http;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use Silex\Application;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap as ResourceOwnerMapBase;

/**
 * ResourceOwnerMap. Holds several resource owners for a firewall. Lazy
 * loads the appropriate resource owner when requested.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class ResourceOwnerMap extends ResourceOwnerMapBase
{
    /**
     * @var array
     */
    protected $resourceOwners;

    /**
     * @var array
     */
    protected $possibleResourceOwners;

	/**
	 * @var Application
	 */
	protected $app;

    /**
     * Constructor.
     *
     * @param array       $possibleResourceOwners Array with possible resource owners names.
     * @param array       $resourceOwners         Array with configured resource owners.
     * @param Application $app                    Silex Application
     */
    public function __construct(array $possibleResourceOwners, $resourceOwners, Application $app)
    {
        $this->possibleResourceOwners = $possibleResourceOwners;
        $this->resourceOwners         = $resourceOwners;
        $this->app                    = $app;
    }

    /**
     * Gets the appropriate resource owner given the name.
     *
     * @param string $name
     *
     * @return null|ResourceOwnerInterface
     */
    public function getResourceOwnerByName($name)
    {
        if (!$this->hasResourceOwnerByName($name)) {
            return null;
        }

        return $this->app['hwi_oauth.resource_owner.'.$name];
    }
}
