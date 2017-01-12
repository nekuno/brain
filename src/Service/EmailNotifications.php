<?php

namespace Service;

use Model\Entity\EmailNotification;
use Doctrine\ORM\EntityManager;

class EmailNotifications
{
    private $mailer;
    private $em;
    private $tp;

    function __construct(\Swift_Mailer $mailer, EntityManager $entityManager, \Twig_Environment $twigServiceProvider)
    {
        $this->mailer = $mailer;
        $this->em = $entityManager;
        $this->tp = $twigServiceProvider;
    }

    public function send(EmailNotification $notification)
    {
        if (!$notification->getRecipient()) {
            throw new \Exception('Recipient not set');
        }

        $this->em->persist($notification);
        $this->em->flush();

        return $this->mail($notification);

    }

    private function mail(EmailNotification $notification)
    {

        switch ($notification->getType()) {
            case EmailNotification::UNREAD_CHAT_MESSAGES :
                $view = 'email-notifications/unread-messages-notification.html.twig';
                break;

            case EmailNotification::EXCEPTIONAL_LINKS :
                $view = 'email-notifications/exceptional_links_notification.html.twig';
                break;

            case EmailNotification::INVITATION :
                $view = 'email-notifications/invitation.html.twig';
                break;

            case EmailNotification::FOLLOWER_FOUND :
                $view = 'email-notifications/follower-found.html.twig';
                break;

            case EmailNotification::INFLUENCER_FOUND :
                $view = 'email-notifications/influencer-found.html.twig';
                break;

            default:
                $view = null;
                break;
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($notification->getSubject())
            ->setFrom('enredos@nekuno.com', 'Nekuno')
            ->setTo($notification->getRecipient())
            ->setContentType('text/html')
            ->setBody($this->tp->render($view, $notification->getInfo()));

        $recipients = $this->mailer->send($message);
        if (!$recipients) {
            throw new \RuntimeException('Email could not be sent');
        }

        return $recipients;
    }
}