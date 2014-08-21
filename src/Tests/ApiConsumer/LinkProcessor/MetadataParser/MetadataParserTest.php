<?php
/**
 * @author adrian.web.dev@gmail.com
 */

namespace Tests\ApiConsumer\LinkProcessor\MetadataParser;


class MetadataParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var AuxMetadataParser
     */
    private $parser;

    public function setUp()
    {
        $this->parser = new AuxMetadataParser();
    }

    public function testConvertArrayKeysAndValuesToLower()
    {
        $testData = array(
            array('Rel' => 'Value', 'name' => null, 'COntent' => 'My Content', 'property' => null),
        );
        $expected = array(
            array('rel' => 'value', 'name' => null, 'content' => 'My Content', 'property' => null),
        );

        $actual = $this->parser->keysAndValuesNotContentToLowercase($testData);

        $this->assertEquals($expected, $actual);
    }

    public function testValidateNameAttribute()
    {
        $this->parser->setValidNameAttributeValues(array('valid', 'othervalid'));

        $actual = $this->parser->isValidNameAttribute(array('name' => 'NotValid', 'content' => 'My Content'));
        $this->assertFalse($actual);

        $actual = $this->parser->isValidNameAttribute(array('name' => 'valid', 'content' => 'Test description'));
        $this->assertTrue($actual);

        $actual = $this->parser->isValidNameAttribute(array('name' => 'othervalid', 'content' => 'Test description'));
        $this->assertTrue($actual);
    }

    public function testValidateRelAttribute()
    {
        $this->parser->setValidRelAttributeValues(array('valid', 'othervalid'));

        $actual = $this->parser->isValidRelAttribute(array('rel' => 'NotValid', 'content' => 'My Content'));
        $this->assertFalse($actual);

        $actual = $this->parser->isValidRelAttribute(array('rel' => 'valid', 'content' => 'Test description'));
        $this->assertTrue($actual);

        $actual = $this->parser->isValidRelAttribute(array('rel' => 'othervalid', 'content' => 'Test description'));
        $this->assertTrue($actual);
    }

    public function testValidateContentAttribute()
    {
        $actual = $this->parser->isValidContentAttribute(array());
        $this->assertFalse($actual);

        $actual = $this->parser->isValidContentAttribute(array('content' => ' '));
        $this->assertFalse($actual);

        $actual = $this->parser->isValidContentAttribute(array('content' => null));
        $this->assertFalse($actual);

        $actual = $this->parser->isValidContentAttribute(array('content' => 'Valid Content'));
        $this->assertTrue($actual);
    }

    /**
     * @dataProvider getHasOneValidAttributeAtLeastTestCases
     */
    public function testArrayHasOneUsefulTagsAtLeast($expected, $testData){
        $actual = $this->parser->hasOneUsefulAttributeAtLeast($testData);
        $this->assertEquals($expected, $actual);
    }

    public function testRemoveUselessTags()
    {
        $testData = array(
            array('rel' => null, 'name' => null, 'content' => null, 'property' => null),
            array('rel' => null, 'name' => null, 'content' => null, 'property' => 'article'),
            array('rel' => null, 'name' => 'description', 'content' => 'My Content', 'property' => null),
        );

        $actual = $this->parser->removeUselessTags($testData);

        $this->assertCount(1, $actual);
    }

    public function testRemoveTagsWithLessThanOneWord()
    {
        $testData = array('Too long tag', 'limit tag', 'tag', 'other longer tag for exclude');

        $actual = $this->parser->removeTagsSorterThanNWords($testData, 1);

        $this->assertCount(1, $actual);
        $this->assertTrue(in_array('tag', $actual));
        $this->assertFalse(in_array('limit tag', $actual));
        $this->assertFalse(in_array('Too long tag', $actual));
    }

    public function testRemoveTagsWithLessThanTwoWords()
    {
        $testData = array('Too long tag', 'limit tag', 'tag', 'other longer tag for exclude');

        $actual = $this->parser->removeTagsSorterThanNWords($testData, 2);

        $this->assertCount(2, $actual);
        $this->assertTrue(in_array('limit tag', $actual));
        $this->assertFalse(in_array('Too long tag', $actual));
    }

    public function getHasOneValidAttributeAtLeastTestCases()
    {
        return array(
            array(false, array('rel' => null, 'name' => null, 'content' => null, 'property' => null)),
            array(true, array('rel' => null, 'name' => null, 'content' => null, 'property' => 'article')),
            array(true, array('rel' => null, 'name' => 'description', 'content' => 'My Content', 'property' => null)),
        );
    }

}
