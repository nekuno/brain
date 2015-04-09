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

    function send(EmailNotification $notification)
    {
        if (! $notification->getRecipient()) throw new \Exception("Recipient not set");

        $this->em->persist($notification);
        $this->em->flush();

        $this->mail($notification);

    }

    private function mail(EmailNotification $notification)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($notification->getSubject())
            ->setTo($notification->getRecipient())
            ->setContentType('text/html')
            ->setBody($this->tp->render(
                'email-notifications/unread-messages-notification.html.twig',
                array('info' => $notification->getInfo())));


        if (! $this->mailer->send($message)) {
            throw new \RuntimeException("Email could not be sent");
        }

    }
}