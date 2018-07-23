<?php

namespace Model\Proposal;

use Model\Neo4j\GraphManager;
use Model\Proposal\ProposalFields\ProposalBuilder;
use Model\User\User;

class ProposalManager
{
    protected $graphManager;

    //TODO: ProposalValidator

    protected $proposalBuilder;

    /**
     * ProposalManager constructor.
     * @param GraphManager $graphManager
     * @param ProposalBuilder $proposalBuilder
     */
    public function __construct(GraphManager $graphManager, ProposalBuilder $proposalBuilder)
    {
        $this->graphManager = $graphManager;
        $this->proposalBuilder = $proposalBuilder;
    }

    public function create(array $data)
    {
        $userId = $data['userId'];

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(user:User{qnoow_id:{userId}})')
            ->with('user')
            ->setParameter('userId', $userId);

        $qb->create('(user)-[:PROPOSES]->(proposal:Proposal)');

        $qb->returns('id(proposal) AS proposalId');

        $resultSet = $qb->getQuery()->getResultSet();

        $proposalId = $resultSet->current()->offsetGet('proposalId');

        $proposal = $this->update($proposalId, $data);

        return $proposal;
    }

    public function update($proposalId, array $data)
    {
        $proposalName = $data['name'];
        $proposal = $this->proposalBuilder->buildFromData($proposalName, $data);

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(proposal:Proposal)')
            ->where('id(proposal) = {proposalId}')
            ->setParameter('proposalId', $proposalId);

        $proposalLabel = $proposal->getLabel();
        $qb->remove('proposal:Work:Sport:Videogame:Hobby:Show:Restaurant:Plan');
        $qb->set("proposal:$proposalLabel")
            ->with('proposal');

        $variables = array('proposal');
        foreach ($proposal->getFields() as $field) {
            $qb->add('', $field->addInformation($variables));
        }

        $qb->returns('proposal');

        $qb->getQuery()->getResultSet();

        return $proposal;
    }

    public function getById($proposalId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(proposal:Proposal)')
            ->where('id(proposal) = {proposalId}')
            ->setParameter('proposalId', $proposalId);

        $qb->returns('{id: id(proposal), labels: labels(proposal)} AS proposal');

        $resultSet = $qb->getQuery()->getResultSet();

        $proposalData = $qb->getData($resultSet->current());

        $proposalName = $this->getProposalName($proposalData);
        $proposal = $this->proposalBuilder->buildFromData($proposalName, $proposalData);

        return $proposal;
    }

    /**
     * @param User $user
     * @return Proposal[]
     * @throws \Exception
     */
    public function getByUser(User $user)
    {
        $userId = $user->getId();

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(user:User{qnoow_id: {userId}})')
            ->setParameter('userId', $userId)
            ->with('user');

        $qb->match('(user)-[:PROPOSES]->(proposal:Proposal)')
            ->returns('{id: id(proposal), labels: labels(proposal)} AS proposal');

        $resultSet = $qb->getQuery()->getResultSet();

        $proposals = array();
        foreach ($resultSet as $row)
        {
            $proposalData = $qb->getData($row);

            $proposalName = $this->getProposalName($proposalData);
            $proposal = $this->proposalBuilder->buildFromData($proposalName, $proposalData);

            $proposals[] = $proposal;

        }

        return $proposals;
    }

    protected function getProposalName($proposalData)
    {
        $labels = $proposalData['labels'];

        foreach ($labels as $label)
        {
            if ($label !== 'Proposal'){
                return $label;
            }
        }

        return '';
    }

}