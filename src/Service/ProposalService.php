<?php

namespace Service;

use Model\Date\Date;
use Model\Date\DateManager;
use Model\Availability\AvailabilityManager;
use Model\Proposal\Proposal;
use Model\Proposal\ProposalFields\ProposalFieldAvailability;
use Model\Proposal\ProposalManager;
use Model\Proposal\ProposalTagManager;
use Model\User\User;

class ProposalService
{
    protected $dateManager;
    protected $availabilityManager;
    protected $proposalManager;
    protected $proposalTagManager;

    /**
     * ProposalService constructor.
     * @param DateManager $dateManager
     * @param AvailabilityManager $availabilityManager
     * @param ProposalManager $proposalManager
     * @param ProposalTagManager $proposalTagManager
     */
    public function __construct(DateManager $dateManager, AvailabilityManager $availabilityManager, ProposalManager $proposalManager, ProposalTagManager $proposalTagManager)
    {
        $this->dateManager = $dateManager;
        $this->availabilityManager = $availabilityManager;
        $this->proposalManager = $proposalManager;
        $this->proposalTagManager = $proposalTagManager;
    }

    public function getById($proposalId, $locale)
    {
        $proposal = $this->proposalManager->getById($proposalId, $locale);

        $availability = $this->availabilityManager->getByProposal($proposal);
        /** @var ProposalFieldAvailability $availabilityField */

        if (null !== $availability) {
            $availabilityField = new ProposalFieldAvailability();
            $availabilityField->setAvailability($availability);
            $proposal->addField($availabilityField);
        }

        return $proposal;
    }

    public function getByUser(User $user, $locale)
    {
        $proposalIds = $this->proposalManager->getIdsByUser($user);

        $proposals = array();
        foreach ($proposalIds as $proposalId)
        {
            $proposal = $this->getById($proposalId, $locale);
            $proposals[] = $proposal;
        }

        foreach ($proposals as $proposal) {
            $availability = $this->availabilityManager->getByProposal($proposal);
            if (null == $availability) {
                continue;
            }

            $dates = $this->dateManager->getByAvailability($availability);
            $availability->setDates($dates);
            $availabilityField = new ProposalFieldAvailability();
            $availabilityField->setAvailability($availability);
            $proposal->addField($availabilityField);
        }

        return $proposals;
    }

    public function create($data, User $user)
    {
        $proposalId = $this->proposalManager->create();
        $proposal = $this->proposalManager->update($proposalId, $data);
        $this->proposalManager->relateToUser($proposal, $user);

        $proposal = $this->createAvailability($proposal, $data);

        return $proposal;
    }

    public function update($proposalId, $data)
    {
        $proposal = $this->proposalManager->update($proposalId, $data);

        if ($proposal->getField('availability')) {
            $availabilityId = $this->getAvailabilityId($proposal);
            $this->availabilityManager->delete($availabilityId);
            $proposal->removeField('availability');
        }

        $proposal = $this->createAvailability($proposal, $data);

        return $proposal;
    }

    public function delete(array $data)
    {
        $proposalId = (integer)$data['proposalId'];
        $locale = $data['locale'];
        $proposal = $this->getById($proposalId, $locale);

        if ($proposal->getField('availability')) {
            $availabilityId = $this->getAvailabilityId($proposal);
            $this->availabilityManager->delete($availabilityId);
        }

        $this->deleteTags($proposal);
        $this->proposalManager->delete($proposalId);
    }

    /**
     * @param $data
     * @return Date[]
     * @throws \Exception
     */
    protected function createDates($data)
    {
        if (!isset($data['availability']) || !isset($data['availability']['days'])) {
            return array();
        }

        $days = $data['days'];
        $dates = array();
        foreach ($days as $day) {
            $dates[] = $this->dateManager->merge($day);
        }

        return $dates;
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function getDaysIds(array $data)
    {
        $dates = $this->createDates($data);
        $ids = array();
        foreach ($dates as $date) {
            $ids[] = $date->getDayId();
        }

        return $ids;
    }

    protected function getAvailabilityId(Proposal $proposal)
    {
        /** @var ProposalFieldAvailability $availabilityField */
        $availabilityField = $proposal->getField('availability');
        $availabilityId = $availabilityField->getAvailability()->getId();

        return $availabilityId;
    }

    protected function createAvailability(Proposal $proposal ,array $data)
    {
        $daysIds = $this->getDaysIds($data);
        if (!empty($daysIds)) {
            $availability = $this->availabilityManager->create($data);
            $this->availabilityManager->relateToProposal($availability, $proposal);

            $availabilityField = new ProposalFieldAvailability();
            $availabilityField->setAvailability($availability);
            $proposal->addField($availabilityField);
        }

        return $proposal;
    }

    protected function deleteTags(Proposal $proposal)
    {
        $tagFields = array('tag', 'tag_and_suggestion');
        foreach ($proposal->getFields() as $field) {
            $isTagField = in_array($field->getType(), $tagFields);
            if ($isTagField) {
                $tagName = $field->getName();
                $tagValue = $field->getValue();

                $this->proposalTagManager->deleteIfOrphan($tagName, $tagValue);
            }
        }
    }

}