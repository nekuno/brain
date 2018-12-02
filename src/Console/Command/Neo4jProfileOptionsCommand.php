<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\LanguageText\LanguageTextManager;
use Model\Neo4j\GraphManager;
use Model\Neo4j\PrivacyOptions;
use Model\Neo4j\ProfileOptions;
use Model\Neo4j\ProfileTags;
use Model\Photo\ProfileOptionGalleryManager;
use Model\Profile\ProfileTagManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jProfileOptionsCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'neo4j:profile-options';

    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @var ProfileTagManager
     */
    protected $profileTagManager;

    /**
     * @var ProfileOptionGalleryManager
     */
    protected $profileOptionGalleryManager;
    /**
     * @var LanguageTextManager
     */
    protected $languageTextManager;

    public function __construct(LoggerInterface $logger, GraphManager $graphManager, ProfileTagManager $profileTagManager, ProfileOptionGalleryManager $profileOptionGalleryManager, LanguageTextManager $languageTextManager)
    {
        parent::__construct($logger);
        $this->graphManager = $graphManager;
        $this->profileTagManager = $profileTagManager;
        $this->languageTextManager = $languageTextManager;
        $this->profileOptionGalleryManager = $profileOptionGalleryManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Load neo4j profile options');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadProfileOptions($output);
        $this->loadPrivacyOptions($output);
        $this->loadProfileTags($output);
    }

    protected function loadProfileOptions(OutputInterface $output)
    {
        $profileOptions = new ProfileOptions($this->graphManager, $this->profileOptionGalleryManager);

        $verbosityLevelMap = array(
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
        );
        $logger = new ConsoleLogger($output, $verbosityLevelMap);
        $profileOptions->setLogger($logger);

        try {
            $result = $profileOptions->load();
        } catch (\Exception $e) {
            $output->writeln(
                'Error loading neo4j profile options with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln(sprintf('%d new profile options processed.', $result->getTotal()));
        $output->writeln(sprintf('%d new profile options updated.', $result->getUpdated()));
        $output->writeln(sprintf('%d new profile options created.', $result->getCreated()));
    }

    protected function loadPrivacyOptions(OutputInterface $output)
    {
        $privacyOptions = new PrivacyOptions($this->graphManager);

        $verbosityLevelMap = array(
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
        );
        $logger = new ConsoleLogger($output, $verbosityLevelMap);
        $privacyOptions->setLogger($logger);

        try {
            $result = $privacyOptions->load();
        } catch (\Exception $e) {
            $output->writeln(
                'Error loading neo4j privacy options with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln(sprintf('%d new privacy options processed.', $result->getTotal()));
        $output->writeln(sprintf('%d new privacy options updated.', $result->getUpdated()));
        $output->writeln(sprintf('%d new privacy options created.', $result->getCreated()));
    }

    protected function loadProfileTags(OutputInterface $output)
    {
        $profileTags = new ProfileTags($this->profileTagManager, $this->languageTextManager);

        $verbosityLevelMap = array(
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
        );
        $logger = new ConsoleLogger($output, $verbosityLevelMap);
        $profileTags->setLogger($logger);

        try {
            $result = $profileTags->load();
        } catch (\Exception $e) {
            $output->writeln(
                'Error loading neo4j privacy options with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln(sprintf('%d profile tags processed.', $result->getTotal()));
        $output->writeln(sprintf('%d profile tag texts updated.', $result->getUpdated()));
        $output->writeln(sprintf('%d profile tags created.', $result->getCreated()));
    }
}