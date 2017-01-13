<?php

namespace Service\Consistency;


use Everyman\Neo4j\Node;

class UserConsistencyChecker extends ConsistencyChecker
{
    public function check(Node $node, ConsistencyNodeRule $userRule)
    {
        //TODO: Change this by using "RealUser" label

        if (in_array('GhostUser', ConsistencyCheckerService::getLabelNames($node))) {
            //TODO: Add relationships to this rule (similarity with user, belongs_to group)
            $qnoow_idRule = $userRule->getProperties()['qnoow_id'];
            $nodeRule = new ConsistencyNodeRule(array('properties' => array('qnoow_id' => $qnoow_idRule)));
        } else {
            $nodeRule = $userRule;
        }

        parent::check($node, $nodeRule);
    }

}