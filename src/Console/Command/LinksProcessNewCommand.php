<?php

namespace Console\Command;

use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Console\ApplicationAwareCommand;
use Model\LinkModel;
use Model\User\RateModel;
use Model\User\TokensModel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LinksProcessNewCommand extends ApplicationAwareCommand
{

    protected function configure()
    {

        $this->setName('links:process:new')
            ->setDescription('Process new links into the database')
            ->addArgument('url', InputArgument::OPTIONAL, 'Url to be processed', null)
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'User to like the link', null)
            ->addOption('resource', null, InputOption::VALUE_REQUIRED, 'Resource from which it was fetched', null)
            ->addOption('csv', null, InputOption::VALUE_REQUIRED, 'Process urls from a CSV file', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /* @var $linksModel LinkModel */
        $linksModel = $this->app['links.model'];
        /* @var $rateModel RateModel */
        $rateModel = $this->app['users.rate.model'];

        $url = $input->getArgument('url');
        $csv = $input->getOption('csv');
        $userId = $input->getOption('userId');
        $resource = $input->getOption('resource');

        if (!$csv && !$url) {
            $output->writeln('Please insert an URL to be processed or select csv file to read from');
        }

        if ($csv && $url) {
            $output->writeln('Please choose only one option between reading from CSV and manually input URL');
        }

        if ($csv) {
            //get links from csv
            $urls = array();
        } else {
            $urls = array($url);
        }

        $output->writeln('Got ' . count($urls) . ' urls to process');

        foreach ($urls as $url) {

            try {

                $preprocessedLink = new PreprocessedLink($url);
                $preprocessedLink->setUrl($url);
                $preprocessedLink->setSource($resource);

                if ($userId && $resource) {
                    /* @var TokensModel $tokensModel */
                    $tokensModel = $this->app['users.tokens.model'];
                    $tokens = $tokensModel->getByUserOrResource($userId, $resource);
                    if (count($tokens) !== 0) {
                        $preprocessedLink->setToken($tokens[0]);
                    }
                }
                /* @var LinkProcessor $processor */
                $processor = $this->app['api_consumer.link_processor'];
                $processedLink = $processor->process($preprocessedLink);

                if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
                    $output->writeln('----------Link outputted------------');
                    $output->writeln('Type: ' . get_class($processedLink));
                    foreach ($processedLink->toArray() as $key => $value) {
                        $value = is_array($value) ? json_encode($value) : $value;
                        $output->writeln(sprintf('%s => %s', $key, $value));
                    }
                    $output->writeln('-----------------------------------');
                }

                $processed = array_key_exists('processed', $processedLink) ? $processedLink['processed'] : 1;
                if ($processed) {
                    $output->writeln(sprintf('Success: Link %s processed', $preprocessedLink->getUrl()));
                } else {
                    $output->writeln(sprintf('Failed request: Link %s not processed', $preprocessedLink->getUrl()));
                }

            } catch (\Exception $e) {
                $output->writeln(sprintf('Error: %s', $e->getMessage()));
                $output->writeln(sprintf('Error: Link %s not processed', $url));
                continue;
            }

            if (!$userId) {
                $output->writeln(sprintf('Link with url %s was not saved in the database', $processedLink->getUrl()));
            } else {
                try {
                    $addedLink = $linksModel->addOrUpdateLink($processedLink);
                    $processedLink->setId($addedLink['id']);
                    $rateModel->userRateLink($userId, $processedLink->getId());
                    foreach ($processedLink->getTags() as $tag) {
                        $linksModel->createTag($tag);
                        $linksModel->addTag($processedLink, $tag);
                    }

                    $output->writeln(sprintf('Success: Link %s saved', $processedLink->getUrl()));

                } catch (\Exception $e) {
                    $output->writeln(sprintf('Error: Link %s not saved', $processedLink->getUrl()));
                    $output->writeln($e->getMessage());
                }
            }

        }
    }
}
