<?php

namespace Service\Consistency;


use Everyman\Neo4j\Node;
use Everyman\Neo4j\PropertyContainer;
use Everyman\Neo4j\Relationship;
use Model\Exception\ValidationException;

class ConsistencyChecker
{
    public function check(Node $node, ConsistencyNodeRule $userRule) {

        $this->checkNodeRelationships($node, $userRule->getRelationships());
        $this->checkProperties($node, $userRule->getProperties());
    }

    /**
     * @param Node $node
     * @param $relationshipRules
     */
    private function checkNodeRelationships(Node $node, $relationshipRules)
    {
        $totalRelationships = $node->getRelationships();

        foreach ($relationshipRules as $relationshipRule) {
            $rule = new ConsistencyRelationshipRule($relationshipRule);

            /** @var Relationship[] $relationships */
            $relationships = array_filter($totalRelationships, function ($relationship) use ($rule) {
                /** @var $relationship Relationship */
                return $relationship->getType() === $rule->getType();
            });

            $errors = array('relationships' => array());

            if (count($relationships) < $rule->getMinimum()) {
                $errors['relationships'][$rule->getType()] = sprintf('Amount of relationships %d is less than %d allowed for node with id %d', count($relationships), $rule->getMinimum(), $node->getId());
            }

            if (count($relationships) > $rule->getMaximum()) {
                $errors['relationships'][$rule->getType()] = sprintf('Amount of relationships %d is more than %d allowed for node with id %d', count($relationships), $rule->getMaximum(), $node->getId());
            }

            foreach ($relationships as $relationship) {
                $startNode = $relationship->getStartNode();
                $endNode = $relationship->getEndNode();
                $otherNode = $startNode->getId() !== $node->getId() ? $startNode : $endNode;


                if ($rule->getDirection() == 'incoming' && $endNode->getId() != $node->getId()
                    || $rule->getDirection() == 'outgoing' && $startNode->getId() != $node->getId()
                ) {
                    $errors['relationships'][] = sprintf('Direction of relationship %d is not correct', $relationship->getId());
                }

                if (!in_array($rule->getOtherNode(), ConsistencyCheckerService::getLabelNames($otherNode))) {
                    $errors['relationships'][] = sprintf('Label of destination node for relationship %d is not correct', $relationship->getId());
                }

                $this->checkProperties($relationship, $rule->getProperties());
            }

            if (!empty($errors['relationships'])) {
                throw new ValidationException($errors, 'Node relationships consistency error for node ' . $node->getId());
            }
        }
    }

    private function checkProperties(PropertyContainer $propertyContainer, array $propertyRules)
    {
        $properties = $propertyContainer->getProperties();

        foreach ($propertyRules as $name => $propertyRule) {

            $errors = array('properties' => array());
            $rule = new ConsistencyPropertyRule($name, $propertyRule);

            if (!isset($properties[$name])) {
                if (!$rule->isRequired()) {
                    continue;
                }
                $errors['properties'][$name] = sprintf('Element with id $d does not have property %s', $propertyContainer->getId(), $name);
            } else {
                $value = $properties[$name];

                $options = $rule->getOptions();
                if (!empty($options)) {
                    if (!in_array($value, $options)) {
                        $errors['properties'][$name] = sprintf('Element with id %d has property %s with invalid value %s', $propertyContainer->getId(), $name, $value);
                    }
                }

                switch ($rule->getType()) {
                    case null:
                        break;
                    case ConsistencyPropertyRule::TYPE_INTEGER:
                        if (!is_int($value)) {
                            $errors['properties'][$name] = sprintf('Element with id %d has property %s with value %s which should be an integer', $propertyContainer->getId(), $name, $value);
                        } else {
                            if ($rule->getMaximum() && $value > $rule->getMaximum()) {
                                $errors['properties'][$name] = sprintf('Element with id %d has property %d greater than maximum %d', $propertyContainer->getId(), $name, $value, $rule->getMaximum());
                            }
                            if ($rule->getMinimum() && $value < $rule->getMinimum()) {
                                $errors['properties'][$name] = sprintf('Element with id %d has property %d lower than minimum %d', $propertyContainer->getId(), $name, $value, $rule->getMinimum());
                            }
                        }
                        break;
                    case ConsistencyPropertyRule::TYPE_BOOLEAN:
                        if (!is_bool($value)) {
                            $errors['properties'][$name] = sprintf('Element with id %d has property %s with value %s which should be a boolean', $propertyContainer->getId(), $name, $value);
                        };
                        break;
                    case ConsistencyPropertyRule::TYPE_ARRAY:
                        if (!is_array($value)) {
                            $errors['properties'][$name] = sprintf('Element with id %d has property %s with value %s which should be an array', $propertyContainer->getId(), $name, $value);
                        };
                        break;
                    case ConsistencyPropertyRule::TYPE_DATETIME:
                        $date = new \DateTime($value);

                        if ($rule->getMaximum() && $date > new \DateTime($rule->getMaximum())) {
                            $errors['properties'][$name] = sprintf('Element with id %d has property %s later than maximum %s', $propertyContainer->getId(), $name, $rule->getMaximum());
                        }
                        if ($rule->getMinimum() && $value < $rule->getMinimum()) {
                            $errors['properties'][$name] = sprintf('Element with id %d has property %s earlier than minimum %s', $propertyContainer->getId(), $name, $rule->getMinimum());
                        }
                        break;
                    default:
                        break;
                }
            }

            if (!empty($errors['properties'])) {
                throw new ValidationException($errors, 'Properties consistency error for element ' . $propertyContainer->getId());
            }
        }
    }
}