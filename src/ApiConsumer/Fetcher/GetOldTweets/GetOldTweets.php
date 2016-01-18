<?php


namespace ApiConsumer\Fetcher\GetOldTweets;


class GetOldTweets
{
    protected $path;

    public function __construct()
    {
        $this->path = __DIR__.'/got.jar';
    }


    public function execute($username, $maxtweets = null, $since=null, $until=null, $querysearch = null)
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

    private function getOutputFilePath()
    {
        return 'output_got.csv';
    }
}