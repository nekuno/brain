<?php

namespace Model\Neo4j;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class ProfileOptions
{
    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @param \Everyman\Neo4j\Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * @throws /Exception
     */
    public function load()
    {

        $this->createOption('Alcohol', 'Yes');
        $this->createOption('Alcohol', 'No');
        $this->createOption('Alcohol', 'Occasionally');
        $this->createOption('Alcohol', 'Socially/On parties');

        $this->createOption('CivilStatus', 'Single');
        $this->createOption('CivilStatus', 'Married');
        $this->createOption('CivilStatus', 'Open relationship');
        $this->createOption('CivilStatus', 'Dating someone');

        $this->createOption('Complexion', 'Slim');
        $this->createOption('Complexion', 'Normal');
        $this->createOption('Complexion', 'Fat');

        $this->createOption('DateAlcohol', 'Yes');
        $this->createOption('DateAlcohol', 'No');
        $this->createOption('DateAlcohol', 'Maybe');

        $this->createOption('DateChildren', 'Yes');
        $this->createOption('DateChildren', 'No');
        $this->createOption('DateChildren', 'Maybe');

        $this->createOption('DateComplexion', 'Yes');
        $this->createOption('DateComplexion', 'No');
        $this->createOption('DateComplexion', 'Maybe');

        $this->createOption('DateHandicap', 'Yes');
        $this->createOption('DateHandicap', 'No');
        $this->createOption('DateHandicap', 'Maybe');

        $this->createOption('DateReligion', 'Yes');
        $this->createOption('DateReligion', 'No');
        $this->createOption('DateReligion', 'Maybe');

        $this->createOption('DateSmoker', 'Yes');
        $this->createOption('DateSmoker', 'No');
        $this->createOption('DateSmoker', 'Maybe');

        $this->createOption('Diet', 'Vegetarian');
        $this->createOption('Diet', 'Vegan');
        $this->createOption('Diet', 'Other');

        $this->createOption('Drugs', 'Yes');
        $this->createOption('Drugs', 'No');
        $this->createOption('Drugs', 'Occasionally');
        $this->createOption('Drugs', 'Socially/On parties');

        $this->createOption('EthnicGroup', 'Oriental');
        $this->createOption('EthnicGroup', 'Afro-American');
        $this->createOption('EthnicGroup', 'Caucasian');

        $this->createOption('EyeColor', 'Blue');
        $this->createOption('EyeColor', 'Brown');
        $this->createOption('EyeColor', 'Black');
        $this->createOption('EyeColor', 'Green');

        $this->createOption('Gender', 'Male');
        $this->createOption('Gender', 'Female');
        $this->createOption('Gender', 'Other');

        $this->createOption('HairColor', 'Black');
        $this->createOption('HairColor', 'Brown');
        $this->createOption('HairColor', 'Blond');
        $this->createOption('HairColor', 'Red');
        $this->createOption('HairColor', 'Gray or White');
        $this->createOption('HairColor', 'Other');

        $this->createOption('Handicap', 'Physical');
        $this->createOption('Handicap', 'Cognitive');
        $this->createOption('Handicap', 'Mental');
        $this->createOption('Handicap', 'Sensory');
        $this->createOption('Handicap', 'Emotional');
        $this->createOption('Handicap', 'Developmental');

        $this->createOption('Income', 'Less than US$12,000/year');
        $this->createOption('Income', 'Between US$12,000 and US$24,000/year');
        $this->createOption('Income', 'More than US$24,000/year');

        $this->createOption('Nationality', 'US');
        $this->createOption('Nationality', 'British');
        $this->createOption('Nationality', 'Spanish');

        $this->createOption('Orientation', 'Heterosexual');
        $this->createOption('Orientation', 'Homosexual');
        $this->createOption('Orientation', 'Bisexual');

        $this->createOption('Pets', 'Cat');
        $this->createOption('Pets', 'Dog');
        $this->createOption('Pets', 'Other');

        $this->createOption('RelationshipInterest', 'Friendship');
        $this->createOption('RelationshipInterest', 'Relation');
        $this->createOption('RelationshipInterest', 'Open Relation');

        $this->createOption('Smoke', 'Yes');
        $this->createOption('Smoke', 'No, but I tolerate it');
        $this->createOption('Smoke', 'No, and I hate it');

        return;
    }

    /**
     * @param $type
     * @param $name
     * @throws /Exception
     */
    public function createOption($type, $name)
    {
        if (!$this->optionExists($type, $name)) {
            $params = array('name' => $name);
            $query = "CREATE (:ProfileOption:".$type." { name: {name} })";

            $neo4jQuery = new Query(
                $this->client,
                $query,
                $params
            );

            $result = $neo4jQuery->getResultSet();
        }

        return;
    }

    /**
     * @param $type
     * @param $name
     * @return boolean
     * @throws /Exception
     */
    public function optionExists($type, $name)
    {
        $params = array('type' => $type, 'name' => $name);
        $query =
            "
            MATCH
            (o:ProfileOption)
            WHERE
            {type} IN labels(o) AND
            o.name = {name}
            RETURN
            o;";

        $neo4jQuery = new Query(
            $this->client,
            $query,
            $params
        );

        $result = $neo4jQuery->getResultSet();

        return count($result) > 0;
    }
} 