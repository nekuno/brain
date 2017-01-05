<?php

namespace Console\Command;

use ApiConsumer\Fetcher\ProcessorService;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Console\ApplicationAwareCommand;
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
                $preprocessedLink->setSource($resource);

                if ($userId && $resource) {
                    $token = $this->getToken($userId, $resource);
                    $preprocessedLink->setToken($token);
                }

                $testUserId = 42;
                $userToProcess = $userId ?: $testUserId;

                /* @var ProcessorService $processor */
                $processor = $this->app['api_consumer.processor'];
                $processedLinks = $processor->process(array($preprocessedLink), $userToProcess);

                foreach ($processedLinks as $processedLink) {
                    $this->outputLink($processedLink, $output);
                }

            } catch (\Exception $e) {
                $output->writeln(sprintf('Error: %s', $e->getMessage()));
                $output->writeln(sprintf('Error: Link %s not processed', $url));
                continue;
            }
        }
    }

    private function getToken($userId, $resource)
    {
        $token = array();

        /* @var TokensModel $tokensModel */
        $tokensModel = $this->app['users.tokens.model'];
        $tokens = $tokensModel->getByUserOrResource($userId, $resource);
        if (count($tokens) !== 0) {
            $token = $tokens[0];
        }

        return $token;
    }

    private function outputLink($link, OutputInterface $output)
    {
        if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
            $output->writeln('----------Link outputted------------');
            $output->writeln('Type: ' . get_class($link));
            foreach ($link as $key => $value) {
                $value = is_array($value) ? json_encode($value) : $value;
                $output->writeln(sprintf('%s => %s', $key, $value));
            }
            $output->writeln('-----------------------------------');
        }
    }
}
