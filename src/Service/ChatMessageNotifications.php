<?php

namespace Service;

use Model\Entity\EmailNotification;
use Doctrine\ORM\EntityManager;
use Model\User;
use Model\User\ProfileModel;
use Manager\UserManager;
use Doctrine\DBAL\Connection;
use Silex\Translator;
use Silex\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Console\Command\SwiftMailerChatSendCommand;

/**
 * ChatMessageNotifications
 */
class ChatMessageNotifications
{

    /**
     * @var EmailNotifications
     */
    protected $emailNotifications;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManagerBrain;

    /**
     * @var Connection
     */
    protected $connectionBrain;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var ProfileModel
     */
    protected $profileModel;

    function __construct(EmailNotifications $emailNotifications, EntityManager $entityManagerBrain, Connection $connectionBrain, Translator $translator, UserManager $userManager, ProfileModel $profileModel)
    {
        $this->emailNotifications = $emailNotifications;
        $this->entityManagerBrain = $entityManagerBrain;
        $this->connectionBrain = $connectionBrain;
        $this->translator = $translator;
        $this->userManager = $userManager;
        $this->profileModel = $profileModel;
    }

    function sendUnreadChatMessages($limit = 99999, OutputInterface $output, SwiftMailerChatSendCommand $chatMessagesNotificationsCommand)
    {
        $usersIds = $this->getUsersWithUnreadMessages($limit);

        $output->writeln(count($usersIds) . ' users with unread messages found');

        foreach ($usersIds as $userId) {
            $userId = (int)$userId['user_to'];

            $chatMessages = $this->getUnReadMessagesByUser($userId);

            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $output->writeln(count($chatMessages) . ' unread messages found for user ' . $userId);
            }

            $filteredChatMessages = $this->filterMessages($chatMessages);

            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity()) {
                foreach ($filteredChatMessages as $message) {
                    $output->writeln('Message for user ' . $userId);
                    $table = $chatMessagesNotificationsCommand->getHelper('table');

                    $table->setHeaders(array_keys($message))
                        ->setRows(array($message))
                        ->render($output);
                }
            }

            $user = $this->userManager->getById($userId);
            $profile = $this->profileModel->getById($userId);

            if (!$profile && OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $output->writeln('Profile ' . $userId . ' not found. Using default locale (' . $this->translator->getLocale() . ').');
            }

            if (isset($profile['interfaceLanguage']) && $profile['interfaceLanguage']) {
                $this->translator->setLocale($profile['interfaceLanguage']);

                if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                    $output->writeln('Profile ' . $userId . ' found. Using locale ' . $profile['interfaceLanguage']);
                }

            }

            $this->emailNotifications->send(
                EmailNotification::create()
                    ->setType(EmailNotification::UNREAD_CHAT_MESSAGES)
                    ->setUserId($userId)
                    ->setRecipient($user->getEmail())
                    ->setSubject($this->translator->trans('notifications.messages.unread.subject'))
                    ->setInfo($this->saveInfo($user, $filteredChatMessages, count($chatMessages)))
            );

            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $output->writeln('Email sent to user ' . $userId . ' with ' . count($filteredChatMessages) . ' messages.');
            }
        }
    }

    public function createDefaultMessage($to, $locale)
    {
        $qb = $this->connectionBrain->createQueryBuilder()
            ->insert('chat_message')->values(array(
                'user_to' => $to,
                'user_from' => 16,
                'text' => ":text",
                'readed' => 0,
                'createdAt' => ':now',
            ));

        $qb->setParameter('now', new \DateTime('now'), \Doctrine\DBAL\Types\Type::DATETIME);
        $this->translator->setLocale($locale);
        $qb->setParameter('text', $this->translator->trans('messages.register'));
        $qb->execute();
    }

    protected function filterMessages(array $chatMessages)
    {

        $usersFrom = array();
        $return = array();

        // Get users_from
        foreach ($chatMessages as $chatMessage) {
            if (!in_array($chatMessage['user_from'], $usersFrom)) {
                $usersFrom[] = $chatMessage['user_from'];
            }
        }

        // Get filtered messages
        foreach ($usersFrom as $indexUser => $userFrom) {
            // Maximum 3 users
            if ($indexUser > 3) {
                break;
            }

            $thisUserChatMessages = array();
            foreach ($chatMessages as $chatMessage) {
                if ($chatMessage['user_from'] === $userFrom) {
                    $thisUserChatMessages[] = $chatMessage;
                }
            }

            // Maximum 1 message per user
            $return[] = $thisUserChatMessages[count($thisUserChatMessages) - 1];
        }

        return $return;

    }

    /**
     * Get users with unread chat messages (until 24h ago) (MYSQL BRAIN DB)
     *
     * @param int $limit
     * @return array
     */
    protected function getUsersWithUnreadMessages($limit = 999999999999)
    {
        $yesterday = new \DateTime('-1 day');
        $yesterday = $yesterday->format("Y-m-d H:m:i");
        $qb = $this->connectionBrain->createQueryBuilder('chat_message')
            ->select('DISTINCT chat_message.user_to')
            ->from('chat_message')
            ->where('chat_message.readed = 0')
            ->andWhere('chat_message.createdAt > :yesterday')
            ->orderBy('chat_message.createdAt', 'desc')
            ->setMaxResults($limit)
            ->setParameter('yesterday', $yesterday);

        return $qb->execute()->fetchAll();
    }

    /**
     * Get unread chat messages by user (until 24h ago) (MYSQL BRAIN DB)
     *
     * @param int $userId
     * @return array
     */
    protected function getUnReadMessagesByUser($userId)
    {
        $yesterday = new \DateTime('-1 day');
        $yesterday = $yesterday->format("Y-m-d H:m:i");
        $qb = $this->connectionBrain->createQueryBuilder('chat_message')
            ->select('*')
            ->from('chat_message')
            ->where('chat_message.readed = 0')
            ->andWhere('chat_message.createdAt > :yesterday')
            ->andWhere('chat_message.user_to = :user_to')
            ->orderBy('chat_message.createdAt', 'desc')
            ->setParameter('user_to', $userId)
            ->setParameter('yesterday', $yesterday);

        return $qb->execute()->fetchAll();
    }

    protected function saveInfo(User $user, array $chatMessages, $totalMessages)
    {
        foreach ($chatMessages as $index => $chatMessage) {
            $chatMessages[$index]['username_from'] = $this->userManager->getById($chatMessage['user_from'])->getUsername();
            $chatMessages[$index]['picture_from'] = $this->userManager->getById($chatMessage['user_from'])->getPhoto()->getUrl();
        }

        return array(
            'unReadMessagesCount' => $totalMessages,
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'messages' => $chatMessages
        );
    }

}