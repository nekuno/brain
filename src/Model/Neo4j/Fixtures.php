<?php
/**
 * Created by PhpStorm.
 * User: zaski
 * Date: 8/19/14
 * Time: 12:20 PM
 */

namespace Model\Neo4j;


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
        $this->loadUsers(8);
        $this->loadContent(19); //Contents are Links
        $this->loadTags(20);

        //$this->loadQuestions();

        //Content-tag relationships
        $this->createLinkTagRelationship(1, 6);
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
        $this->createUserDisikesLinkRelationship(3, 6);
        $this->createUserDislikesLinkRelationship(4, 4);
        $this->createUserDislikesLinkRelationship(4, 5);
        $this->createUserDisikesLinkRelationship(4, 6);
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


        //$this->createUserQuestionRelationships();

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
            (l:Link {url: 'testLink " . $link . "'}),
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
            (l:Link {url: 'testLink " . $link . "'}),
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

    public function createUserDisikesLinkRelationship($user, $link)
    {
        $relationshipQuery =
            "MATCH
                (l:Link {url: 'testLink " . $link . "'}),
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

}