<?php

namespace Service\Consistency;


use Event\ExceptionEvent;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ConsistencyCheckerService
{
    protected $graphManager;
    protected $dispatcher;
    protected $consistency;

    /**
     * ConsistencyChecker constructor.
     * @param GraphManager $graphManager
     * @param EventDispatcher $dispatcher
     * @param array $consistency
     */
    public function __construct(GraphManager $graphManager, EventDispatcher $dispatcher, array $consistency)
    {
        $this->graphManager = $graphManager;
        $this->dispatcher = $dispatcher;
        $this->consistency = $consistency;
    }

    public function checkDatabase()
    {
        //dispatch consistency start
        $this->dispatcher->dispatch(\AppEvents::CONSISTENCY_START);
        $paginationSize = 1000;
        $offset = 0;

        do{
            $qb = $this->graphManager->createQueryBuilder();

            $qb->match('(a)');

            $qb->returns('a')
                ->skip('{offset}')
                ->limit($paginationSize)
                ->setParameter('offset', $offset);;

            $result = $qb->getQuery()->getResultSet();
            foreach ($result as $row) {
                $node = $row->offsetGet('a');
                $this->checkNode($node);
            }

            $offset += $paginationSize;
        } while ($result->count() >= $paginationSize);

        //dispatch consistency end
        $this->dispatcher->dispatch(\AppEvents::CONSISTENCY_END);

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