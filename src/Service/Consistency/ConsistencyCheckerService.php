<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Service\Consistency;


use Event\ExceptionEvent;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Model\Exception\ValidationException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ConsistencyCheckerService
{
    protected $consistency;

    /**
     * ConsistencyChecker constructor.
     * @param EventDispatcher $dispatcher
     * @param array $consistency
     */
    public function __construct(EventDispatcher $dispatcher, array $consistency)
    {
        $this->dispatcher = $dispatcher;
        $this->consistency = $consistency;
    }

    /**
     * @param Node $node
     */
    public function checkNode(Node $node)
    {
        /** @var Label[] $labels */
        $labelNames = $this->getLabelNames($node);

        $rules = $this->consistency;
        foreach ($rules['nodes'] as $rule) {
            if (!in_array($rule['label'], $labelNames)) {
                continue;
            }

            if (isset($rule['class'])){
                $checker = new $rule['class']();
            } else {
                $checker = new ConsistencyChecker();
            }

            $nodeRule = new ConsistencyNodeRule($rule);
            try{
                $checker->check($node, $nodeRule);
            } catch (ValidationException $e) {
                $this->dispatcher->dispatch(\AppEvents::CONSISTENCY_ERROR, new ExceptionEvent($e, 'Checking node '.$node->getId()));
            }
        }
    }


    static function getLabelNames(Node $node)
    {
        /** @var Label[] $labels */
        $labels = $node->getLabels();
        $labelNames = array();
        foreach ($labels as $label) {
            $labelNames[] = $label->getName();
        }

        return $labelNames;
    }
}