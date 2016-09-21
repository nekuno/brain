<?php

namespace Console\Command;

use ApiConsumer\EventListener\FetchLinksInstantSubscriber;
use ApiConsumer\EventListener\FetchLinksSubscriber;
use ApiConsumer\EventListener\OAuthTokenSubscriber;
use ApiConsumer\Fetcher\FetcherService;
use Console\ApplicationAwareCommand;
use Model\User\SocialNetwork\SocialProfileManager;
use Model\User\TokensModel;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class LinksFetchCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('links:fetch')
            ->setDescription('Fetch links from given resource owner')
            ->setDefinition(
                array(
                    new InputOption(
                        'resource',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'The resource owner which should fetch links'
                    ),
                    new InputOption(
                        'user',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'ID of the user to fetch links from'
                    ),
                    new InputOption(
                        'public',
                        null,
                        InputOption::VALUE_NONE,
                        'Fetch as Nekuno instead of as the user'
                    ),
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $resource = $input->getOption('resource');
        $userId = $input->getOption('user');
        $public = $input->getOption('public');

        if (null === $resource && null === $userId) {
            throw new MissingOptionsException ("You must provide the user or the resource to fetch links from", array("resource", "user"));
        }

        if (null !== $resource) {
            $resourceOwners = $this->app['api_consumer.config']['resource_owner'];
            $availableResourceOwners = implode(', ', array_keys($resourceOwners));

            if (!isset($resourceOwners[$resource])) {
                $output->writeln(sprintf('Resource owner %s not found, available resource owners: %s.', $resource, $availableResourceOwners));

                return;
            }
        }

        /* @var $tokensModel TokensModel */
        $tokensModel = $this->app['users.tokens.model'];

        if (!$public) {

            $tokens = $tokensModel->getByUserOrResource($userId, $resource);
        } else {
            /* @var $socialProfileManager SocialProfileManager */
            $socialProfileManager = $this->app['users.socialprofile.manager'];

            $profiles = $socialProfileManager->getSocialProfiles($userId, $resource, false);
            $tokens = array();
            foreach ($profiles as $profile)
            {
                $tokens[] = $tokensModel->buildFromSocialProfile($profile);
            }
        }

        /* @var FetcherService $fetcher */
        $fetcher = $this->app['api_consumer.fetcher'];

        $logger = new ConsoleLogger($output, array(LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL));
        $fetcher->setLogger($logger);

        $fetchLinksSubscriber = new FetchLinksSubscriber($output);
        $fetchLinksInstantSubscriber = new FetchLinksInstantSubscriber($this->app['guzzle.client'], $this->app['instant.host']);
        $oauthTokenSubscriber = new OAuthTokenSubscriber($this->app['users.tokens.model'], $this->app['mailer'], $this->app['logger'], $this->app['amqp']);
        $dispatcher = $this->app['dispatcher'];
        /* @var $dispatcher EventDispatcher */
        $dispatcher->addSubscriber($fetchLinksSubscriber);
        $dispatcher->addSubscriber($fetchLinksInstantSubscriber);
        $dispatcher->addSubscriber($oauthTokenSubscriber);

        foreach ($tokens as $token) {
            try {
                $token['public'] = $public;
                $fetcher->fetch( $token);

            } catch (\Exception $e) {
                $output->writeln(
                    sprintf(
                        'Error fetching links for user %d with message: %s',
                        $token['id'],
                        $e->getMessage()
                    )
                );
                continue;
            }
        }

        $output->writeln('Success!');
    }
}
