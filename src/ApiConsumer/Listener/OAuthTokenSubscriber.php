<?php
namespace ApiConsumer\Listener;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Event\OAuthTokenEvent;
use ApiConsumer\Event\FilterTokenRefreshedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OAuthTokenSubscriber
 * @package ApiConsumer\Listener
 */
class OAuthTokenSubscriber implements EventSubscriberInterface
{
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
     */
    public function __construct(UserProviderInterface $userProvider, \Swift_Mailer $mailer, LoggerInterface $logger)
    {
        $this->userProvider = $userProvider;

        $this->mailer = $mailer;

        $this->logger = $logger;
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
            </html>"
        ;

        $message->setFrom('noreply@qnoow.dev.com');
        $message->setTo(array($user['email']));
        $message->setBody($body, 'text/html');

        if(0 === $this->mailer->send($message)){
            $this->logger->error(sprintf('Error: The notification email was not sent for address %s', $user['email']));
        }
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
}
