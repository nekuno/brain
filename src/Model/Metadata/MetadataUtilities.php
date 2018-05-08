<?php

namespace Model\Metadata;

class MetadataUtilities
{
    public function labelToType($labelName)
    {
        return lcfirst($labelName);
    }

    public function typeToLabel($typeName)
    {
        return ucfirst($typeName);
    }

    public function getLocaleString($labelField, $locale)
    {
        if (null === $labelField || !is_array($labelField) || !isset($labelField[$locale])) {
            $errorMessage = sprintf('Locale %s not present for metadata', $locale);
            throw new \InvalidArgumentException($errorMessage);
        }

        return $labelField[$locale];
    }

    public function getBirthdayRangeFromAgeRange($min = null, $max = null, $nowDate = null)
    {
        $return = array('max' => null, 'min' => null);
        if ($min) {
            $now = new \DateTime($nowDate);
            $maxBirthday = $now->modify('-' . ($min) . ' years')->format('Y-m-d');
            $return ['max'] = $maxBirthday;
        }
        if ($max) {
            $now = new \DateTime($nowDate);
            $minBirthday = $now->modify('-' . ($max + 1) . ' years')->modify('+ 1 days')->format('Y-m-d');
            $return['min'] = $minBirthday;
        }

        return $return;
    }

    /*
     * Please don't believe in this crap
     */
    public function getZodiacSignFromDate($date)
    {

        $sign = null;
        $birthday = \DateTime::createFromFormat('Y-m-d', $date);

        $zodiac[356] = 'capricorn';
        $zodiac[326] = 'sagittarius';
        $zodiac[296] = 'scorpio';
        $zodiac[266] = 'libra';
        $zodiac[235] = 'virgo';
        $zodiac[203] = 'leo';
        $zodiac[172] = 'cancer';
        $zodiac[140] = 'gemini';
        $zodiac[111] = 'taurus';
        $zodiac[78] = 'aries';
        $zodiac[51] = 'pisces';
        $zodiac[20] = 'aquarius';
        $zodiac[0] = 'capricorn';

        if (!$date) {
            return $sign;
        }

        $dayOfTheYear = $birthday->format('z');
        $isLeapYear = $birthday->format('L');
        if ($isLeapYear && ($dayOfTheYear > 59)) {
            $dayOfTheYear = $dayOfTheYear - 1;
        }

        foreach ($zodiac as $day => $sign) {
            if ($dayOfTheYear > $day) {
                break;
            }
        }

        return $sign;
    }
}