<?php

/*
 * Test the /api/categories Api
 *
 * Command to run: $ phpunit src/Pilotabai/CompetitionDbBundle/Tests/Controller/Api/CategoryControllerTest.php
 * Or with a filter: $ phpunit src/Pilotabai/CompetitionDbBundle/Tests/Controller/Api/CategoryControllerTest.php --filter testPUTCategory
 * 
 */
namespace Pilotabai\CompetitionDbBundle\Tests\Controller\Api;

use GuzzleHttp\Psr7\Response;
use Pilotabai\CompetitionDbBundle\Tests\ApiTestCase;

class CategoryControllerTest extends ApiTestCase
{
    private $username = 'nicolaspb';

    public function testPOSTCategoryWorks()
    {
        $website = 'ffpb';
        $competitionValue = 20150101;
        $specialityValue = 22;
        $levelValue = 3;
        $data = array(
            "website" => $website,
            "competitionValue" => $competitionValue,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue,
            "competition" => "Test: A competition test name",
            "speciality" => "Test: A speciality test name",
            "level" => "Test: A level test name"
        );

//        $token = $this->getService('lexik_jwt_authentication.encoder')
//            ->encode(['username' => 'nicolaspb']);
        
        $response = $this->client->post('api/categories', [
            'body' => json_encode($data),
//            'headers' => [
//                'Authorization' => 'Bearer '.$token
//            ]
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertContains("/api/categories/1", $response->getHeader('Location')[0]);
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'website',
            'competition',
            'speciality',
            'level',
            'competitionValue',
            'specialityValue',
            'levelValue'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'website', $website);
        $this->asserter()->assertResponsePropertyEquals($response, 'competitionValue', $competitionValue);
        $this->asserter()->assertResponsePropertyEquals($response, 'specialityValue', $specialityValue);
        $this->asserter()->assertResponsePropertyEquals($response, 'levelValue', $levelValue);

        $response = $this->client->post('api/categories', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors',
        ));
        $this->asserter()->assertResponsePropertyContains($response, 'type', 'validation_error');
        $this->asserter()->assertResponsePropertyEquals($response, 'errors[0]', 'This category is already saved');
    }

    public function testGETCategory()
    {
        $website = 'websiteTest';
        $competitionValue = 20150101;
        $specialityValue = 22;
        $levelValue = 3;
        $data = array(
            "website" => $website,
            "competitionValue" => $competitionValue,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue
        );
        $category = $this->createCategory($data);

        $response = $this->client->get("/competitiondb/web/app_test.php/api/categories/".$category->getId(), [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'website',
            'competition',
            'speciality',
            'level',
            'competitionValue',
            'specialityValue',
            'levelValue',
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'id', $category->getId());
        $this->asserter()->assertResponsePropertyEquals($response, 'website', $website);
        $this->asserter()->assertResponsePropertyEquals($response, 'competitionValue', $competitionValue);
        $this->asserter()->assertResponsePropertyEquals($response, 'specialityValue', $specialityValue);
        $this->asserter()->assertResponsePropertyEquals($response, 'levelValue', $levelValue);
        $this->asserter()->assertResponsePropertyContains($response, '_links.self', '/api/categories/'.$category->getId());
        $this->asserter()->assertResponsePropertyContains($response, '_links.games', '/api/games?categoryFilter='.$category->getId());
    }

    public function testGETCategoryCollectionPaginated()
    {
        $data = array(
            "website" => 'ub',
            "competitionValue" => 123,
            "specialityValue" => 456,
            "levelValue" => 789
        );
        $category = $this->createCategory($data);

        $website = 'ffpb';
        $competitionValue = 64;
        $specialityValue = 1;
        $levelValue = 3;

        for ($i = 0; $i < 125; $i++) {
            $data = array(
                "website" => $website,
                "competitionValue" => $competitionValue,
                "specialityValue" => $specialityValue + $i,
                "levelValue" => $levelValue
            );
            $this->createCategory($data);
        }

        $data = array(
            "website" => $website,
            "competitionValue" => $competitionValue+1,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue
        );
        $category = $this->createCategory($data);

        // page 1
        $response = $this->client->get('api/categories?websiteFilter='.$website.'&competitionValFilter='.$competitionValue, [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'items'
        ));
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'items[5].specialityValue',
            6
        );
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 50);
        $this->asserter()->assertResponsePropertyEquals($response, 'total', 125);
        $this->asserter()->assertResponsePropertyExists($response, '_links.next');
        // page 2
        $nextLink = $this->asserter()->readResponseProperty($response, '_links.next');
        $response = $this->client->get($nextLink, [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'items[5].specialityValue',
            56
        );
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 50);
        // last page
        $lastLink = $this->asserter()->readResponseProperty($response, '_links.last');
        $response = $this->client->get($lastLink, [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'items[24].specialityValue',
            125
        );
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'items[25].website');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 25);
    }

    public function testGETCategoryCollection()
    {
        $website = 'websiteTest';
        $competitionValue = 20150101;
        $specialityValue = 22;
        $levelValue = 3;
        $data = array(
            "website" => $website,
            "competitionValue" => $competitionValue,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue
        );
        $category0 = $this->createCategory($data);
        $website = 'websiteTest2';
        $competitionValue = 20160101;
        $specialityValue = 21;
        $levelValue = 5;
        $data = array(
            "website" => $website,
            "competitionValue" => $competitionValue,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue
        );
        $category1 = $this->createCategory($data);

        $response = $this->client->get('api/categories',[
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'items'
        ));
        $this->asserter()->assertResponsePropertyIsArray($response, 'items');
        $this->asserter()->assertResponsePropertyCount($response, 'items', 2);
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].website', $category0->getWebsite());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].competitionValue', $category0->getCompetitionValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].specialityValue', $category0->getSpecialityValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].levelValue', $category0->getLevelValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].website', $category1->getWebsite());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].competitionValue', $category1->getCompetitionValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].specialityValue', $category1->getSpecialityValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].levelValue', $category1->getLevelValue());
    }

    public function testPUTCategory()
    {
        $website = 'ffpb';
        $competitionValue = 20150101;
        $specialityValue = 22;
        $levelValue = 3;
        $data = array(
            "website" => $website,
            "competitionValue" => $competitionValue,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue
        );
        $category = $this->createCategory($data);

        $website = 'ffpb';
        $competitionValue = 20159999;
        $specialityValue = 20;
        $levelValue = 3;
        $competition = "A new comp name";
        $speciality = "A new spec name";
        $level = $category->getLevel();
        $data = array(
            "website" => $website,
            "competition" => $competition,
            "speciality" => $speciality,
            "level" => $level,
            "competitionValue" => $competitionValue,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue
        );

        $response = $this->client->put('api/categories/'.$category->getId(), [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'website',
            'competition',
            'speciality',
            'level',
            'competitionValue',
            'specialityValue',
            'levelValue',
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'website', $category->getWebsite());
        $this->asserter()->assertResponsePropertyEquals($response, 'competitionValue', $category->getCompetitionValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'competition', $competition);
        $this->asserter()->assertResponsePropertyEquals($response, 'specialityValue', $category->getSpecialityValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'speciality', $speciality);
        $this->asserter()->assertResponsePropertyEquals($response, 'levelValue', $category->getLevelValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'level', $level);
    }

    public function testPATCHCategory()
    {
        $website = 'ffpb';
        $competitionValue = 20150101;
        $specialityValue = 22;
        $levelValue = 3;
        $data = array(
            "website" => $website,
            "competitionValue" => $competitionValue,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue
        );
        $category = $this->createCategory($data);

        $competition = "A new comp name";
        $speciality = "A new spec name";
        $data = array(
            "competition" => $competition,
            "speciality" => $speciality,
        );

        $response = $this->client->patch('api/categories/'.$category->getId(), [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'website',
            'competition',
            'speciality',
            'level',
            'competitionValue',
            'specialityValue',
            'levelValue',
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'website', $category->getWebsite());
        $this->asserter()->assertResponsePropertyEquals($response, 'competitionValue', $category->getCompetitionValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'competition', $competition);
        $this->asserter()->assertResponsePropertyEquals($response, 'specialityValue', $category->getSpecialityValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'speciality', $speciality);
        $this->asserter()->assertResponsePropertyEquals($response, 'levelValue', $category->getLevelValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'level', $category->getLevel());
    }

    public function testDELETECategory()
    {
        $website = 'ffpb';
        $competitionValue = 20150101;
        $specialityValue = 22;
        $levelValue = 3;
        $data = array(
            "website" => $website,
            "competitionValue" => $competitionValue,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue
        );
        $category = $this->createCategory($data);

        $response = $this->client->delete("/competitiondb/web/app_test.php/api/categories/".$category->getId(), array(
            'headers' => $this->getAuthorizedHeaders($this->username)
        ));
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testValidationErrors()
    {
        $website = 'ffpb';
        $competitionValue = 20150101;
        $specialityValue = 22;
        $levelValue = 3;
        $data = array(
//            "website" => $website,
            "competition" => "Test: A competition test name",
//            "speciality" => "Test: A speciality test name",
            "level" => "Test: A level test name",
            "competitionValue" => $competitionValue,
//            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue
        );

        $response = $this->client->post('api/categories', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors',
        ));
        $this->asserter()->assertResponsePropertyContains($response, 'type', 'validation_error');
        $this->asserter()->assertResponsePropertyExists($response, 'errors.website');
        $this->asserter()->assertResponsePropertyExists($response, 'errors.specialityValue');
        $this->asserter()->assertResponsePropertyEquals($response, 'errors.website[0]', 'Please enter a website');
        $this->asserter()->assertResponsePropertyEquals($response, 'errors.specialityValue[0]', 'Please enter a specialityValue');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.competitionValue');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.levelValue');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.speciality');
    }

    public function testInvalidJson()
    {
        $invalidBody = <<<EOF
{
	"website": "websitetest"
	"competition": "Competition Name 123",
	"speciality": "Speciality Name
	"level": "Level Name 123",
	"competitionValue": 98765,
	"specialityValue": 25,
	"levelValue": 6
}
EOF;

        $response = $this->client->post('api/categories', [
            'body' => $invalidBody,
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title'
        ));
        $this->asserter()->assertResponsePropertyContains($response, 'type', 'invalid_body_format');
    }

    public function test404Exception()
    {
        $response = $this->client->get('api/categories/fake', [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Not Found');
        $this->asserter()->assertResponsePropertyEquals($response, 'detail', 'No route found for "GET /api/categories/fake"');

        $response = $this->client->get('api/categories/999', [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Not Found');
        $this->asserter()->assertResponsePropertyEquals($response, 'detail', 'No category found with id "999"');
    }

    public function testRequiresAuthentication()
    {
        $response = $this->client->post('api/categories', [
            'body' => '[]'
        ]);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testBadToken()
    {
        $response = $this->client->post('api/categories', [
            'body' => '[]',
            'headers' => [
                'Authorization' => 'Bearer WRONG'
            ]
        ]);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
//        $this->debugResponse($response);
    }
}