<?php

namespace Security;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
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
     * @var array
     */
    protected $hwiOAuth;

    /**
     * Constructor.
     *
     * @param array $resourceOwners Main array.
     * @param array $possibleResourceOwners Main array.
     * @param array $hwiOAuth Main array.
     */
    public function __construct($resourceOwners, $possibleResourceOwners, array $hwiOAuth)
    {
        $this->possibleResourceOwners = $possibleResourceOwners;
        $this->resourceOwners = $resourceOwners;
        $this->hwiOAuth = $hwiOAuth;
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

        return $this->hwiOAuth['resource_owner.' . $name];
    }
}
