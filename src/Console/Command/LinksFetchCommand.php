<?php

namespace Console\Command;

use ApiConsumer\EventListener\FetchLinksInstantSubscriber;
use ApiConsumer\EventListener\FetchLinksSubscriber;
use ApiConsumer\EventListener\OAuthTokenSubscriber;
use ApiConsumer\Fetcher\FetcherService;
use ApiConsumer\Fetcher\ProcessorService;
use Console\ApplicationAwareCommand;
use Event\ProcessLinksEvent;
use Model\Token\TokensManager;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Service\DeviceService;
use Service\InstantConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Service\EventDispatcher;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class LinksFetchCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'links:fetch';

    /**
     * @var FetcherService
     */
    protected $fetcherService;

    /**
     * @var ProcessorService
     */
    protected $processorService;

    /**
     * @var string[]
     */
    protected $resourceOwners;

    /**
     * @var InstantConnection
     */
    protected $instantConnection;

    /**
     * @var DeviceService
     */
    protected $deviceService;

    /**
     * @var TokensManager
     */
    protected $tokensManager;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var AMQPStreamConnection
     */
    protected $AMQPStreamConnection;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    public function __construct(LoggerInterface $logger, FetcherService $fetcherService, ProcessorService $processorService, $resourceOwners, InstantConnection $instantConnection, DeviceService $deviceService, TokensManager $tokensManager, \Swift_Mailer $mailer, AMQPStreamConnection $AMQPStreamConnection, EventDispatcher $dispatcher)    {
        parent::__construct($logger);
        $this->fetcherService = $fetcherService;
        $this->processorService = $processorService;
        $this->resourceOwners = $resourceOwners;
        $this->instantConnection = $instantConnection;
        $this->deviceService = $deviceService;
        $this->tokensManager = $tokensManager;
        $this->mailer = $mailer;
        $this->AMQPStreamConnection = $AMQPStreamConnection;
        $this->dispatcher = $dispatcher;
    }

    protected function configure()
    {
        $this
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
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $resource = $input->getOption('resource');
        $userId = $input->getOption('user');

        if (null === $resource && null === $userId) {
            throw new MissingOptionsException ("You must provide the user or the resource to fetch links from", array("resource", "user"));
        }

        if (null !== $resource) {
            $availableResourceOwners = implode(', ', array_keys($this->resourceOwners));

            if (!isset($this->resourceOwners[$resource])) {
                $output->writeln(sprintf('Resource owner %s not found, available resource owners: %s.', $resource, $availableResourceOwners));

                return;
            }
        }

        $logger = new ConsoleLogger($output, array(LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL));
        $this->fetcherService->setLogger($logger);
        $this->processorService->setLogger($logger);

        $dispatcher = $this->setUpSubscribers($output);

            try {
                $links = $this->fetcherService->fetchUser($userId, $resource);
                $dispatcher->dispatch(\AppEvents::PROCESS_START, new ProcessLinksEvent($userId, $resource, $links));
                $this->processorService->process($links, $userId);
                $dispatcher->dispatch(\AppEvents::PROCESS_FINISH, new ProcessLinksEvent($userId, $resource, $links));

            } catch (\Exception $e) {
                $output->writeln(
                    sprintf(
                        'Error fetching links for user %d with message: %s',
                        $userId,
                        $e->getMessage()
                    )
                );
            }

        $output->writeln('Success!');
    }

    private function setUpSubscribers(OutputInterface $output)
    {
        $fetchLinksSubscriber = new FetchLinksSubscriber($output);
        $fetchLinksInstantSubscriber = new FetchLinksInstantSubscriber($this->instantConnection, $this->deviceService);
        $oauthTokenSubscriber = new OAuthTokenSubscriber($this->tokensManager, $this->mailer, $this->logger, $this->AMQPStreamConnection);

        $this->dispatcher->addSubscriber($fetchLinksSubscriber);
        $this->dispatcher->addSubscriber($fetchLinksInstantSubscriber);
        $this->dispatcher->addSubscriber($oauthTokenSubscriber);

        return $this->dispatcher;
    }
}
