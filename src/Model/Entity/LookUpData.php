<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
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

    /**
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @Column(name="name", type="string", nullable=true)
     */
    protected $name;

    /**
     * @Column(name="email", type="string", nullable=true)
     * @Assert\Email
     */
    protected $email;

    /**
     * @Column(name="gender", type="string", nullable=true)
     * @Assert\Choice(choices = {"male", "female"}, message = "Choose a valid gender.")
     */
    protected $gender;

    /**
     * @Column(name="location", type="string", nullable=true)
     */
    protected $location;

    /**
     * @Column(name="social_profiles", type="array", nullable=true)
     * @Asset\Collection()
     */
    protected $socialProfiles;

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
     * @Column(name="updated_at", type="datetime")
     * @Assert\DateTime()
     */
    protected $updatedAt;

    function __construct($email = null, $gender = null, $location = null, $name = null, $socialProfiles = array())
    {
        $this->email = $email;
        $this->gender = $gender;
        $this->location = $location;
        $this->name = $name;
        $this->socialProfiles = $socialProfiles;
    }

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
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $socialProfiles
     */
    public function setSocialProfiles($socialProfiles)
    {
        $this->socialProfiles = $socialProfiles;
    }

    /**
     * @return array
     */
    public function getSocialProfiles()
    {
        return $this->socialProfiles;
    }

    /**
     * @param array
     */
    public function addSocialProfiles(array $socialProfiles)
    {
        foreach($socialProfiles as $index => $socialProfile) {
            $this->socialProfiles[$index] = $socialProfile;
        }
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

    public function toArray()
    {
        return array(
            'name' => $this->name,
            'email' => $this->email,
            'gender' => $this->gender,
            'location' => $this->location,
            'socialProfiles' => $this->socialProfiles,
        );
    }

    static function getTypes()
    {
        return array(
            self::LOOKED_UP_BY_EMAIL,
            self::LOOKED_UP_BY_TWITTER_USERNAME,
            self::LOOKED_UP_BY_FACEBOOK_USERNAME,
        );
    }

}
