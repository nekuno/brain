<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User\Filters;


use Everyman\Neo4j\Node;
use Model\Neo4j\GraphManager;
use Model\User\ContentFilterModel;
use Service\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FilterContentManager
{

    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @var ContentFilterModel
     */
    protected $contentFilterModel;

    /**
     * @var Validator
     */
    protected $validator;

    public function __construct(GraphManager $graphManager, ContentFilterModel $contentFilterModel, Validator $validator)
    {
        $this->graphManager = $graphManager;
        $this->contentFilterModel = $contentFilterModel;
        $this->validator = $validator;
    }

    public function getFilterContentByThreadId($id)
    {
        $filterId = $this->getFilterContentIdByThreadId($id);
        return $this->getFilterContentById($filterId);
    }
    /**
     * @param FilterContent $filters
     * @return Node|null
     * @throws \Model\Neo4j\Neo4jException
     */
    public function createFilterContent(FilterContent $filters)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->create('(filter:Filter:FilterContent)')
            ->returns('filter');
        $result = $qb->getQuery()->getResultSet();

        $filter = $result->current()->offsetGet('filter');
        if ($filter == null){
            return null;
        }

        return $this->updateFiltersContent($filters);
    }

    public function updateFilterContentByThreadId($id, $filtersArray)
    {
        $this->validate($filtersArray);

        $filters = $this->buildFiltersContent();

        $filterId = $this->getFilterContentIdByThreadId($id);
        $filters->setId($filterId);

        if (isset($filtersArray['tag']))
        {
            $filters->setTag($filtersArray['tag']);
        }

        if (isset($filtersArray['type']))
        {
            $filters->setType($filtersArray['type']);
        }

        $this->updateFiltersContent($filters);

        return $filters;
    }

    /**
     * @param FilterContent $filters
     * @return bool
     */
    public function updateFiltersContent(FilterContent $filters)
    {
        $type = $filters->getType();
        $tag = $filters->getTag();

        if ($tag){
            $this->saveTag($filters->getId(), $tag );
        }

        if ($type){
            $this->saveType($filters->getId(), $type);
        }

        return true;
    }

    protected function validate(array $data)
    {
        $this->validator->validateEditFilterContent($data, $this->getChoices());
    }

    //TODO: LinkModel->getValidTypes() is the same
    protected function getChoices(){
        return array(
            'type' => array(
                'Link', 'Audio', 'Video', 'Image', 'Creator'
            )
        );
    }

    /**
     * @return FilterContent
     */
    protected function buildFiltersContent()
    {
        return new FilterContent();
    }

    /**
     * @param $id
     * @return FilterContent
     */
    protected function getFilterContentById($id)
    {
        $filter = $this->buildFiltersContent();
        $filter->setId($id);
        $filter->setTag($this->getTag($id));
        $filter->setType($this->getType($id));
        return $filter;
    }

    protected function getFilterContentIdByThreadId($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->with('thread')
            ->merge('(thread)-[:HAS_FILTER]->(filter:Filter:FilterContent)')
            ->returns('id(filter) as filterId');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0){
            return null;
        }

        return $result->current()->offsetGet('filterId');

    }

    /**
     * @param $id
     * @return mixed
     * @throws \Model\Neo4j\Neo4jException
     */
    private function getTag($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:Filter)')
            ->where('id(filter) = {id}')
            ->optionalMatch('(filter)-[:FILTERS_BY]->(tag:Tag)')
            ->returns('tag');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Filter with id ' . $id . ' not found');
        }

        $tags = array();
        foreach ($result as $row){
            /** @var Node $tagNode */
            $tagNode = $row->offsetGet('tag');
            if ($tagNode) {
                $tags[] = $tagNode->getProperty('name');
            }
        }

        return $tags;
    }

    /**
     * @param $id
     * @return array
     * @throws \Model\Neo4j\Neo4jException
     */
    private function getType($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:FilterContent)')
            ->where('id(filter) = {id}')
            ->returns('filter.type as type');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Filter with id ' . $id . ' not found');
        }

        $type= $result->current()->offsetGet('type');

        //TODO: Unnedeed if database is consistent for sure.
        try{
            $type = \GuzzleHttp\json_decode($type);
        } catch (\Exception $e){

        }

        return $type;
    }

    private function saveType($id, $type)
    {
        //TODO: Validate

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:FilterContent)')
            ->where('id(filter) = {id}')
            ->set('filter.type = {type}')
            ->returns('filter');
        $qb->setParameters(array(
            'id' => (integer)$id,
            'type' => json_encode($type),
        ));
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Filter with id ' . $id . ' not found');
        }

    }
    
    private function saveTag($id, $tag)
    {
        //TODO: Validate

        if (!$tag) {
            return;
        }

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(filter:FilterContent)')
            ->where('id(filter) = {id}')
            ->optionalMatch('(filter)-[old_tag_rel:FILTERS_BY]->(:Tag)')
            ->delete('old_tag_rel')
            ->with('filter');
        foreach ($tag as $singleTag) {
            $trimmedName = preg_replace('/\s+/', '', $singleTag);
            $trimmedName = str_replace(',', '', $trimmedName);
            $qb->merge("(tag$trimmedName:Tag{name: '{$singleTag}' })")
                ->merge("(filter)-[:FILTERS_BY]->(tag$trimmedName)");
            $qb->setParameter($singleTag, $singleTag);
        }

            $qb->returns('filter');
        $qb->setParameters(array(
            'id' => (integer)$id
        ));
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Filter with id ' . $id . ' or tag with name ' . $tag . ' not found');
        }
    }


}