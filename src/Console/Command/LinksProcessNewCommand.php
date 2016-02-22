<?php

namespace Console\Command;

use ApiConsumer\LinkProcessor\LinkProcessor;
use Console\ApplicationAwareCommand;
use Model\LinkModel;
use Model\User\RateModel;
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

                $link = array('url' => $url);

                /* @var LinkProcessor $processor */
                $processor = $this->app['api_consumer.link_processor'];
                $processedLink = $processor->process($link);

                $processed = array_key_exists('processed', $processedLink) ? $processedLink['processed'] : 1;
                if ($processed) {
                    $output->writeln(sprintf('Success: Link %s processed', $link['url']));
                } else {
                    $output->writeln(sprintf('Failed request: Link %s not processed', $link['url']));
                }

            } catch (\Exception $e) {
                $output->writeln(sprintf('Error: %s', $e->getMessage()));
                $output->writeln(sprintf('Error: Link %s not processed', $url));
                continue;
            }

            if (!$userId) {
                $output->writeln(sprintf('Link with url %s was not saved in the database', $processedLink['url']));
            } else {
                try {
                    $addedLink = $linksModel->addLink($processedLink);
                    $rateModel->userRateLink($userId, $addedLink, RateModel::LIKE);

                    if (isset($processedLink['tags'])) {
                        foreach ($processedLink['tags'] as $tag) {
                            $linksModel->createTag($tag);
                            $linksModel->addTag($processedLink, $tag);
                        }
                    }

                    $output->writeln(sprintf('Success: Link %s saved', $processedLink['url']));

                } catch (\Exception $e) {
                    $output->writeln(sprintf('Error: Link %s not saved', $processedLink['url']));
                    $output->writeln($e->getMessage());
                }
            }

        }
    }
}
