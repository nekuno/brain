<?php

namespace Service;

use Model\Availability\Availability;
use Model\Availability\AvailabilityDataFormatter;
use Model\Availability\AvailabilityManager;
use Model\Date\DateManager;
use Model\Date\DayPeriodManager;
use Model\Proposal\Proposal;
use Model\User\User;

class AvailabilityService
{
    protected $availabilityManager;
    protected $availabilityDataFormatter;
    protected $dayPeriodManager;
    protected $dateManager;

    /**
     * AvailabilityService constructor.
     * @param AvailabilityManager $availabilityManager
     * @param AvailabilityDataFormatter $availabilityDataFormatter
     * @param DayPeriodManager $dayPeriodManager
     * @param DateManager $dateManager
     */
    public function __construct(
        AvailabilityManager $availabilityManager,
        AvailabilityDataFormatter $availabilityDataFormatter,
        DayPeriodManager $dayPeriodManager,
        DateManager $dateManager
    ) {
        $this->availabilityManager = $availabilityManager;
        $this->availabilityDataFormatter = $availabilityDataFormatter;
        $this->dayPeriodManager = $dayPeriodManager;
        $this->dateManager = $dateManager;
    }

    public function getByUser(User $user)
    {
        $availability = $this->availabilityManager->getByUser($user);

        if (null == $availability)
        {
            return null;
        }

        $this->complete($availability);

        return $availability;
    }

    public function getByProposal(Proposal $proposal)
    {
        $availability = $this->availabilityManager->getByProposal($proposal);
        if (null == $availability) {
            return null;
        }

        $this->complete($availability);

        return $availability;
    }

    public function create($data, User $user)
    {
        $availability = $this->saveWithData($data);

        $this->availabilityManager->relateToUser($availability, $user);

        return $availability;
    }

    public function saveWithData($data)
    {
        $formattedData = $this->availabilityDataFormatter->getFormattedData($data);

        $availability = $this->availabilityManager->create();
        $availability = $this->availabilityManager->addDynamic($availability, $formattedData['dynamic']);

        //TODO: Remove this necessity and build static data from relationships
        if ($availability) {
            $staticOriginalData = isset($data['availability']) && isset($data['availability']['static']) ? json_encode($data['availability']['static']) : '';
            $availability->setStatic($staticOriginalData);
            $availability = $this->availabilityManager->addStatic($availability, $formattedData['static']);
        }

        return $availability;
    }

    public function update($data, User $user)
    {
        $this->delete($user);

        return $this->create($data, $user);
    }

    public function delete(User $user)
    {
        $availability = $this->getByUser($user);

        if ($availability == null)
        {
            return null;
        }

        return $this->availabilityManager->delete($availability->getId());
    }

    protected function complete(Availability $availability)
    {
        $dayPeriods = $this->dayPeriodManager->getByAvailabilityStatic($availability);
        foreach ($dayPeriods as $dayPeriod) {
            $date = $this->dateManager->getByPeriod($dayPeriod);
            $dayPeriod->setDate($date);
        }

        $availability->setDayPeriods($dayPeriods);
    }
}