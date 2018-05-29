<?php

namespace Service\Consistency;

use Event\ExceptionEvent;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Service\Consistency\ConsistencyErrors\ConsistencyError;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConsistencyCheckerService
{
    protected $graphManager;
    protected $dispatcher;
    protected $consistencyRules;
    protected $consistencyNodeRetriever;

    /**
     * ConsistencyChecker constructor.
     * @param GraphManager $graphManager
     * @param EventDispatcherInterface $dispatcher
     * @param array $consistencyRules
     */
    public function __construct(GraphManager $graphManager, EventDispatcherInterface $dispatcher, array $consistencyRules)
    {
        $this->graphManager = $graphManager;
        $this->dispatcher = $dispatcher;
        $this->consistencyRules = $consistencyRules;
        $this->consistencyNodeRetriever = new ConsistencyNodeRetriever($this->graphManager);
    }

    /**
     * @param null $label
     * @param int $offset
     * @param null $limit
     * @return ConsistencyError[]
     */
    public function getDatabaseErrors($label = null, $offset = 0, $limit = null)
    {
        //dispatch consistency start
        $this->dispatcher->dispatch(\AppEvents::CONSISTENCY_START);
        $paginationSize = 1000; //Used for query performance

        $errors = array();
        do {
            $nodes = $this->consistencyNodeRetriever->getNodeData($paginationSize, $offset, $label);

            foreach ($nodes as $node) {
                $nodeErrors = $this->checkSingle($node);
                $errors = array_merge($errors, $nodeErrors);
            }

            $moreResultsAvailable = count($nodes) >= $paginationSize;
            $limitReached = $limit ? $offset >= $limit : false;

            $offset += $paginationSize;

        } while ($moreResultsAvailable && !$limitReached);

        //dispatch consistency end
        $this->dispatcher->dispatch(\AppEvents::CONSISTENCY_END);

        return $errors;
    }

    /** @var $errors ConsistencyError[]
     * @return ConsistencyError[]
     */
    public function solveDatabaseErrors(array $errors)
    {
        foreach ($errors as $error) {
            $solver = $this->chooseSolver($error->getRule());
            $isSolved = $solver->solve($error);
            $error->setSolved($isSolved);
        }

        return $errors;
    }

    /**
     * @param ConsistencyNodeData $nodeData
     * @return ConsistencyError[]
     */
    protected function checkSingle(ConsistencyNodeData $nodeData)
    {
        $nodeId = $nodeData->getId();

        $rules = $this->chooseRules($nodeData);
        $errors = array();

        foreach ($rules as $rule) {

            $nodeRule = new ConsistencyNodeRule($rule);
            $checker = $this->chooseChecker($nodeRule);

            try {
                $checker->checkNode($nodeData, $nodeRule);
            } catch (ValidationException $e) {
                $newErrors = $e->getErrors();
                foreach ($newErrors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $newError) {
                        /** @var ConsistencyError $newError */
                        $newError->setRule($nodeRule);
                        $newError->setNodeId($nodeId);
                    }

                    $errors = array_merge($errors, $fieldErrors);
                }

                $this->dispatcher->dispatch(\AppEvents::CONSISTENCY_ERROR, new ExceptionEvent($e, 'Checking node ' . $nodeId));
            }
        }

        return $errors;
    }

    protected function chooseRules(ConsistencyNodeData $nodeData)
    {
        $labels = $nodeData->getLabels();

        return array_filter($this->consistencyRules, function($rule) use ($labels) {
            $nodeRule = new ConsistencyNodeRule($rule);
            return in_array($nodeRule->getLabel(), $labels);
        });
    }

    /**
     * @param ConsistencyNodeRule $rule
     * @return ConsistencyChecker
     */
    protected function chooseChecker(ConsistencyNodeRule $rule)
    {
        $ruleClass = $rule->getCheckerClass();
        $defaultClass = ConsistencyChecker::class;

        if ($ruleClass){
            return new $ruleClass();
        } else {
            return new $defaultClass();
        }
    }

    /**
     * @param ConsistencyNodeRule $rule
     * @return ConsistencySolver
     */
    protected function chooseSolver(ConsistencyNodeRule $rule)
    {
        $ruleClass = $rule->getSolverClass();
        $defaultClass = ConsistencySolver::class;

        if ($ruleClass){
            return new $ruleClass($this->graphManager);
        } else {
            return new $defaultClass($this->graphManager);
        }
    }
}