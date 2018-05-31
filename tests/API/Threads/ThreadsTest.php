<?php

namespace Tests\API;

use Tests\API\Threads\ThreadsAPITest;

class ThreadsTest extends ThreadsAPITest
{
    public function testThreads()
    {
        $this->assertGetThreadsWithoutCredentialsResponse();
        $this->assertGetRecommendationsWithoutCredentialsResponse();
        $this->assertGetOwnThreads();
        $this->assertEditThread();
        $this->assertCreateThread();
        $this->assertGetRecommendations();
        $this->assertDeleteThread();
        $this->assertValidationErrorsResponse();
    }

    protected function assertGetThreadsWithoutCredentialsResponse()
    {
        $response = $this->getThreads(null);
        $this->assertStatusCode($response, 401, "Get Threads without credentials");
    }

    protected function assertGetRecommendationsWithoutCredentialsResponse()
    {
        $threadId = $this->getFirstThreadId();
        $response = $this->getRecommendations($threadId, null);
        $this->assertStatusCode($response, 401, "Get Recommendations without credentials");
    }

    public function assertGetOwnThreads()
    {
        $response = $this->getThreads();
        $formattedResponse = $this->assertJsonResponse($response, 200, 'Getting own threads');

        $this->assertArrayOfType('array', $formattedResponse, 'Threads response is array of arrays');
        $this->assertArrayHasKey('items', $formattedResponse, 'Thread data has items');

        $threads = $formattedResponse['items'];
        $this->assertArrayOfType('array', $threads, 'Thread items in an array');
        $this->assertArrayHasKey(0, $threads, 'Exists first thread');
        $firstThread = $threads[0];

        $this->isType('array')->evaluate($firstThread, 'Thread data is an array');
        $this->assertArrayHasKey('name', $firstThread, 'Thread data has name');
        $this->assertArrayHasKey('category', $firstThread, 'Thread data has category');
        $this->assertArrayHasKey('filters', $firstThread, 'Thread data has filters');

        $filters = $firstThread['filters'];
        $this->isType('array')->evaluate($filters, 'Thread filters is array');
        $this->assertArrayHasKey('userFilters', $filters, 'Thread filters has userFilters');
        $this->isType('array')->evaluate($filters['userFilters'], 'Thread userFilters is array');
        $this->assertArrayOfType('array', $filters['userFilters'], 'Thread userFilters is array');
    }

    public function assertEditThread()
    {
        $threadId = $this->getFirstThreadId();
        $threadData = $this->getThreadEditData();
        $response = $this->editThread($threadData, $threadId);

        $formattedResponse = $this->assertJsonResponse($response, 200, 'Correctly editing thread');
        $this->assertArrayHasKey('name', $formattedResponse, 'Edit thread response has name key');
        $this->assertEquals('testing_thread', $formattedResponse['name'], "name is testing_thread");
        $this->assertArrayHasKey('id', $formattedResponse, 'Edit thread response has id key');
        $this->assertArrayHasKey('filters', $formattedResponse, 'Edit thread response has filters key');
        $this->assertArrayHasKey('userFilters', $formattedResponse['filters'], 'Edit thread response has filters["userFilters"] key');
        $this->assertArrayHasKey('descriptiveGender', $formattedResponse['filters']['userFilters'], 'Edit thread response has filters["userFilters"]["descriptiveGender"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['descriptiveGender'], 'Edit thread response has filters["userFilters"]["descriptiveGender"][0] key');
        $this->assertEquals('man', $formattedResponse['filters']['userFilters']['descriptiveGender'][0], 'filters["userFilters"]["descriptiveGender"][0] is equal to man');
        $this->assertArrayHasKey('birthday', $formattedResponse['filters']['userFilters'], 'Edit thread response has filters["userFilters"]["birthday"] key');
        $this->assertArrayHasKey('min', $formattedResponse['filters']['userFilters']['birthday'], 'Edit thread response has filters["userFilters"]["birthday"]["min"] key');
        $this->assertEquals(30, $formattedResponse['filters']['userFilters']['birthday']['min'], 'filters["userFilters"]["birthday"]["min"] is equal to 30');
        $this->assertArrayHasKey('max', $formattedResponse['filters']['userFilters']['birthday'], 'Edit thread response has filters["userFilters"]["birthday"]["max"] key');
        $this->assertEquals(40, $formattedResponse['filters']['userFilters']['birthday']['max'], 'filters["userFilters"]["birthday"]["max"] is equal to 40');

        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['language'], 'Create thread response has filters["userFilters"]["language"][0] key');
        $this->assertArrayHasKey('tag', $formattedResponse['filters']['userFilters']['language'][0], 'Create thread response has filters["userFilters"]["language"][0]["tag"] key');
        $this->assertEquals('English', $formattedResponse['filters']['userFilters']['language'][0]['tag']['name'], 'filters["userFilters"]["language"][0]["tag"]["name"] is equal to "English"');
        $this->assertArrayHasKey('choices', $formattedResponse['filters']['userFilters']['language'][0], 'Create thread response has filters["userFilters"]["language"][0]["choices"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['language'][0]['choices'], 'Create thread response has filters["userFilters"]["language"][0]["choices"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['language'][0]['choices'], 'Create thread response has filters["userFilters"]["language"][0]["choices"][1] key');
        $this->assertContains('full_professional', $formattedResponse['filters']['userFilters']['language'][0]['choices'], 'filters["userFilters"]["language"][0]["choices"][0] contains "full_professional"');
        $this->assertContains('professional_working', $formattedResponse['filters']['userFilters']['language'][0]['choices'], 'filters["userFilters"]["language"][0]["choices"][1] contains "professional_working"');
    }

    public function assertCreateThread()
    {
        $threadData = $this->getThreadCreateData();
        $response = $this->createThread($threadData);

        $formattedResponse = $this->assertJsonResponse($response, 201, 'Correctly creating thread');
        $this->assertArrayHasKey('name', $formattedResponse, 'Create thread response has name key');
        $this->assertEquals('testing_thread_2', $formattedResponse['name'], "name is testing_thread_2");
        $this->assertArrayHasKey('id', $formattedResponse, 'Create thread response has id key');
        $this->assertArrayHasKey('filters', $formattedResponse, 'Create thread response has filters key');
        $this->assertArrayHasKey('userFilters', $formattedResponse['filters'], 'Create thread response has filters["userFilters"] key');
        $this->assertArrayHasKey('descriptiveGender', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["descriptiveGender"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['descriptiveGender'], 'Create thread response has filters["userFilters"]["descriptiveGender"][0] key');
        $this->assertEquals('woman', $formattedResponse['filters']['userFilters']['descriptiveGender'][0], 'filters["userFilters"]["descriptiveGender"][0] is equal to woman');
        $this->assertArrayHasKey('civilStatus', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["civilStatus"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['civilStatus'], 'Create thread response has filters["userFilters"]["civilStatus"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['civilStatus'], 'Create thread response has filters["userFilters"]["civilStatus"][1] key');
        $this->assertContains('open-relationship', $formattedResponse['filters']['userFilters']['civilStatus'], 'filters["userFilters"]["civilStatus"] contains "open-relationship"');
        $this->assertContains('married', $formattedResponse['filters']['userFilters']['civilStatus'], 'filters["userFilters"]["civilStatus"] contains "married"');
        $this->assertArrayHasKey('language', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["language"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['language'], 'Create thread response has filters["userFilters"]["language"][0] key');
        $this->assertArrayHasKey('tag', $formattedResponse['filters']['userFilters']['language'][0], 'Create thread response has filters["userFilters"]["language"][0]["tag"] key');
        $this->assertEquals('English', $formattedResponse['filters']['userFilters']['language'][0]['tag']['name'], 'filters["userFilters"]["language"][0]["tag"]["name"] is equal to "English"');
        $this->assertArrayHasKey('choices', $formattedResponse['filters']['userFilters']['language'][0], 'Create thread response has filters["userFilters"]["language"][0]["choices"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['language'][0]['choices'], 'Create thread response has filters["userFilters"]["language"][0]["choices"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['language'][0]['choices'], 'Create thread response has filters["userFilters"]["language"][0]["choices"][1] key');
        $this->assertContains('full_professional', $formattedResponse['filters']['userFilters']['language'][0]['choices'], 'filters["userFilters"]["language"][0]["choices"][0] contains "full_professional"');
        $this->assertContains('professional_working', $formattedResponse['filters']['userFilters']['language'][0]['choices'], 'filters["userFilters"]["language"][0]["choices"][1] contains "professional_working"');
        $this->assertArrayHasKey('zodiacSign', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["zodiacSign"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['zodiacSign'], 'Create thread response has filters["userFilters"]["zodiacSign"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['zodiacSign'], 'Create thread response has filters["userFilters"]["zodiacSign"][1] key');
        $this->assertContains('sagittarius', $formattedResponse['filters']['userFilters']['zodiacSign'], 'filters["userFilters"]["zodiacSign"] contains "sagittarius"');
        $this->assertContains('scorpio', $formattedResponse['filters']['userFilters']['zodiacSign'], 'filters["userFilters"]["zodiacSign"] contains "scorpio"');
        $this->assertArrayHasKey('compatibility', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["compatibility"] key');
        $this->assertEquals(51, $formattedResponse['filters']['userFilters']['compatibility'], 'filters["userFilters"]["compatibility"] is equal to 51');
        $this->assertArrayHasKey('similarity', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["similarity"] key');
        $this->assertEquals(52, $formattedResponse['filters']['userFilters']['similarity'], 'filters["userFilters"]["similarity"] is equal to 52');
        $this->assertArrayHasKey('complexion', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["complexion"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['complexion'], 'Create thread response has filters["userFilters"]["complexion"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['complexion'], 'Create thread response has filters["userFilters"]["complexion"][1] key');
        $this->assertArrayHasKey(2, $formattedResponse['filters']['userFilters']['complexion'], 'Create thread response has filters["userFilters"]["complexion"][2] key');
        $this->assertContains('fat', $formattedResponse['filters']['userFilters']['complexion'], 'filters["userFilters"]["complexion"] contains "fat"');
        $this->assertContains('slim', $formattedResponse['filters']['userFilters']['complexion'], 'filters["userFilters"]["complexion"] contains "slim"');
        $this->assertContains('normal', $formattedResponse['filters']['userFilters']['complexion'], 'filters["userFilters"]["complexion"] contains "normal"');
        $this->assertArrayHasKey('orientation', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["orientation"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['orientation'], 'Create thread response has filters["userFilters"]["orientation"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['orientation'], 'Create thread response has filters["userFilters"]["orientation"][1] key');
        $this->assertArrayHasKey(2, $formattedResponse['filters']['userFilters']['orientation'], 'Create thread response has filters["userFilters"]["orientation"][2] key');
        $this->assertContains('heterosexual', $formattedResponse['filters']['userFilters']['orientation'], 'filters["userFilters"]["orientation"] contains "heterosexual"');
        $this->assertContains('homosexual', $formattedResponse['filters']['userFilters']['orientation'], 'filters["userFilters"]["orientation"] contains "homosexual"');
        $this->assertContains('bisexual', $formattedResponse['filters']['userFilters']['orientation'], 'filters["userFilters"]["orientation"] contains "bisexual"');
        $this->assertArrayHasKey('relationshipInterest', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["relationshipInterest"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['relationshipInterest'], 'Create thread response has filters["userFilters"]["relationshipInterest"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['relationshipInterest'], 'Create thread response has filters["userFilters"]["relationshipInterest"][1] key');
        $this->assertContains('friendship', $formattedResponse['filters']['userFilters']['relationshipInterest'], 'filters["userFilters"]["relationshipInterest"] contains "heterosexual"');
        $this->assertContains('relation', $formattedResponse['filters']['userFilters']['relationshipInterest'], 'filters["userFilters"]["relationshipInterest"] contains "homosexual"');
        $this->assertArrayHasKey('eyeColor', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["eyeColor"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['eyeColor'], 'Create thread response has filters["userFilters"]["eyeColor"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['eyeColor'], 'Create thread response has filters["userFilters"]["eyeColor"][1] key');
        $this->assertArrayHasKey(2, $formattedResponse['filters']['userFilters']['eyeColor'], 'Create thread response has filters["userFilters"]["eyeColor"][2] key');
        $this->assertArrayHasKey(3, $formattedResponse['filters']['userFilters']['eyeColor'], 'Create thread response has filters["userFilters"]["eyeColor"][3] key');
        $this->assertContains('blue', $formattedResponse['filters']['userFilters']['eyeColor'], 'filters["userFilters"]["eyeColor"] contains "blue"');
        $this->assertContains('black', $formattedResponse['filters']['userFilters']['eyeColor'], 'filters["userFilters"]["eyeColor"] contains "black"');
        $this->assertContains('brown', $formattedResponse['filters']['userFilters']['eyeColor'], 'filters["userFilters"]["eyeColor"] contains "brown"');
        $this->assertContains('green', $formattedResponse['filters']['userFilters']['eyeColor'], 'filters["userFilters"]["eyeColor"] contains "green"');
        $this->assertArrayHasKey('hairColor', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["hairColor"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['hairColor'], 'Create thread response has filters["userFilters"]["hairColor"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['hairColor'], 'Create thread response has filters["userFilters"]["hairColor"][1] key');
        $this->assertArrayHasKey(2, $formattedResponse['filters']['userFilters']['hairColor'], 'Create thread response has filters["userFilters"]["hairColor"][2] key');
        $this->assertContains('black', $formattedResponse['filters']['userFilters']['hairColor'], 'filters["userFilters"]["eyeColor"] contains "black"');
        $this->assertContains('blond', $formattedResponse['filters']['userFilters']['hairColor'], 'filters["userFilters"]["eyeColor"] contains "blond"');
        $this->assertContains('brown', $formattedResponse['filters']['userFilters']['hairColor'], 'filters["userFilters"]["eyeColor"] contains "brown"');
        $this->assertArrayHasKey('alcohol', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["alcohol"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['alcohol'], 'Create thread response has filters["userFilters"]["alcohol"][0] key');
        $this->assertContains('occasionally', $formattedResponse['filters']['userFilters']['alcohol'], 'filters["userFilters"]["alcohol"] contains "black"');
        $this->assertArrayHasKey('drugs', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["drugs"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['drugs'], 'Create thread response has filters["userFilters"]["drugs"][0] key');
        $this->assertContains('cannabis', $formattedResponse['filters']['userFilters']['drugs'], 'filters["userFilters"]["drugs"] contains "cannabis"');
        $this->assertContains('stimulants', $formattedResponse['filters']['userFilters']['drugs'], 'filters["userFilters"]["drugs"] contains "stimulants"');
        $this->assertArrayHasKey('ethnicGroup', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["ethnicGroup"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['ethnicGroup'], 'Create thread response has filters["userFilters"]["ethnicGroup"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['ethnicGroup'], 'Create thread response has filters["userFilters"]["ethnicGroup"][1] key');
        $this->assertArrayHasKey(2, $formattedResponse['filters']['userFilters']['ethnicGroup'], 'Create thread response has filters["userFilters"]["ethnicGroup"][2] key');
        $this->assertContains('oriental', $formattedResponse['filters']['userFilters']['ethnicGroup'], 'filters["userFilters"]["ethnicGroup"] contains "oriental"');
        $this->assertContains('afro-american', $formattedResponse['filters']['userFilters']['ethnicGroup'], 'filters["userFilters"]["ethnicGroup"] contains "afro-american"');
        $this->assertContains('caucasian', $formattedResponse['filters']['userFilters']['ethnicGroup'], 'filters["userFilters"]["ethnicGroup"] contains "caucasian"');
        $this->assertArrayHasKey('height', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["height"] key');
        $this->assertArrayHasKey('max', $formattedResponse['filters']['userFilters']['height'], 'Create thread response has filters["userFilters"]["height"]["max"] key');
        $this->assertArrayHasKey('min', $formattedResponse['filters']['userFilters']['height'], 'Create thread response has filters["userFilters"]["height"]["min"] key');
        $this->assertEquals(190, $formattedResponse['filters']['userFilters']['height']['max'], 'filters["userFilters"]["ethnicGroup"]["max"] is equal to 190');
        $this->assertEquals(160, $formattedResponse['filters']['userFilters']['height']['min'], 'filters["userFilters"]["ethnicGroup"]["min"] is equal to 160');
        $this->assertArrayHasKey('income', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["income"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['income'], 'Create thread response has filters["userFilters"]["income"][0] key');
        $this->assertContains('between-us-12-000-and-us-24-000-year', $formattedResponse['filters']['userFilters']['income'], 'filters["userFilters"]["income"] contains "between-us-12-000-and-us-24-000-year"');
        $this->assertArrayHasKey('interfaceLanguage', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["interfaceLanguage"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['interfaceLanguage'], 'Create thread response has filters["userFilters"]["interfaceLanguage"][0] key');
        $this->assertContains('es', $formattedResponse['filters']['userFilters']['interfaceLanguage'], 'filters["userFilters"]["interfaceLanguage"] contains "es"');
        $this->assertArrayHasKey('religion', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["religion"] key');
        $this->assertArrayHasKey('choices', $formattedResponse['filters']['userFilters']['religion'], 'Create thread response has filters["userFilters"]["religion"]["choices"] key');
        $this->assertArrayHasKey('details', $formattedResponse['filters']['userFilters']['religion'], 'Create thread response has filters["userFilters"]["religion"]["details"] key');
        $this->assertContains('agnosticism', $formattedResponse['filters']['userFilters']['religion']['choices'], 'filters["userFilters"]["religion"]["choices"] contains "agnosticism"');
        $this->assertContains('atheism', $formattedResponse['filters']['userFilters']['religion']['choices'], 'filters["userFilters"]["religion"]["choices"] contains "atheism"');
        $this->assertContains('not_important', $formattedResponse['filters']['userFilters']['religion']['details'], 'filters["userFilters"]["religion"]["details"] contains "not_important"');
        $this->assertContains('laughing_about_it', $formattedResponse['filters']['userFilters']['religion']['details'], 'filters["userFilters"]["religion"]["details"] contains "laughing_about_it"');
        $this->assertArrayNotHasKey(3, $formattedResponse['filters']['userFilters']['religion'], 'Create thread response has not filters["userFilters"]["religion"][3] key');
        $this->assertArrayHasKey('sons', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["sons"] key');
        $this->assertArrayHasKey('choice', $formattedResponse['filters']['userFilters']['sons'], 'Create thread response has filters["userFilters"]["sons"]["choice"] key');
        $this->assertArrayHasKey('details', $formattedResponse['filters']['userFilters']['sons'], 'Create thread response has filters["userFilters"]["sons"]["details"] key');
        $this->assertEquals('no', $formattedResponse['filters']['userFilters']['sons']['choice'], 'filters["userFilters"]["sons"]["choice"] is equal to "no"');
        $this->assertContains('not_want', $formattedResponse['filters']['userFilters']['sons']['details'], 'filters["userFilters"]["sons"]["details"] contains "not_want"');
        $this->assertArrayHasKey('pets', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["pets"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['pets'], 'Create thread response has filters["userFilters"]["pets"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['pets'], 'Create thread response has filters["userFilters"]["pets"][1] key');
        $this->assertContains('cat', $formattedResponse['filters']['userFilters']['pets'], 'filters["userFilters"]["pets"] contains "cat');
        $this->assertContains('dog', $formattedResponse['filters']['userFilters']['pets'], 'filters["userFilters"]["pets"] contains "dog"');
        $this->assertArrayHasKey('diet', $formattedResponse['filters']['userFilters'], 'Create thread response has filters["userFilters"]["diet"] key');
        $this->assertArrayHasKey(0, $formattedResponse['filters']['userFilters']['diet'], 'Create thread response has filters["userFilters"]["diet"][0] key');
        $this->assertArrayHasKey(1, $formattedResponse['filters']['userFilters']['diet'], 'Create thread response has filters["userFilters"]["diet"][1] key');
        $this->assertContains('vegan', $formattedResponse['filters']['userFilters']['diet'], 'filters["userFilters"]["pets"] contains "vegan');
        $this->assertContains('vegetarian', $formattedResponse['filters']['userFilters']['diet'], 'filters["userFilters"]["pets"] contains "vegetarian"');
    }

    public function assertGetRecommendations()
    {
        $threadId = $this->getFirstThreadId();
        $response = $this->getRecommendations($threadId);
        $formattedResponse = $this->assertJsonResponse($response, 200, 'Correctly get recommendation from created thread');
        $this->assertArrayHasKey('items', $formattedResponse, 'Recommendation list has items key');
        $this->assertArrayOfType('array', $formattedResponse['items'], 'Recommendation items is an array of arrays');

        foreach ($formattedResponse['items'] as $recommendation)
        {
            $this->assertArrayHasKey('topLinks', $recommendation, 'Each recommendation has topLinks key');
            $this->assertArrayOfType('strings', $recommendation['topLinks'], 'Each topLinks is an array of strings');
        }
    }

    public function assertDeleteThread()
    {
        $threads = $this->getThreads();
        $formattedThreads = $this->assertJsonResponse($threads);
        $items = $formattedThreads['items'];
        $this->assertCount(2, $items, '2 threads exist before delete');

        $threadId = $this->getFirstThreadId();
        $this->deleteThread($threadId);

        $threads = $this->getThreads();
        $formattedThreads = $this->assertJsonResponse($threads);
        $items = $formattedThreads['items'];
        $this->assertCount(1, $items, '1 thread exists after delete');
    }

    protected function assertValidationErrorsResponse()
    {
        $threadData = $this->getThreadCreateDataWithUndefinedDescriptiveGender();
        $response = $this->createThread($threadData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Thread with descriptive gender error");
        $this->assertDescriptiveGenderValidationErrorFormat($formattedResponse);

        $threadData = $this->getThreadCreateDataWithUndefinedLanguageChoice();
        $response = $this->createThread($threadData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Thread with language choice error");
        $this->assertLanguageValidationErrorFormat($formattedResponse);

        $threadData = $this->getThreadCreateDataWithUndefinedZodiacChoice();
        $response = $this->createThread($threadData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Thread with zodiac sign choice error");
        $this->assertZodiacValidationErrorFormat($formattedResponse);
    }

    protected function getFirstThreadId()
    {
        $threads = $this->getThreads();
        $formattedThreads = $this->assertJsonResponse($threads);
        $thread = $formattedThreads['items'][0];
        return $thread['id'];
    }

    protected function assertDescriptiveGenderValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('descriptiveGender', $exception['validationErrors'], "Thread error has not descriptiveGender key");
        $this->assertContains('Option with value "undefined" is not valid, possible values are', $exception['validationErrors']['descriptiveGender'][0], "descriptiveGender key is not valid format.");
    }

    protected function assertLanguageValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('language', $exception['validationErrors'], "Thread error has not language key");
        $this->assertContains('Option with value "undefined" is not valid, possible values are', $exception['validationErrors']['language'][0], "language key is not valid format.");
    }

    protected function assertZodiacValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('zodiacSign', $exception['validationErrors'], "Thread error has not zodiacSign key");
        $this->assertContains('Option with value "undefined" is not valid, possible values are', $exception['validationErrors']['zodiacSign'][0], "zodiacSign key is not valid format.");
    }

    protected function getThreadEditData()
    {
        return array(
            'name' => 'testing_thread',
            'category' => 'ThreadUsers',
            'filters' => array(
                'userFilters' => array(
                    'descriptiveGender' => array('man'),
                    'birthday' => array(
                        'max' => 40,
                        'min' => 30,
                    ),
                    'language' => array(
                        array(
                            'tag' => array(
                                'name' => 'English'
                            ),
                            'choices' => array(
                                'full_professional',
                                'professional_working'
                            )
                        )
                    ),
                ),
            ),
        );
    }

    protected function getThreadCreateData()
    {
        return array(
            'name' => 'testing_thread_2',
            'category' => 'ThreadUsers',
            'filters' => array(
                'userFilters' => array(
                    'descriptiveGender' => array('woman'),
                    'civilStatus' => array('married', 'open-relationship'),
                    'language' => array(
                        array(
                            'tag' => array(
                                'name' => 'English'
                            ),
                            'choices' => array(
                                'full_professional',
                                'professional_working'
                            )
                        )
                    ),
                    'zodiacSign' => array(
                        'sagittarius',
                        'scorpio',
                    ),
                    'location' => array(
                        'distance' => 100,
                        'location' => array(
                            "locality" => "Madrid",
                            "address" => "Madrid",
                            "country" => "Spain",
                            "longitude" => -3.7037902,
                            "latitude" => 40.4167754
                        )
                    ),
                    'compatibility' => 51,
                    'complexion' => array(
                        'fat',
                        'normal',
                        'slim',
                    ),
                    'orientation' => array(
                        'heterosexual',
                        'homosexual',
                        'bisexual',
                    ),
                    'relationshipInterest' => array(
                        'friendship',
                        'relation',
                    ),
                    'eyeColor' => array(
                        'blue',
                        'black',
                        'brown',
                        'green',
                    ),
                    'hairColor' => array(
                        'black',
                        'blond',
                        'brown',
                    ),
                    'similarity' => 52,
                    'alcohol' => array(
                        'occasionally',
                    ),
                    'drugs' => array(
                        'cannabis',
                        'stimulants',
                    ),
                    'ethnicGroup' => array(
                        'oriental',
                        'afro-american',
                        'caucasian'
                    ),
                    'height' => array(
                        'max' => 190,
                        'min' => 160,
                    ),
                    'income' => array(
                        'between-us-12-000-and-us-24-000-year',
                    ),
                    'interfaceLanguage' => array(
                        'es',
                    ),
                    'religion' => array(
                        'choices' => array(
                            'agnosticism',
                            'atheism',
                        ),
                        'details' => array(
                            'not_important',
                            'laughing_about_it',
                        )
                    ),
                    'sons' => array(
                        'choice' => 'no',
                        'details' => array('not_want'),
                    ),
                    'pets' => array(
                        'cat',
                        'dog'
                    ),
                    'diet' => array(
                        'vegan',
                        'vegetarian'
                    )
                ),
            ),
        );
    }

    protected function getThreadCreateDataWithUndefinedDescriptiveGender()
    {
        return array(
            'name' => 'testing_thread_3',
            'category' => 'ThreadUsers',
            'filters' => array(
                'userFilters' => array(
                    'descriptiveGender' => array('undefined'),
                    'civilStatus' => array('married', 'open-relationship'),
                    'language' => array(
                        array(
                            'tag' => array(
                                'name' => 'English'
                            ),
                            'choices' => array(
                                'full_professional',
                                'professional_working'
                            )
                        )
                    )
                ),
            ),
        );
    }

    protected function getThreadCreateDataWithUndefinedLanguageChoice()
    {
        return array(
            'name' => 'testing_thread_3',
            'category' => 'ThreadUsers',
            'filters' => array(
                'userFilters' => array(
                    'descriptiveGender' => array('man'),
                    'civilStatus' => array('married', 'open-relationship'),
                    'language' => array(
                        array(
                            'tag' => array(
                                'name' => 'English'
                            ),
                            'choices' => array(
                                'full_professional',
                                'undefined'
                            )
                        )
                    )
                ),
            ),
        );
    }

    protected function getThreadCreateDataWithUndefinedZodiacChoice()
    {
        return array(
            'name' => 'testing_thread_2',
            'category' => 'ThreadUsers',
            'filters' => array(
                'userFilters' => array(
                    'descriptiveGender' => array('woman'),
                    'civilStatus' => array('married', 'open-relationship'),
                    'language' => array(
                        array(
                            'tag' => array(
                                'name' => 'English'
                            ),
                            'choices' => array(
                                'full_professional',
                                'professional_working'
                            )
                        )
                    ),
                    'zodiacSign' => array(
                        'sagittarius',
                        'scorpio',
                        'undefined'
                    )
                ),
            ),
        );
    }
}