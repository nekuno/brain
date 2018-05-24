<?php

namespace Service\Consistency;

use Model\Exception\ErrorList;
use Model\Exception\ValidationException;
use Service\Consistency\ConsistencyErrors\ConsistencyError;
use Service\Consistency\ConsistencyErrors\MissingPropertyConsistencyError;
use Service\Consistency\ConsistencyErrors\RelationshipAmountConsistencyError;
use Service\Consistency\ConsistencyErrors\RelationshipMultipleSimilarConsistencyError;
use Service\Consistency\ConsistencyErrors\RelationshipOtherLabelConsistencyError;
use Service\Consistency\ConsistencyErrors\ReverseRelationshipConsistencyError;

class ConsistencyChecker
{
    public function checkNode(ConsistencyNodeData $nodeData, ConsistencyNodeRule $rule)
    {
        $this->checkNodeRelationships($nodeData, $rule);
        $this->checkProperties($nodeData->getProperties(), $nodeData->getId(), $rule->getProperties());
    }

    /**
     * @param ConsistencyNodeData $nodeData
     * @param ConsistencyNodeRule $rule
     * @internal param array $totalRelationships
     * @internal param $nodeId
     */
    protected function checkNodeRelationships(ConsistencyNodeData $nodeData, ConsistencyNodeRule $rule)
    {
        $relationshipRules = $rule->getRelationships();
        $nodeId = $nodeData->getId();
        $errors = new ErrorList();

        foreach ($relationshipRules as $relationshipRule) {
            $rule = new ConsistencyRelationshipRule($relationshipRule);

            list($incoming, $outgoing) = $this->chooseRelationshipsByType($nodeData, $rule);
            $type = $rule->getType();

            $errorsLabelAmount = $this->analyzeLabelAmount($incoming, $outgoing);
            $errors->addErrors($type, $errorsLabelAmount);

            $errorsReverseDirection = $this->analyzeDirection($rule, $incoming, $outgoing);
            $errors->addErrors($type, $errorsReverseDirection);

            $errorsOtherNodeLabel = $this->analyzeOtherNodeLabels($rule, $incoming, $outgoing);
            $errors->addErrors($type, $errorsOtherNodeLabel);

            /** @var ConsistencyRelationshipData[] $totalRelationships */
            $totalRelationships = array_merge($incoming + $outgoing);

            $errorRelationshipsAmount = $this->analyzeRelationshipAmount($rule, $totalRelationships);
            $errors->addError($type, $errorRelationshipsAmount);

            foreach ($totalRelationships as $relationship) {
                $this->checkProperties($relationship->getProperties(), $relationship->getId(), $rule->getProperties());
            }
        }

        $this->throwErrors($errors, $nodeId);
    }

    protected function checkProperties(array $properties, $id, array $propertyRules)
    {
        foreach ($propertyRules as $name => $propertyRule) {

            $errors = new ErrorList();
            $rule = new ConsistencyPropertyRule($name, $propertyRule);

            if (!isset($properties[$name])) {
                if (!$rule->isRequired()) {
                    continue;
                }

                $error = new MissingPropertyConsistencyError();
                $error->setPropertyName($name);
                $errors->addError($name, $error);
            } else {
                $value = $properties[$name];

                $options = $rule->getOptions();
                if (!empty($options)) {
                    if (!in_array($value, $options)) {
                        $error = new ConsistencyError();
                        $error->setMessage(sprintf('Element with id %d has property %s with invalid value %s', $id, $name, $value));
                        $errors->addError($name, $error);
                    }
                }

                switch ($rule->getType()) {
                    case null:
                        break;
                    case ConsistencyPropertyRule::TYPE_INTEGER:
                        if (!is_int($value)) {
                            $error = new ConsistencyError();
                            $error->setMessage(sprintf('Element with id %d has property %s with value %s which should be an integer', $id, $name, $value));
                            $errors->addError($name, $error);
                        } else {
                            if ($rule->getMaximum() && $value > $rule->getMaximum()) {
                                $error = new ConsistencyError();
                                $error->setMessage(sprintf('Element with id %d has property %d greater than maximum %d', $id, $name, $value, $rule->getMaximum()));
                                $errors->addError($name, $error);
                            }

                            if ($rule->getMinimum() && $value < $rule->getMinimum()) {
                                $error = new ConsistencyError();
                                $error->setMessage(sprintf('Element with id %d has property %d lower than minimum %d', $id, $name, $value, $rule->getMinimum()));
                                $errors->addError($name, $error);
                            }
                        }
                        break;
                    case ConsistencyPropertyRule::TYPE_BOOLEAN:
                        if (!is_bool($value)) {
                            $error = new ConsistencyError();
                            $error->setMessage(sprintf('Element with id %d has property %s that should be a value', $id, json_encode($value)));
                            $errors->addError($name, $error);
                        };
                        break;
                    case ConsistencyPropertyRule::TYPE_ARRAY:
                        if (!is_array($value)) {
                            $error = new ConsistencyError();
                            $error->setMessage(sprintf('Element with id %d has property %s with value %s which should be an array', $id, $name, $value));
                            $errors->addError($name, $error);
                        };
                        break;
                    case ConsistencyPropertyRule::TYPE_DATETIME:
                        $date = $this->getDateTimeFromTimestamp($value);

                        if ($rule->getMaximum() && $date > new \DateTime($rule->getMaximum())) {
                            $error = new ConsistencyError();
                            $error->setMessage(sprintf('Element with id %d has property %s later than maximum %s', $id, $name, $rule->getMaximum()));
                            $errors->addError($name, $error);
                        }
                        if ($rule->getMinimum() && $value < $rule->getMinimum()) {
                            $error = new ConsistencyError();
                            $error->setMessage(sprintf('Element with id %d has property %s earlier than minimum %s', $id, $name, $rule->getMinimum()));
                            $errors->addError($name, $error);
                        }
                        break;
                    default:
                        break;
                }
            }

            $this->throwErrors($errors, $id);
        }
    }

    protected function getDateTimeFromTimestamp($timestamp)
    {
        if (is_int($timestamp) || is_float($timestamp)) {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($timestamp);
        } else {
            $dateTime = new \DateTime($timestamp);
        }

        return $dateTime;
    }

    /**
     * @param ConsistencyNodeData $nodeData
     * @param ConsistencyRelationshipRule $rule
     * @return ConsistencyRelationshipData[][]
     */
    protected function chooseRelationshipsByType(ConsistencyNodeData $nodeData, ConsistencyRelationshipRule $rule)
    {
        $incoming = array();
        foreach ($nodeData->getIncoming() as $candidateRelationship) {
            if ($candidateRelationship->getType() === $rule->getType()) {
                $incoming[] = $candidateRelationship;
            }
        }

        $outgoing = array();
        foreach ($nodeData->getOutgoing() as $candidateRelationship) {
            if ($candidateRelationship->getType() === $rule->getType()) {
                $outgoing[] = $candidateRelationship;
            }
        }

        return array($incoming, $outgoing);
    }

    /**
     * Two or more relationships between two nodes with the same type is forbidden
     * @param ConsistencyRelationshipData[] $incoming
     * @param ConsistencyRelationshipData[] $outgoing
     * @return array
     */
    protected function analyzeLabelAmount(array $incoming, array $outgoing)
    {
        $errors = array();
        $nodeId = null;

        $otherNodes = array();
        foreach ($incoming as $relationship) {
            $nodeId = $relationship->getEndNodeId();
            $otherNodeId = $relationship->getStartNodeId();
            $otherNodes[(integer)$otherNodeId][] = $otherNodeId;
        }
        foreach ($outgoing as $relationship) {
            $nodeId = $relationship->getStartNodeId();
            $otherNodeId = $relationship->getEndNodeId();
            $otherNodes[(integer)$otherNodeId][] = $otherNodeId;
        }

        foreach ($otherNodes as $otherNodeId => $relationships) {
            $amount = count($relationships);
            if ($amount > 1) {
                $error = new RelationshipMultipleSimilarConsistencyError();
                $error->setCurrentAmount($amount);
                $error->setNodeId($nodeId);
                $error->setOtherNodeId($otherNodeId);

                $errors[] = $error;
            }
        }

        return $errors;
    }

    protected function analyzeRelationshipAmount(ConsistencyRelationshipRule $rule, array $relationships)
    {
        $error = null;

        $count = count($relationships);
        $tooFew = $count < $rule->getMinimum();
        $tooMany = $count > $rule->getMaximum();
        if ($tooFew || $tooMany) {
            $error = new RelationshipAmountConsistencyError();
            $error->setCurrentAmount($count);
            $error->setMinimum($rule->getMinimum());
            $error->setMaximum($rule->getMaximum());
            $error->setType($rule->getType());
        }

        return $error;
    }

    /**
     * @param ConsistencyRelationshipRule $rule
     * @param ConsistencyRelationshipData[] $incoming
     * @param ConsistencyRelationshipData[] $outgoing
     * @return array
     */
    protected function analyzeDirection(ConsistencyRelationshipRule $rule, array $incoming, array $outgoing)
    {
        $errors = array();

        if ($rule->getDirection() == 'outgoing') {
            foreach ($incoming as $rel) {
                $error = new ReverseRelationshipConsistencyError($rel->getId());
                $errors[] = $error;
            }
        }

        if ($rule->getDirection() == 'incoming') {
            foreach ($outgoing as $rel) {
                $error = new ReverseRelationshipConsistencyError($rel->getId());
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @param ConsistencyRelationshipRule $rule
     * @param ConsistencyRelationshipData[] $incoming
     * @param ConsistencyRelationshipData[] $outgoing
     * @return RelationshipOtherLabelConsistencyError[]
     */
    protected function analyzeOtherNodeLabels(ConsistencyRelationshipRule $rule, array $incoming, array $outgoing)
    {
        $errors = array();

        $allowedLabels = $rule->getOtherNode();
        if (null === $allowedLabels)
        {
            return $errors;
        }

        if (is_string($allowedLabels)){
            $allowedLabels = array($allowedLabels);
        }

        foreach ($incoming as $relationship) {
            $otherNodeLabels = $relationship->getStartNodeLabels();

            $allowedNodeLabels = array_filter($otherNodeLabels, function($label) use ($allowedLabels) {return in_array($label, $allowedLabels);});
            $hasAllowedLabel = !empty($allowedNodeLabels);
            if (!$hasAllowedLabel) {
                $error = new RelationshipOtherLabelConsistencyError();
                $error->setType($rule->getType());
                $error->setOtherNodeLabel($rule->getOtherNode());
                $error->setRelationshipId($relationship->getId());
                $errors[] = $error;
            }
        }

        foreach ($outgoing as $relationship) {
            $otherNodeLabels = $relationship->getEndNodeLabels();

            $allowedNodeLabels = array_filter($otherNodeLabels, function($label) use ($allowedLabels) {return in_array($label, $allowedLabels);});
            $hasAllowedLabel = !empty($allowedNodeLabels);
            if (!$hasAllowedLabel) {
                $error = new RelationshipOtherLabelConsistencyError();
                $error->setType($rule->getType());
                $error->setOtherNodeLabel($rule->getOtherNode());
                $error->setRelationshipId($relationship->getId());
                $errors[] = $error;
            }
        }

        return $errors;
    }

    protected function throwErrors(ErrorList $errors, $id)
    {
        if ($errors->hasErrors()) {
            throw new ValidationException($errors, 'Consistency error for element ' . $id);
        }
    }
}