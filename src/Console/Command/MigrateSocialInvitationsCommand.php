<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Silex\Application;
use Service\MigrateSocialInvitations;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateSocialInvitationsCommand extends ApplicationAwareCommand
{
    protected function configure()
    {
        $this->setName('migrate:social-invitations')
            ->setDescription('Migrate invitations from social to brain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var  $migrateSocialInvitations MigrateSocialInvitations */
        $migrateSocialInvitations = $this->app['migrateSocialInvitations.service'];

        $migrateSocialInvitations->migrateInvitations($output);

        $output->writeln('Done');
    }
}