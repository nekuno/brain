<?php

namespace Model\User\Matching;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class NormalDistributionModel
{
    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    protected $dataDir;

    /**
     * @param \Everyman\Neo4j\Client $client
     * @param string $dataDir
     */
    public function __construct(Client $client, $dataDir)
    {
        $this->client = $client;
        $this->dataDir = $dataDir;
    }

    protected function getDataDirectory()
    {
        $dataDirectory = $this->dataDir . "/matchingData";
        if(!file_exists($dataDirectory) && !is_dir($dataDirectory)){
            mkdir($dataDirectory);
        }

        return $dataDirectory;
    }

    protected function saveDataFile($fileName, $data)
    {
        $dataFile = $this->getDataDirectory() . "/" . $fileName . ".json";
        $logFile  = $this->getDataDirectory() . "/" .  $fileName . ".log";

        if(file_exists($dataFile)){
            file_put_contents($logFile, file_get_contents($dataFile)."\n", FILE_APPEND );
        }
        file_put_contents($dataFile, json_encode($data) );
    }

    protected function readDataFile($fileName)
    {
        $dataFile = $this->getDataDirectory() . "/" . $fileName . ".json";

        $data = array();
        if(file_exists($dataFile)){
            $data = json_decode(file_get_contents($dataFile, true) );
        }

        return $data;
    }

    public function updateContentNormalDistributionVariables()
    {
        //Construct query string
        $queryString = "
            MATCH
            a=(u1:User)-[rl1]->(cl1:Link)-[:TAGGED]->(tl1:Tag)
            MATCH
            b=(u2:User)-[rl2]->(cl2:Link)-[:TAGGED]->(tl1)
            WHERE
            u1<>u2 AND
            type(rl1) = type(rl2)
            WITH
            u1, u2, length(collect(DISTINCT cl2)) AS numOfContentsInCommon
            RETURN
            avg(numOfContentsInCommon) AS ave_content,
            stdevp(numOfContentsInCommon) AS stdev_content
        ";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $queryString
        );

        //Execute Query
        try {
            $result = $query->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        //Get the wanted results
        foreach ($result as $row) {
            $average = $row['ave_content'];
            $stdev = $row['stdev_content'];
        }

        if ($average == null) {
            $average = 0;
        }
        if ($stdev == null){
            $stdev = 0;
        }

        //Persist data

        $date = date("d-m-Y [H:i:s]");
        $data = array("average" => $average, "stdev" => $stdev, "calculationDate" => $date );

        $this->saveDataFile("contentNormalDistributionVariables", $data);

        return $data;
    }

    public function getContentNormalDistributionVariables()
    {
        return $this->readDataFile("contentNormalDistributionVariables");
    }

} 