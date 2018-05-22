<?php

namespace Console\Command;

use ApiConsumer\Fetcher\FetcherService;
use ApiConsumer\Fetcher\ProcessorService;
use Console\ApplicationAwareCommand;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class LinksFetchAndPreProcessCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'links:fetch-and-preprocess';

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

    public function __construct(LoggerInterface $logger, FetcherService $fetcherService, ProcessorService $processorService, $resourceOwners)
    {
        parent::__construct($logger);
        $this->fetcherService = $fetcherService;
        $this->processorService = $processorService;
        $this->resourceOwners = $resourceOwners;
    }

    protected function configure()
    {
        $this->setDescription('Preprocess urls')
            ->setDefinition(
                array(
                    new InputOption(
                        'resource',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'The resource owner which should preprocess links'
                    ),
                    new InputOption(
                        'userId',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'ID of the user to preprocess links from'
                    ),
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resource = $input->getOption('resource');
        $userId = $input->getOption('userId');

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

        try {
            $links = $this->fetcherService->fetchUser($userId, $resource);
            $this->processorService->preProcess($links);

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
}
