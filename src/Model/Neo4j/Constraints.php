<?php

namespace Model\Neo4j;


class Constraints
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @param GraphManager $gm
     */
    public function __construct(GraphManager $gm)
    {

        $this->gm = $gm;
    }

    //TODO: Update to add indexes
    /**
     * Load the constraints
     *
     * @throws Neo4jException
     */
    public function load()
    {

        $constraints = array(
            'CREATE INDEX ON :Link(url)',
            'CREATE INDEX ON :Link(popularity)',
            'CREATE INDEX ON :Popularity(popularity)',
            'CREATE INDEX ON :Tag(name)',
            'CREATE CONSTRAINT ON (ans:Answer) ASSERT ans.qnoow_id IS UNIQUE',
            'CREATE CONSTRAINT ON (que:Question) ASSERT que.qnoow_id IS UNIQUE',
            'CREATE CONSTRAINT ON (inv:Invitation) ASSERT inv.token IS UNIQUE',
        );

        $fields = array('qnoow_id', 'usernameCanonical', 'slug');
        foreach ($fields as $field) {
            $constraints[] = "CREATE CONSTRAINT ON (u:User) ASSERT u.$field IS UNIQUE";
        }

        foreach ($constraints as $query) {
            $this->gm->createQuery($query)->getResultSet();
        }
    }
}