<?php

namespace Model\Neo4j;

use Doctrine\Common\Collections\ArrayCollection;


class QueryBuilder
{

    /**
     * @var ArrayCollection
     */
    protected $parameters;

    /**
     * @var GraphManager
     */
    private $_gm;

    /**
     * @var array
     */
    private $_parts = array();

    /**
     * @param GraphManager $gm
     */
    public function __construct(GraphManager $gm)
    {
        $this->_gm = $gm;
        $this->parameters = new ArrayCollection();
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->_gm->createQuery($this->_getCypher(), $this->parameters->toArray());
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setParameter($key, $value)
    {
        $this->parameters->set($key, $value);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param $parameters
     * @return $this
     */
    public function setParameters($parameters)
    {
        $this->parameters = new ArrayCollection($parameters);

        return $this;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getParameter($key)
    {
        return $this->parameters->get($key);
    }

    /**
     * @param $cypherPartName
     * @param $cypherPart
     * @return $this
     */
    public function add($cypherPartName, $cypherPart)
    {
        $this->_parts[] = new CypherPart($cypherPartName, $cypherPart);

        return $this;
    }

    /**
     * @param mixed $match
     * @return QueryBuilder
     */
    public function match($match = null)
    {

        if (empty($match)) {
            return $this;
        }

        $matches = is_array($match) ? $match : func_get_args();

        return $this->add('match', implode(', ', $matches));
    }

    /**
     * @param mixed $optionalMatch
     * @return QueryBuilder
     */
    public function optionalMatch($optionalMatch = null)
    {

        if (empty($optionalMatch)) {
            return $this;
        }

        $optionalMatches = is_array($optionalMatch) ? $optionalMatch : func_get_args();

        return $this->add('optional match', implode(', ', $optionalMatches));
    }

    /**
     * @param mixed $where
     * @return QueryBuilder
     * @throws \Exception
     */
    public function where($where = null)
    {

        if (empty($where)) {
            return $this;
        }

        $wheres = is_array($where) ? $where : func_get_args();

        foreach ($wheres as $key => $value)
        {
            $wheres[$key] = '('.$value.')';
        }

        return $this->add('where', implode(' AND ', $wheres));
    }

    /**
     * @param mixed $create
     * @return QueryBuilder
     */
    public function create($create = null)
    {

        if (empty($create)) {
            return $this;
        }

        $creates = is_array($create) ? $create : func_get_args();

        return $this->add('create', implode(', ', $creates));
    }

    /**
     * @param mixed $createUnique
     * @return QueryBuilder
     */
    public function createUnique($createUnique = null)
    {

        if (empty($createUnique)) {
            return $this;
        }

        $createUniques = is_array($createUnique) ? $createUnique : func_get_args();

        return $this->add('create unique', implode(', ', $createUniques));
    }

    /**
     * @param mixed $merge
     * @return QueryBuilder
     */
    public function merge($merge = null)
    {

        if (empty($merge)) {
            return $this;
        }

        $merges = is_array($merge) ? $merge : func_get_args();

        return $this->add('merge', implode(', ', $merges));
    }

    /**
     * @param mixed $set
     * @return QueryBuilder
     */
    public function set($set = null)
    {

        if (empty($set)) {
            return $this;
        }

        $sets = is_array($set) ? $set : func_get_args();

        return $this->add('set', implode(', ', $sets));
    }

    /**
     * @param mixed $delete
     * @return QueryBuilder
     */
    public function delete($delete = null)
    {

        if (empty($delete)) {
            return $this;
        }

        $deletes = is_array($delete) ? $delete : func_get_args();

        return $this->add('delete', implode(', ', $deletes));
    }

    /**
     * @param mixed $remove
     * @return QueryBuilder
     */
    public function remove($remove = null)
    {

        if (empty($remove)) {
            return $this;
        }

        $removes = is_array($remove) ? $remove : func_get_args();

        return $this->add('remove', implode(', ', $removes));
    }

    /**
     * @param mixed $return
     * @return QueryBuilder
     */
    public function returns($return = null)
    {

        if (empty($return)) {
            return $this;
        }

        $returns = is_array($return) ? $return : func_get_args();

        return $this->add('return', implode(', ', $returns));
    }

    /**
     * @param mixed $with
     * @return QueryBuilder
     */
    public function with($with = null)
    {

        if (empty($with)) {
            return $this;
        }

        $withs = is_array($with) ? $with : func_get_args();

        return $this->add('with', implode(', ', $withs));
    }

    public function unwind($unwind = null)
    {
        if (empty($unwind)){
            return $this;
        }

        $unwinds = is_array($unwind) ? $unwind : func_get_args();

        return $this->add('unwind', implode(', ', $unwinds));
    }

    /**
     * @param mixed $orderBy
     * @return QueryBuilder
     */
    public function orderBy($orderBy = null)
    {

        if (empty($orderBy)) {
            return $this;
        }

        $orderBys = is_array($orderBy) ? $orderBy : func_get_args();

        return $this->add('order by', implode(', ', $orderBys));
    }

    /**
     * @param integer $limit
     * @return QueryBuilder
     */
    public function limit($limit = null)
    {

        if (empty($limit)) {
            return $this;
        }

        return $this->add('limit', $limit);
    }

    /**
     * @param integer $skip
     * @return QueryBuilder
     */
    public function skip($skip = null)
    {

        if (empty($skip)) {
            return $this;
        }

        return $this->add('skip', $skip);
    }

    /**
     * Equivalents to MATCH (a) WHERE a:Type1 OR a:Type 2, but faster
     * @param array $types
     * @param string $name
     * @param array $withs
     * @return $this
     */
    public function filterContentByType(array $types, $name = 'content', $withs = array())
    {
        //See http://stackoverflow.com/questions/20003769/neo4j-match-multiple-labels-2-or-more
        //Much slower than next alternative, it seems.

//        if (!empty($types)) {
//            $this->with(array_merge($withs, array("collect(id($name)) as ids")));
//
//            $counter = 0;
//            $lastCounter = -1;
//            foreach ($types as $type) {
//                $this->optionalMatch("($name$counter:$type)")
//                    ->where("id($name$counter) IN ids");
//                if ($counter == 0) {
//                    $with = array_merge($withs, array("collect(distinct $name$counter) AS collect$name$counter", 'ids'));
//                } else {
//                    $with = array_merge($withs, array("collect(distinct $name$counter) + collect$name$lastCounter AS collect$name$counter", 'ids'));
//                }
//                $this->with($with);
//                $lastCounter = $counter;
//                $counter++;
//            }
//            $this->unwind("collect$name$lastCounter AS $name");
//        }
//
//        $this->with(array_merge($withs, array($name)));

        //See http://stackoverflow.com/questions/25530383/neo4j-match-multiple-labels?rq=1
        //Faster for our "filter from a given subgraph" case
        $this->match("($name)");
        $wheres = array();
        foreach ($types as $type){
            if ($type === 'Link'){
                continue;
            }
            $wheres[] = "('$type' in labels($name))";
        }
        if (!empty($wheres)){
            $this->where(implode(' OR ', $wheres));
        }

        $this->with(array_merge($withs, array($name)));
        
        return $this;
    }

    /**
     * Faster than this->filterContentByType when matching for first time a single type (content not already a low cardinality subset)
     * @param array $types
     * @param string $name
     * @return $this
     */
    public function matchContentByType(array $types, $name = 'content')
    {
        if (count($types) > 1){
            return $this->filterContentByType($types, $name);
        }
        
        if (count($types) == 0){
            $types = array('Link');
        }
        
        $typesString = $types[0] !== 'Link' ? ':'.$types[0] : '';
        $this->match("($name:Link$typesString)");
        $this->with($name);
        
        return $this;
    }

    /**
     * @return string
     */
    private function _getCypher()
    {

        $parts = array();
        foreach ($this->_parts as $part) {
            /* @var $part CypherPart */
            $parts[] = $part->getCypherPartName() . ' ' . $part->getCypherPart();
        }

        return implode("\n", $parts);
    }
}