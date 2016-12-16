<?php

namespace Tests\Model\User\Recommendation;

use Model\User\ProfileFilterModel;

class ProfileFilterModelTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider getAgeRange
     */
    public function testGetBirthdayRangeFromAgeRange($now, $ageMin, $ageMax, $expectedReturn)
    {
        $gm = $client = $this->getMockBuilder('Model\Neo4j\GraphManager')
            ->disableOriginalConstructor()
            ->getMock();

        $model = new ProfileFilterModel($gm, array(), array(), array(), array(), 'es');

        $return = $model->getBirthdayRangeFromAgeRange($ageMin, $ageMax, $now);

        $this->assertEquals($expectedReturn, $return, 'getting birthday range from age range');
    }

    public function getAgeRange()
    {
        return array(
            // I want someone whose 20 birthday is today, or yesterday, or the day before...until 364 days ago
            array('2000-08-15', '20', '20', array('max' => '1980-08-15', 'min' => '1979-08-16')),
            array('2000-08-15', '20', '30', array('max' => '1980-08-15', 'min' => '1969-08-16')),
            array('2000-08-15', '15', '25', array('max' => '1985-08-15', 'min' => '1974-08-16')),
        );
    }
}