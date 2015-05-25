<?php


namespace Model\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\GeneratedValue;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Email;

/**
 * @Entity(repositoryClass="EmailNotificationRepository")
 * @Table(name="email_notification")
 * @HasLifecycleCallbacks()
 */
class EmailNotification
{

    const UNREAD_CHAT_MESSAGES = 1;
    const EXCEPTIONAL_LINKS = 2;

    /**
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="notification_id", type="integer")
     */
    protected $notificationId;

    /**
     * @Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @Email
     * @Column(name="recipient", type="string", nullable=false)
     */
    protected $recipient;

    /**
     * @Column(name="subject", type="string")
     */
    protected $subject;

    /**
     * @Column(name="type", nullable=false)
     * @Choice(callback={"getTypes"})
     * @ORM\Column
     */
    protected $type;

    /**
     * @Column(name="info", type="array", nullable=false)
     */
    protected $info;

    /**
     * @Column(name="created_at", type="datetime")
     */
    protected $createdAt;


    static public function create()
    {
        return new static;
    }

    /**
     * Get user
     *
     * @return integer
     */
    public function getUserId()
    {

        return $this->userId;
    }

    /**
     * Set user
     *
     * @param integer $userId
     * @return EmailNotification
     */
    public function setUserId($userId)
    {

        $this->userId = $userId;

        return $this;
    }

    /**
     * Get recipient
     *
     * @return string
     */
    public function getRecipient()
    {

        return $this->recipient;
    }

    /**
     * Set recipient
     *
     * @param string $recipient
     * @return EmailNotification
     */
    public function setRecipient($recipient)
    {

        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return EmailNotification
     */
    public function setSubject($subject)
    {

        $this->subject = $subject;

        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {

        return $this->type;
    }

    /**
     * Set type
     *
     * @param int $type
     * @return EmailNotification
     */
    public function setType($type)
    {

        $this->type = $type;

        return $this;
    }

    /**
     * Get types
     *
     * @return array
     */
    public static function getTypes()
    {
        return array(
            self::UNREAD_CHAT_MESSAGES,
            self::EXCEPTIONAL_LINKS,
        );
    }

    /**
     * Get info
     *
     * @return array
     */
    public function getInfo()
    {

        return $this->info;
    }

    /**
     * Set info
     *
     * @param array $info
     * @return EmailNotification
     */
    public function setInfo($info)
    {

        $this->info = $info;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {

        return $this->createdAt;
    }

    /**
     * @prePersist
     */
    public function setCreatedAt()
    {

        $this->createdAt = new \DateTime();
    }
}
