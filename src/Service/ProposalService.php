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
        foreach ($proposalIds as $proposalId) {
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
    protected function createDateObjects($data)
    {
        if (!isset($data['availability'])) {
            return array();
        }

        $days = array_map(
            function ($object) {
                return $object['day'];
            },
            $data['availability']['static']
        );
        $dates = array();
        foreach ($days as $index => $day) {
            $date = $this->dateManager->merge($day);
            if ($date !== null) {
                $dates[$index] = $date;
            }
        }

        return $dates;
    }

    protected function createTimeRanges($data)
    {
        if (!isset($data['availability'])) {
            return array();
        }

        $ranges = array_map(
            function ($object) {
                return $object['range'];
            },
            $data['availability']['static']
        );

        $secondsInDay = 24 * 3600;
        foreach ($ranges as &$range) {
            $range['min'] = isset($range['min']) ? $range['min'] : 0;
            $range['max'] = isset($range['max']) ? $range['min'] : $secondsInDay;

        }

        return $ranges;
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function createDates(array $data)
    {
        $dates = $this->createDateObjects($data);
        $ranges = $this->createTimeRanges($data);
        $ids = array();
        foreach ($dates as $index => $date) {
            $ids[] = array('date' => $date, 'range' => $ranges[$index]);
        }

        return $ids;
    }

    protected function getDynamicData(array $data)
    {
        if (!isset($data['availability']) || !isset($data['availability']['dynamic'])) {
            return array();
        }

        $ranges = array();
        foreach ($data['availability']['dynamic'] as $datum) {
            $weekday = $datum['weekday'];
            $range = $datum['range'];

            $ranges[] = array('weekday' => $weekday, 'range' => array($range['min'], $range['max']));
        }

        return $ranges;
    }

    protected function getAvailabilityId(Proposal $proposal)
    {
        /** @var ProposalFieldAvailability $availabilityField */
        $availabilityField = $proposal->getField('availability');
        $availabilityId = $availabilityField->getAvailability()->getId();

        return $availabilityId;
    }

    protected function createAvailability(Proposal $proposal, array $data)
    {
        $dates = $this->createDates($data);
        $dynamic = $this->getDynamicData($data);

        $availability = null;
        if (!empty($dates) || !empty($dynamic)) {
            $availability = $this->availabilityManager->create();
        }

        if (!empty($dates)) {
            $this->availabilityManager->addStatic($availability, $dates);
        }

        if (!empty($dynamic)) {
            $this->availabilityManager->addDynamic($availability, $dynamic);
        }

        if ($availability) {
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