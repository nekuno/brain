<?php

namespace Model\Location;

class Location implements \JsonSerializable
{
    protected $id;
    protected $latitude;
    protected $longitude;
    protected $address;
    protected $locality;
    protected $country;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param mixed $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param mixed $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * @param mixed $locality
     */
    public function setLocality($locality)
    {
        $this->locality = $locality;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    public function jsonSerialize()
    {
        $location = array();

        if (isset($this->latitude)) {
            $location['latitude'] = $this->latitude;
        }
        if (isset($this->longitude)) {
            $location['longitude'] = $this->longitude;
        }
        if (isset($this->address)) {
            $location['address'] = $this->address;
        }
        if (isset($this->locality)) {
            $location['locality'] = $this->locality === 'N/A' ? $this->address : $this->locality;
        }
        if (isset($this->country)) {
            $location['country'] = $this->country;
        }

        return empty($location) ? null : $location;
    }

    public function toArray()
    {
        return $this->jsonSerialize();
    }

}