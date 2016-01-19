<?php


namespace ApiConsumer\Fetcher\GetOldTweets;


use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;

class GetOldTweets
{
    protected $path;
    protected $parser;

    public function __construct(TwitterUrlParser $parser)
    {
        $this->path = __DIR__.'/got.jar';
        $this->parser = $parser;
    }


    public function execute($username, $maxtweets = 1000, $since=null, $until=null, $querysearch = null)
    {
        $options = [];

        if ($username){
            $options[] = ' username='.$username;
        }
        if ($maxtweets)
        {
            $options[] = ' maxtweets='.$maxtweets;
        }
        if ($since)
        {
            $options[] = ' since='.$since;
        }
        if ($until)
        {
            $options[] = ' until='.$until;
        }
        if ($querysearch)
        {
            $options[] = ' querysearch="'.$querysearch.'"';
        }

        exec('java -jar '.$this->path.implode(' ', $options));

    }

    public function loadCSV()
    {
        $tweets = array();
        $first = true;

        if (($handle = fopen($this->getOutputFilePath(), 'r')) !== false) {

            while (($data = fgetcsv($handle, 0, ';')) !== false) {

                if ($first) {
                    $first = false;
                    continue;
                }

                $tweets[] = array('text' => $data[4], 'date' => $data[1]);

            }
            fclose($handle);
        }

        return $tweets;
    }

    public function getURLsFromTweets(array $tweets)
    {
        $urls = array();
        foreach ($tweets as $tweet)
        {
            $urls = array_merge($urls, $this->parser->extractURLsFromText($tweet['text']));
        }
        return $urls;
    }

    private function getOutputFilePath()
    {
        return 'output_got.csv';
    }
}