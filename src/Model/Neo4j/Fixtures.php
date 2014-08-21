<?php
/**
 * Created by PhpStorm.
 * User: zaski
 * Date: 8/19/14
 * Time: 12:20 PM
 */

namespace Model\Neo4j;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Exception\QueryErrorException;


class Fixtures {

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

    public function load()
    {
        $this->loadUsers(8);//8 users

        $this->loadContent(19); //Contents are Links. //19 Links
        $this->loadTags(20);//20 tags

        //Content-tag relationships
        $this->createLinkTagRelationship(1, 6);//Link 1 has tag 6
        $this->createLinkTagRelationship(1, 7);

        $this->createLinkTagRelationship(2, 8);
        $this->createLinkTagRelationship(2, 1);

        $this->createLinkTagRelationship(3, 1);
        $this->createLinkTagRelationship(3, 2);
        $this->createLinkTagRelationship(3, 3);

        $this->createLinkTagRelationship(4, 1);
        $this->createLinkTagRelationship(4, 2);
        $this->createLinkTagRelationship(4, 3);
        $this->createLinkTagRelationship(4, 9);

        $this->createLinkTagRelationship(5, 1);
        $this->createLinkTagRelationship(5, 5);

        $this->createLinkTagRelationship(6, 2);

        $this->createLinkTagRelationship(7, 3);
        $this->createLinkTagRelationship(7, 4);

        $this->createLinkTagRelationship(9, 10);

        $this->createLinkTagRelationship(10, 11);
        $this->createLinkTagRelationship(10, 12);

        $this->createLinkTagRelationship(13, 13);
        $this->createLinkTagRelationship(13, 4);

        $this->createLinkTagRelationship(14, 3);
        $this->createLinkTagRelationship(14, 14);

        $this->createLinkTagRelationship(15, 3);

        $this->createLinkTagRelationship(16, 15);
        $this->createLinkTagRelationship(16, 16);
        $this->createLinkTagRelationship(16, 17);

        $this->createLinkTagRelationship(18, 18);
        $this->createLinkTagRelationship(18, 19);
        $this->createLinkTagRelationship(18, 20);

        //User-content relationships
        $this->createUserLikesLinkRelationship(1, 1);
        $this->createUserLikesLinkRelationship(1, 2);
        $this->createUserLikesLinkRelationship(1, 3);

        $this->createUserLikesLinkRelationship(2, 1);
        $this->createUserLikesLinkRelationship(2, 2);
        $this->createUserLikesLinkRelationship(2, 3);

        $this->createUserDislikesLinkRelationship(3, 4);
        $this->createUserDislikesLinkRelationship(3, 5);
        $this->createUserDislikesLinkRelationship(3, 6);

        $this->createUserDislikesLinkRelationship(4, 4);
        $this->createUserDislikesLinkRelationship(4, 5);
        $this->createUserDislikesLinkRelationship(4, 6);

        $this->createUserLikesLinkRelationship(5, 1);
        $this->createUserLikesLinkRelationship(5, 7);
        $this->createUserLikesLinkRelationship(5, 8);
        $this->createUserLikesLinkRelationship(5, 9);
        $this->createUserLikesLinkRelationship(5, 10);
        $this->createUserLikesLinkRelationship(5, 11);
        $this->createUserLikesLinkRelationship(5, 12);

        $this->createUserDislikesLinkRelationship(6, 13);
        $this->createUserDislikesLinkRelationship(6, 14);
        $this->createUserLikesLinkRelationship(6, 15);
        $this->createUserLikesLinkRelationship(6, 16);
        $this->createUserLikesLinkRelationship(6, 17);
        $this->createUserDislikesLinkRelationship(6, 18);
        $this->createUserDislikesLinkRelationship(6, 19);
        $this->createUserDislikesLinkRelationship(6, 11);
        $this->createUserLikesLinkRelationship(6, 12);

        //Questions
        $this->loadQuestionsWithAnswers(4, 3); //4 questions, 3 answers each

        //User-Answer-Question relationships
        $this->createUserRatesRelationship(3, 1, 0);//User 3 rates question 1 with a 0
        $this->createUserAnswersRelationship(3, 12);//Answer 2 of question 1
        $this->createUserAcceptsRelationship(3, 11);//Answer 1 of question 1
        $this->createUserAcceptsRelationship(3, 12);
        $this->createUserAcceptsRelationship(3, 13);

        $this->createUserRatesRelationship(3, 4, 0);
        $this->createUserAnswersRelationship(3, 41);
        $this->createUserAcceptsRelationship(3, 41);
        $this->createUserAcceptsRelationship(3, 42);

        $this->createUserRatesRelationship(4, 1, 1);
        $this->createUserAnswersRelationship(4, 11);
        $this->createUserAcceptsRelationship(4, 11);

        $this->createUserRatesRelationship(4, 2, 10);
        $this->createUserAnswersRelationship(4, 21);
        $this->createUserAcceptsRelationship(4, 22);

        $this->createUserRatesRelationship(5, 2, 10);
        $this->createUserAnswersRelationship(5, 21);
        $this->createUserAcceptsRelationship(5, 21);

        $this->createUserRatesRelationship(5, 4, 10);
        $this->createUserAnswersRelationship(5, 42);
        $this->createUserAcceptsRelationship(5, 42);

        $this->createUserRatesRelationship(6, 2, 1);
        $this->createUserAnswersRelationship(6, 21);
        $this->createUserAcceptsRelationship(6, 22);

        $this->createUserRatesRelationship(6, 3, 50);
        $this->createUserAnswersRelationship(6, 32);
        $this->createUserAcceptsRelationship(6, 32);
        $this->createUserAcceptsRelationship(6, 33);

        $this->createUserRatesRelationship(7, 2, 50);
        $this->createUserAnswersRelationship(7, 22);
        $this->createUserAcceptsRelationship(7, 21);

        $this->createUserRatesRelationship(7, 3, 50);
        $this->createUserAnswersRelationship(7, 32);
        $this->createUserAcceptsRelationship(7, 31);
        $this->createUserAcceptsRelationship(7, 32);

        $this->createUserRatesRelationship(7, 4, 1);
        $this->createUserAnswersRelationship(7, 42);
        $this->createUserAcceptsRelationship(7, 42);
    }

    public function loadUsers($numberOfUsers)
    {

        //Create queries in loop
        $userQuery = array();
        for ($i = 1; $i<=$numberOfUsers; $i++)
        {
            $userQuery[] =
            "CREATE (u:User {
                status: 'active',
                qnoow_id: " . $i . ",
                username: 'user" . $i . "',
                email: 'testuser" . $i . "@test.test'
            })
            RETURN u;";
        }

        //Execute queries in loop
        foreach ($userQuery as $query) {
            $neo4jQuery = new Query(
                $this->client,
                $query
            );

            try {
                $result = $neo4jQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;

                return;
            }
        }

        return;

    }

    public function loadContent($numberOfContents)
    {

        //Create queries in loop
        $contentQuery = array();
        for ($i = 1; $i<=$numberOfContents; $i++)
        {
            $contentQuery[] =
            "CREATE (l:Content:Link {
                url: 'testLink" . $i . "',
                description: 'test description " . $i . "',
                processed: 0
            })
            RETURN l;";
        }

        //Execute queries in loop
        foreach ($contentQuery as $query) {
            $neo4jQuery = new Query(
                $this->client,
                $query
            );

            try {
                $result = $neo4jQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;

                return;
            }
        }

        return;

    }

    public function loadTags($numberOfTags)
    {

        //Create queries in loop
        $tagQuery = array();
        for ($i = 1; $i<=$numberOfTags; $i++)
        {
            $tagQuery[] =
            "CREATE (t:Tag {
                name: 'testTag" . $i . "'
            })
            RETURN t;";
        }

        //Execute queries in loop
        foreach ($tagQuery as $query) {
            $neo4jQuery = new Query(
                $this->client,
                $query
            );

            try {
                $result = $neo4jQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;

                return;
            }
        }

        return;

    }

    public function createLinkTagRelationship($link, $tag)
    {
        $relationshipQuery =
        "MATCH
            (l:Link {url: 'testLink" . $link . "'}),
            (t:Tag {name: 'testTag" . $tag . "'})
        CREATE
            (l)-[r:TAGGED]->(t)
        RETURN
            l, r, t
        ;";

        $neo4jQuery = new Query(
            $this->client,
            $relationshipQuery
        );

        try {
            $result = $neo4jQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;

            return;
        }

        return;

    }

    public function createUserLikesLinkRelationship($user, $link)
    {
        $relationshipQuery =
        "MATCH
            (l:Link {url: 'testLink" . $link . "'}),
            (u:User {qnoow_id: ". $user . "})
        CREATE
            (l)<-[r:LIKES]-(u)
        RETURN
            l, r, u
        ;";

        $neo4jQuery = new Query(
            $this->client,
            $relationshipQuery
        );

        try {
            $result = $neo4jQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;

            return;
        }

        return;
    }

    public function createUserDislikesLinkRelationship($user, $link)
    {
        $relationshipQuery =
        "MATCH
            (l:Link {url: 'testLink" . $link . "'}),
            (u:User {qnoow_id: ". $user . "})
        CREATE
            (l)<-[r:DISLIKES]-(u)
        RETURN
            l, r, u
        ;";

        $neo4jQuery = new Query(
            $this->client,
            $relationshipQuery
        );

        try {
            $result = $neo4jQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;

            return;
        }

        return;
    }

    public function loadQuestionsWithAnswers($numberOfQuestions, $numberOfAnswersPerQuestion)
    {
        //Create queries in loop
        $questionsQuery = array();
        for ($i = 1; $i<=$numberOfQuestions; $i++)
        {
            $questionsQueryString =
            "CREATE
                (q:Question {qnoow_id: " . $i . ", text: 'question " . $i . "'})
            ";

            for($j = 1; $j<=$numberOfAnswersPerQuestion; $j++)
            {
                $questionsQueryString .=
                ", (:Answer {qnoow_id: " . $i . $j . ", text: 'answer " . $i . "-" . $j . "'})
                -[:IS_ANSWER_OF]->(q)";
            }

            $questionsQueryString .= " RETURN q;";

            $questionsQuery[] = $questionsQueryString;
        }

        //Execute queries in loop
        foreach ($questionsQuery as $query) {
            $neo4jQuery = new Query(
                $this->client,
                $query
            );

            try {
                $result = $neo4jQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;

                return;
            }
        }

        return;

    }

    public function createUserRatesRelationship($user, $question, $rating)
    {
        $relationshipQuery =
        "MATCH
            (q:Question {qnoow_id: " . $question . "}),
            (u:User {qnoow_id: ". $user . "})
        CREATE
            (u)-[r:RATES {rating: " . $rating . "}]->(q)
        RETURN
            u, r, q
        ;";

        $neo4jQuery = new Query(
            $this->client,
            $relationshipQuery
        );

        try {
            $result = $neo4jQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;

            return;
        }

        return;
    }

    public function createUserAnswersRelationship($user, $answer)
    {
        $relationshipQuery =
        "MATCH
            (a:Answer {qnoow_id: " . $answer . "}),
            (u:User {qnoow_id: ". $user . "})
        CREATE
            (u)-[r:ANSWERS]->(a)
        RETURN
            u, r, a
        ;";

        $neo4jQuery = new Query(
            $this->client,
            $relationshipQuery
        );

        try {
            $result = $neo4jQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;

            return;
        }

        return;
    }

    public function createUserAcceptsRelationship($user, $answer)
    {
        $relationshipQuery =
        "MATCH
            (a:Answer {qnoow_id: " . $answer . "}),
            (u:User {qnoow_id: ". $user . "})
        CREATE
            (u)-[r:ACCEPTS]->(a)
        RETURN
            u, r, a
        ;";

        $neo4jQuery = new Query(
            $this->client,
            $relationshipQuery
        );

        try {
            $result = $neo4jQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;

            return;
        }

        return;
    }

}