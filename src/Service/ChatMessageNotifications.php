<?

namespace Service;

use Model\Entity\EmailNotification;
use Doctrine\ORM\EntityManager;
use Model\User\ProfileModel;
use Model\UserModel;
use Doctrine\DBAL\Connection;
use Service\EmailNotifications;
use Silex\Application\TranslationTrait;
use Silex\Application;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\HttpFoundation\Request;

/**
 * ChatMessageNotifications
 */
class ChatMessageNotifications
{

    /**
     * @var EmailNotifications
     */
    private $emailNotifications;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Connection
     */
    protected $driver;

    /**
     * @var TranslationTrait
     */
    protected $translator;

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var ProfileModel
     */
    protected $profileModel;

    /**
     * @var Request
     */
    protected $request;


    function __construct(EmailNotifications $emailNotifications, EntityManager $em, Connection $driver, TranslationTrait $translator,  UserModel $userModel, ProfileModel $profileModel, Request $request)
    {
        $this->emailNotifications = $emailNotifications;
        $this->em = $em;
        $this->driver = $driver;
        $this->translator = $translator;
        $this->userModel = $userModel;
        $this->profileModel = $profileModel;
        $this->request = $request;
    }

    function sendUnreadChatMessages($limit = 9999999999, Output $output)
    {
        $usersIds = $this->getUsersWithUnreadMessages($limit);

        $output->writeln( count($usersIds) . ' users with unread messages found');

        foreach($usersIds as $userId)
        {
            $chatMessages = $this->getUnReadMessagesByUser($userId);

            $output->writeln( count($chatMessages) . ' unread messages found for user ' . $userId );

            $filteredChatMessages = $this->filterMessages($chatMessages);

            $user = $this->userModel->getById($userId);
            $profile = $this->profileModel->getById($userId);

            if(! $user || ! $profile)
            {
                $output->writeln('User or Profile not found');
            }

            $this->request->getSession()->set('_locale', $profile['options']['InterfaceLanguage']['id']);

            $this->emailNotifications->send(EmailNotification::create()
                ->setType(1)
                ->setUserId($userId)
                ->setRecipient($user['email'])
                ->setSubject($this->translator->trans('notifications.messages.unread.subject'))
                ->setInfo($this->saveInfo($user, $filteredChatMessages))
            );

            $output->writeln( 'Email sent to ' . $userId . ' with ' .  count($filteredChatMessages) . 'messages.' );
        }
    }

    protected function filterMessages(array $chatMessages)
    {

        $usersFrom = array();
        $return = array();

        // Get users_from
        foreach($chatMessages as $chatMessage)
        {
            if(! in_array($chatMessage['user_from'], $usersFrom))
                $usersFrom[] = $chatMessage['user_from'];
        }

        // Get filtered messages
        foreach($usersFrom as $indexUser => $userFrom)
        {
            // Maximum 3 users
            if($indexUser > 3)
            {
                break;
            }

            foreach($chatMessages as $chatMessage)
            {
                if($chatMessage['user_from'] === $userFrom)
                {
                    $return[] = $chatMessage;

                    // Maximum 1 message per user
                    break;
                }
            }
        }

        return $return;

    }

    /**
     * Get users with unread chat messages (until 24h ago) (SOCIAL DB)
     *
     * @param int $limit
     * @return array
     */
    protected function getUsersWithUnreadMessages($limit = 999999999999)
    {
        $yesterday = new \DateTime('-1 day');
        $qb = $this->driver->createQueryBuilder('chat_message.user_to')
            ->where('chat_message.readed = 0')
            ->where('chat_message.createdAt > :yesterday')
            ->orderBy('chat_message.createdAt', 'asc')
            ->setMaxResults(':limit')
            ->setParameter('limit', $limit)
            ->setParameter('yesterday', $yesterday->getTimestamp());

        return $qb->execute()->fetchAll();
    }

    /**
     * Get unread chat messages by user (until 24h ago) (SOCIAL DB)
     *
     * @param int $userId
     * @return array
     */
    protected function getUnReadMessagesByUser($userId)
    {
        $yesterday = new \DateTime('-1 day');
        $qb = $this->driver->createQueryBuilder('chat_message as ch')
            ->where('ch.readed = 0')
            ->where('ch.createdAt > :yesterday')
            ->where('n.user_to = :user')
            ->groupBy('ch.user_from')
            ->orderBy('ch.createdAt', 'desc')
            ->setParameter('user_to', $userId)
            ->setParameter('yesterday', $yesterday->getTimestamp());

        return $qb->execute()->fetchAll();
    }

    protected function saveInfo(array $user, array $chatMessages)
    {
        foreach($chatMessages as $index => $chatMessage)
        {
            $chatMessages[$index]['username_from'] = $this->userModel->getById($chatMessage['user_from'])['username'];
        }

        return array(
            'unReadMessagesCount' => count($chatMessages),
            'username' => $user['username'],
            'email' => $user['email'],
            'messages' => $chatMessages
        );
    }

}