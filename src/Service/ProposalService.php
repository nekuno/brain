<?php

namespace Service;

use Model\Date\Date;
use Model\Date\DateManager;
use Model\Availability\AvailabilityManager;
use Model\Proposal\ProposalFields\ProposalFieldAvailability;
use Model\Proposal\ProposalManager;
use Model\User\User;

class ProposalService
{
    protected $dateManager;
    protected $availabilityManager;
    protected $proposalManager;

    /**
     * ProposalService constructor.
     * @param $dateManager
     * @param $availabilityManager
     * @param $proposalManager
     */
    public function __construct(DateManager $dateManager, AvailabilityManager $availabilityManager, ProposalManager $proposalManager)
    {
        $this->dateManager = $dateManager;
        $this->availabilityManager = $availabilityManager;
        $this->proposalManager = $proposalManager;
    }

    public function getById($proposalId)
    {
        $proposal = $this->proposalManager->getById($proposalId);

        $availability = $this->availabilityManager->getByProposal($proposal);
        /** @var ProposalFieldAvailability $availabilityField */
        $availabilityField = $proposal->getField('availability');
        $availabilityField->setAvailability($availability);

        return $proposal;
    }

    public function getByUser(User $user)
    {
        $proposals = $this->proposalManager->getByUser($user);

        foreach ($proposals as $proposal)
        {
            $availability = $this->availabilityManager->getByProposal($proposal);

            $dates = $this->dateManager->getByAvailability($availability);
            $availability->setDates($dates);
        }

        return $proposals;
    }

    public function create($data)
    {
        $dates = $this->createDates($data);
        $daysIds = $this->getDaysIds($dates);

        $availability = $this->availabilityManager->create($daysIds);
        $data['availability'] = $availability;

        $proposal = $this->proposalManager->create($data);

        return $proposal;
    }

    public function update($data)
    {
        $proposalId = $data['proposalId'];
        $proposal = $this->getById($proposalId);

        /** @var ProposalFieldAvailability $availabilityField */
        $availabilityField = $proposal->getField('availability');
        $availabilityId = $availabilityField->getAvailability()->getId();
        $availability = $this->availabilityManager->update($availabilityId, $data);

        $proposal = $this->proposalManager->update($proposalId, $data);

        /** @var ProposalFieldAvailability $availabilityField */
        $availabilityField = $proposal->getField('availability');
        $availabilityField->setAvailability($availability);

        return $proposal;
    }

    /**
     * @param $data
     * @return Date[]
     * @throws \Exception
     */
    protected function createDates($data)
    {
        $days = $data['days'];
        $dates = array();
        foreach ($days as $day) {
            $dates[] = $this->dateManager->merge($day);
        }

        return $dates;
    }

    /**
     * @param Date[] $dates
     * @return array
     */
    protected function getDaysIds(array $dates)
    {
        $ids = array();
        foreach ($dates as $date) {
            $ids[] = $date->getDayId();
        }

        return $ids;
    }

}