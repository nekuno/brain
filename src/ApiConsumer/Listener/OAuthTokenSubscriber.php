<?php
namespace ApiConsumer\Listener;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\Event\FilterTokenRefreshedEvent;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OAuthTokenSubscriber
 * @package ApiConsumer\Listener
 */
class OAuthTokenSubscriber implements EventSubscriberInterface
{

    /**
     * @var \PhpAmqpLib\Connection\AMQPConnection
     */
    private $amqp;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @param UserProviderInterface $userProvider
     * @param \Swift_Mailer $mailer
     * @param LoggerInterface $logger
     * @param \PhpAmqpLib\Connection\AMQPConnection $amqp
     */
    public function __construct(UserProviderInterface $userProvider, \Swift_Mailer $mailer, LoggerInterface $logger, AMQPConnection $amqp)
    {
        $this->userProvider = $userProvider;

        $this->mailer = $mailer;

        $this->logger = $logger;

        $this->amqp = $amqp;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'token.refreshed' => array('onTokenRefreshed', 0),
            'token.expired' => array('onTokenExpired', 0),
        );
    }

    /**
     * @param OAuthTokenEvent $event
     * @throws \Exception
     */
    public function onTokenExpired(OAuthTokenEvent $event)
    {

        $user = $event->getUser();

        $this->sendMail($user);

        $channel = $this->amqp->channel();

        $exchange = 'notifications';
        $queue = 'notification';
        $channel->exchange_declare($exchange, 'direct', false, true, false);

        $routing_key = 'notification.token_expire';
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind('notification', 'notifications', $routing_key);

        $messageData = array(
            'user' => $user['id'],
            'resourceOwner' => $user['resourceOwner'],
            'message' => 'Token for ' . $user['resourceOwner'] . ' is expired.'
        );

        $message = new AMQPMessage(serialize($messageData), array('delivery_mode' => 2));
        $channel->basic_publish($message, 'notifications', $routing_key);


        $channel->close();
        $this->amqp->close();
    }

    /**
     * @param OAuthTokenEvent $event
     */
    public function onTokenRefreshed(OAuthTokenEvent $event)
    {
        $user = $event->getUser();
        $this->userProvider->updateAccessToken(
            $user['resourceOwner'],
            $user['user_id'],
            $user['oauthToken'],
            $user['createdTime'],
            $user['expireTime']
        );
    }

    /**
     * @param array $user
     * @return int
     */
    protected function sendMail(array $user)
    {

        $loginUrl = 'http://qnoow.dev.com/app_dev.php/connect/' . $user['resourceOwner'];

        $message = \Swift_Message::newInstance('Action required');
        $body = "
            <!DOCTYPE html>
            <html>
            <head>
            </head>
            <body>
                <h2>Hello! {$user['username']}</h2>
                <p>We need that you grant access to your {$user['resourceOwner']} account again. Please click the link below.</p>
                <a href='{$loginUrl}'>Grant access</a>
            </body>
            </html>";

        $message->setFrom('noreply@qnoow.com');
        $message->setTo(array($user['email']));
        $message->setBody($body, 'text/html');

        return $this->mailer->send($message);
    }
}
