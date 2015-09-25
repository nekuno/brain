<?php
namespace ApiConsumer\EventListener;

use ApiConsumer\Event\OAuthTokenEvent;
use Model\User\TokensModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OAuthTokenSubscriber
 * @package ApiConsumer\Listener
 */
class OAuthTokenSubscriber implements EventSubscriberInterface
{

    /**
     * @var AMQPStreamConnection
     */
    protected $amqp;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var TokensModel
     */
    protected $tm;

    /**
     * @param TokensModel $tm
     * @param Swift_Mailer $mailer
     * @param LoggerInterface $logger
     * @param AMQPStreamConnection $amqp
     */
    public function __construct(TokensModel $tm, Swift_Mailer $mailer, LoggerInterface $logger, AMQPStreamConnection $amqp)
    {
        $this->tm = $tm;
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
            \AppEvents::TOKEN_REFRESHED => array('onTokenRefreshed', 0),
            \AppEvents::TOKEN_EXPIRED => array('onTokenExpired', 0),
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

        try {
            $channel = $this->amqp->channel();
        } catch (AMQPRuntimeException $e) {
            $this->amqp->reconnect();
            $channel = $this->amqp->channel();
        }

        $exchangeName = 'social.direct';
        $exchangeType = 'direct';
        $queueName = 'social.notification';
        $routing_key = 'social.notification.token_expire';

        $channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $exchangeName, $routing_key);

        $messageData = array(
            'user' => $user['id'],
            'resourceOwner' => $user['resourceOwner'],
            'message' => 'Token for ' . $user['resourceOwner'] . ' is expired.'
        );

        $message = new AMQPMessage(json_encode($messageData), array('delivery_mode' => 2));
        $channel->basic_publish($message, $exchangeName, $routing_key);

        $channel->close();
    }

    /**
     * @param OAuthTokenEvent $event
     */
    public function onTokenRefreshed(OAuthTokenEvent $event)
    {
        $user = $event->getUser();
        $this->tm->updateOauthToken(
            $user['id'],
            $user['resourceOwner'],
            $user['oauthToken'],
            $user['createdTime'],
            $user['expireTime']
        );

        if (isset($user['refreshToken']) && (null !== $user['refreshToken'])) {
            $this->tm->updateRefreshToken(
                $user['id'],
                $user['resourceOwner'],
                $user['refreshToken']
            );
        }
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
