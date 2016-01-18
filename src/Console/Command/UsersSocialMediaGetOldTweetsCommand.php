<?php

namespace Console\Command;


use ApiConsumer\Fetcher\GetOldTweets\GetOldTweets;
use Console\ApplicationAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersSocialMediaGetOldTweetsCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('users:social-media:get-old-tweets')
            ->setDescription('Use the GetOldTweets java library')
            ->addArgument('username', InputArgument::REQUIRED, 'Twitter username (without @)')
            ->addOption('maxtweets', null, InputOption::VALUE_REQUIRED, 'Maximum number of tweets', 5)
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Oldest date limit, format YYYY-MM-DD', null)
            ->addOption('until', null, InputOption::VALUE_REQUIRED, 'Newest date limit, format YYYY-MM-DD', null)
            ->addOption('querysearch', null, InputOption::VALUE_REQUIRED, 'String to search in tweets', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $username = $input->getArgument('username');
        $maxtweets = $input->getOption('maxtweets');
        $since = $input->getOption('since');
        $until = $input->getOption('until');
        $querysearch = $input->getOption('querysearch');
        /** @var GetOldTweets $getoldtweets */
        $getoldtweets = $this->app['get_old_tweets'];

        $getoldtweets->execute($username, $maxtweets, $since, $until, $querysearch);

        $tweets = $getoldtweets->loadCSV();

        $output->writeln(count($tweets).' tweets loaded');

        if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
            foreach($tweets as $position => $tweet)
            {
                $output->writeln('-------------------');
                $output->writeln('Tweet in position '.$position);
                $output->writeln('Text: '.$tweet['text']);
                $output->writeln('Date: '.$tweet['date']);
            }
            $output->writeln('-------------------');
        }

    }

}