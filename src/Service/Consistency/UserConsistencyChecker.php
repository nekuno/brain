<?php

namespace Service\Consistency;


class UserConsistencyChecker extends ConsistencyChecker
{
    public function checkNode(ConsistencyNodeData $nodeData, ConsistencyNodeRule $rule)
    {
        //TODO: Change this by using "RealUser" label

        $labels = $nodeData->getLabels();
        if (in_array('GhostUser', $labels)) {
            //TODO: Add relationships to this rule (similarity with user, belongs_to group)
            $qnoow_idRule = $rule->getProperties()['qnoow_id'];
            $nodeRule = new ConsistencyNodeRule(array('properties' => array('qnoow_id' => $qnoow_idRule)));
        } else {
            $nodeRule = $rule;
        }

        parent::checkNode($nodeData, $nodeRule);
    }

}