<?php

namespace Console\Command;

use ApiConsumer\Fetcher\ProcessorService;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Console\ApplicationAwareCommand;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class LinksPreProcessCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'links:preprocess';

    /**
     * @var ProcessorService
     */
    protected $processorService;

    public function __construct(LoggerInterface $logger, ProcessorService $processorService)
    {
        parent::__construct($logger);
        $this->processorService = $processorService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Preprocess urls')
            ->addArgument(
                'urls',
                InputArgument::IS_ARRAY,
                'Urls to preprocess (separate multiple urls with a space)?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urls = $input->getArgument('urls');

        $output->writeln('Preprocessing urls.');


        foreach ($urls as $url) {
            try {
                $preprocessedLink = new PreprocessedLink($url);
                $preprocessedLink->setSource('nekuno');

                $this->processorService->setLogger(new ConsoleLogger($output, array(LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL)));
                $preProcessedLinks = $this->processorService->preProcess(array($preprocessedLink));

                /** @var PreprocessedLink $preProcessedLink */
                foreach ($preProcessedLinks as $preProcessedLink) {
                    $output->writeln($preProcessedLink->getUrl());
                }

            } catch (\Exception $e) {
                $output->writeln(sprintf('Error: %s', $e->getMessage()));
                $output->writeln(sprintf('Error: Link %s not pre processed', $url));
                continue;
            }
        }
    }
}
