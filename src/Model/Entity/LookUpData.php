<?php

namespace Model\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\GeneratedValue;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Entity(repositoryClass="LookUpDataRepository")
 * @Table(name="look_up_data")
 * @HasLifecycleCallbacks()
 */
class LookUpData
{
    const LOOKED_UP_BY_EMAIL = 'BY_EMAIL';
    const LOOKED_UP_BY_TWITTER_USERNAME = 'BY_TWITTER_USERNAME';
    const LOOKED_UP_BY_FACEBOOK_USERNAME = 'BY_FACEBOOK_USERNAME';

    const FULLCONTACT_API_RESOURCE = 'FULLCONTACT';
    const PEOPLEGRAPH_API_RESOURCE = 'PEOPLEGRAPH';

    /**
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @Column(name="looked_up_type", type="string", nullable=true)
     * @Assert\Choice(callback={"getTypes"}, message = "Choose a valid type.")
     */
    protected $lookedUpType;

    /**
     * @Column(name="looked_up_value", type="string", nullable=true)
     */
    protected $lookedUpValue;

    /**
     * @Column(name="response", type="array", nullable=false)
     */
    protected $response = array();

    /**
     * @Column(name="api_resource", type="string", nullable=true)
     * @Assert\Choice(callback={"getApiResourceTypes"}, message = "Choose a valid API resource.")
     */
    protected $apiResource;

    /**
     * @Column(name="updated_at", type="datetime")
     * @Assert\DateTime()
     */
    protected $updatedAt;

    /**
     * @Column(name="hash", type="string")
     */
    protected $hash;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $lookedUpType
     */
    public function setLookedUpType($lookedUpType)
    {
        $this->lookedUpType = $lookedUpType;
    }

    /**
     * @return string
     */
    public function getLookedUpType()
    {
        return $this->lookedUpType;
    }

    /**
     * @param string $lookedUpValue
     */
    public function setLookedUpValue($lookedUpValue)
    {
        $this->lookedUpValue = $lookedUpValue;
    }

    /**
     * @return string
     */
    public function getLookedUpValue()
    {
        return $this->lookedUpValue;
    }

    /**
     * @param array $response
     */
    public function setResponse(array $response)
    {
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $apiResource
     */
    public function setApiResource($apiResource)
    {
        $this->apiResource = $apiResource;
    }

    /**
     * @return string
     */
    public function getApiResource()
    {
        return $this->apiResource;
    }

    /**
     * @prePersist
     */
    public function setHash()
    {
        $this->hash = md5($this->getLookedUpType() . $this->getLookedUpValue());
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Get updateAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {

        return $this->updatedAt;
    }

    /**
     * @prePersist
     */
    public function setUpdatedAt()
    {

        $this->updatedAt = new \DateTime();
    }

    static function getTypes()
    {
        return array(
            self::LOOKED_UP_BY_EMAIL,
            self::LOOKED_UP_BY_TWITTER_USERNAME,
            self::LOOKED_UP_BY_FACEBOOK_USERNAME,
        );
    }

    static function getApiResourceTypes()
    {
        return array(
            self::FULLCONTACT_API_RESOURCE,
            self::PEOPLEGRAPH_API_RESOURCE,
        );
    }
}
