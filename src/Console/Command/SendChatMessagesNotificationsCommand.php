<?php

namespace Console\Command;

use Silex\Application;
use Service\ChatMessageNotifications;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendChatMessagesNotifications extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('chat-email-notifications:send')
            ->setDescription('Send chat notifications (unread messages since last 24h)')
            ->addOption('limit', 'lim', InputOption::VALUE_REQUIRED, 'Notifications limit', 99999);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getOption('limit');

        if (! is_int($limit)) {
            $output->writeln(sprintf('Limit must be an integer, %s given.', gettype($limit)));
            return;
        }

        /** @var  $chatMessageNotifications ChatMessageNotifications */
        $chatMessageNotifications = $this->app['chatMessageNotifications.service'];

        try {

            $chatMessageNotifications->sendUnreadChatMessages($limit, $output);

        } catch (\Exception $e) {

            $output->writeln('Error trying to send emails: ' . $e->getMessage());
        }

        $output->writeln('Done.');

    }

}
