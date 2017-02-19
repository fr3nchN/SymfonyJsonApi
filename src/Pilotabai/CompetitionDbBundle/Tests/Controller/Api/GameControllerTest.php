<?php

/*
 * Test the /api/games Api
 *
 * Command to run: $ phpunit src/Pilotabai/CompetitionDbBundle/Tests/Controller/Api/GameControllerTest.php
 * Or with a filter: $ phpunit src/Pilotabai/CompetitionDbBundle/Tests/Controller/Api/GameControllerTest.php --filter testPUTGame
 * 
 */
namespace Pilotabai\CompetitionDbBundle\Tests\Controller\Api;

use GuzzleHttp\Psr7\Response;
use Pilotabai\CompetitionDbBundle\Entity\Category;
use Pilotabai\CompetitionDbBundle\Entity\Game;
use Pilotabai\CompetitionDbBundle\Tests\ApiTestCase;

class GameControllerTest extends ApiTestCase
{
    private $username = 'nicolaspb';

    /**
     * @var Category
     */
    private $category;

    protected function setUp()
    {
        parent::setUp();
        $website = 'website1';
        $competitionValue = 1222;
        $specialityValue = 1333;
        $levelValue = 1444;
        $data = array(
            "website" => $website,
            "competitionValue" => $competitionValue,
            "specialityValue" => $specialityValue,
            "levelValue" => $levelValue,
            "competition" => "Test: A competition test name",
            "speciality" => "Test: A speciality test name",
            "level" => "Test: A level test name"
        );

        $this->category = $this->createCategory($data);
    }

    public function testPOSTGameWorks()
    {
        $rencontre = 12345;
        $phase = 'finale';
        $data = array(
            "rencontre" => $rencontre,
            "phase" => $phase,
            "category" => $this->category->getId()
        );

        $response = $this->client->post('api/games', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertContains("/api/games/1", $response->getHeader('Location')[0]);
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'rencontre',
            'phase',
            'category'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'rencontre', $rencontre);
        $this->asserter()->assertResponsePropertyEquals($response, 'phase', $phase);
        $this->asserter()->assertResponsePropertyEquals($response, 'category.website', $this->category->getWebsite());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.competitionValue', $this->category->getCompetitionValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.specialityValue', $this->category->getSpecialityValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.levelValue', $this->category->getLevelValue());

        $response = $this->client->post('api/games', [
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
        $this->asserter()->assertResponsePropertyEquals($response, 'errors[0]', 'This game is already saved');
    }

    public function testGETGame()
    {
        $rencontre = 12345;
        $phase = 'finale';
        $data = array(
            "rencontre" => $rencontre,
            "phase" => $phase,
            "category" => $this->category->getId()
        );

        $game = $this->createGame($data, $this->category);

        $response = $this->client->get("api/games/".$game->getId(), [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'rencontre',
            'phase',
            'category'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'id', $game->getId());
        $this->asserter()->assertResponsePropertyEquals($response, 'rencontre', $rencontre);
        $this->asserter()->assertResponsePropertyEquals($response, 'phase', $phase);
        $this->asserter()->assertResponsePropertyEquals($response, 'category.website', $this->category->getWebsite());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.competitionValue', $this->category->getCompetitionValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.specialityValue', $this->category->getSpecialityValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.levelValue', $this->category->getLevelValue());
        $this->asserter()->assertResponsePropertyContains($response, '_links.self', '/api/games/'.$game->getId());
        $this->asserter()->assertResponsePropertyContains($response, '_links.category', '/api/categories/'.$game->getCategoryId());
    }

    public function testGETGameCollectionPaginated()
    {
        $games = array();

        $rencontre = 12345;
        $phase = 'quart';
        $game = new Game();
        $game->setPhase($phase);
        $game->setCategory($this->category);
        $game->setRencontre($rencontre);
        $games[] = $game;

        $rencontre = 1;
        $phaseFilter = "poules";
        for ($i = 0; $i < 125; $i++) {
            $game = new Game();
            $game->setPhase($phaseFilter);
            $game->setCategory($this->category);
            $game->setRencontre($rencontre + $i);
            $games[] = $game;
        }

        $rencontre = 777;
        $phase = 'finale';
        $game = new Game();
        $game->setPhase($phase);
        $game->setCategory($this->category);
        $game->setRencontre($rencontre);
        $games[] = $game;

        $this->createGames($games);

        // page 1
        $response = $this->client->get('api/games?categoryFilter='.$this->category->getId().'&phaseFilter='.$phaseFilter, [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'items'
        ));
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'items[5].rencontre',
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
            'items[5].rencontre',
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
            'items[24].rencontre',
            125
        );
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'items[25].website');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 25);
    }

    public function testGETGameCollection()
    {
        $rencontre = 321;
        $phase = 'quart';
        $data = array(
            "rencontre" => $rencontre,
            "phase" => $phase,
            "category" => $this->category->getId()
        );
        $game0 = $this->createGame($data, $this->category);
        $rencontre = 654;
        $phase = 'demi';
        $data = array(
            "rencontre" => $rencontre,
            "phase" => $phase,
            "category" => $this->category->getId()
        );
        $game1 = $this->createGame($data, $this->category);

        $response = $this->client->get('api/games',[
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'items'
        ));
        $this->asserter()->assertResponsePropertyIsArray($response, 'items');
        $this->asserter()->assertResponsePropertyCount($response, 'items', 2);
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].rencontre', $game0->getRencontre());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].phase', $game0->getPhase());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[0].category.id', $game0->getCategory()->getId());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].rencontre', $game1->getRencontre());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].phase', $game1->getPhase());
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].category.id', $game1->getCategory()->getId());
    }

    public function testPUTGame()
    {
        $rencontre = 321;
        $phase = 'quart';
        $data = array(
            "rencontre" => $rencontre,
            "phase" => $phase,
            "category" => $this->category->getId()
        );
        $game = $this->createGame($data, $this->category);

        $rencontre = 321;
        $phase = 'huitième';
        $data = array(
            "rencontre" => $rencontre,
            "phase" => $phase,
            "category" => $this->category->getId()
        );

        $response = $this->client->put('api/games/'.$game->getId(), [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'rencontre',
            'phase',
            'category'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'rencontre', $rencontre);
        $this->asserter()->assertResponsePropertyEquals($response, 'phase', $phase);
        $this->asserter()->assertResponsePropertyEquals($response, 'category.website', $this->category->getWebsite());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.competitionValue', $this->category->getCompetitionValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.specialityValue', $this->category->getSpecialityValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.levelValue', $this->category->getLevelValue());
    }

    public function testPATCHGame()
    {
        $rencontre = 321;
        $phase = 'quart';
        $data = array(
            "rencontre" => $rencontre,
            "phase" => $phase,
            "category" => $this->category->getId()
        );
        $game = $this->createGame($data, $this->category);

        $data = array(
            "phase" => $phase,
        );

        $response = $this->client->patch('api/games/'.$game->getId(), [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'id',
            'rencontre',
            'phase',
            'category'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'rencontre', $rencontre);
        $this->asserter()->assertResponsePropertyEquals($response, 'phase', $phase);
        $this->asserter()->assertResponsePropertyEquals($response, 'category.website', $this->category->getWebsite());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.competitionValue', $this->category->getCompetitionValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.specialityValue', $this->category->getSpecialityValue());
        $this->asserter()->assertResponsePropertyEquals($response, 'category.levelValue', $this->category->getLevelValue());
    }

    public function testDELETEGame()
    {
        $rencontre = 321;
        $phase = 'quart';
        $data = array(
            "rencontre" => $rencontre,
            "phase" => $phase,
            "category" => $this->category->getId()
        );
        $game = $this->createGame($data, $this->category);

        $response = $this->client->delete("api/games/".$game->getId(), array(
            'headers' => $this->getAuthorizedHeaders($this->username)
        ));
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testValidationErrors()
    {
        $rencontre = 12345;
        $phase = 'finale';
        $data = array(
//            "rencontre" => $rencontre,
            "phase" => $phase,
//            "category" => $this->category->getId()
        );

        $response = $this->client->post('api/games', [
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
        $this->asserter()->assertResponsePropertyExists($response, 'errors.rencontre');
        $this->asserter()->assertResponsePropertyExists($response, 'errors.category');
        $this->asserter()->assertResponsePropertyEquals($response, 'errors.rencontre[0]', 'Please enter a rencontre');
        $this->asserter()->assertResponsePropertyEquals($response, 'errors.category[0]', 'Please enter a category');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.phase');
    }

    public function testInvalidJson()
    {
        $invalidBody = <<<EOF
{
	"rencontre": 4545
	"phase": "Voici une phase",
	"category": 1
}
EOF;

        $response = $this->client->post('api/games', [
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
        $response = $this->client->get('api/games/fake', [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Not Found');
        $this->asserter()->assertResponsePropertyEquals($response, 'detail', 'No route found for "GET /api/games/fake"');

        $response = $this->client->get('api/games/999', [
            'headers' => $this->getAuthorizedHeaders($this->username)
        ]);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Not Found');
        $this->asserter()->assertResponsePropertyEquals($response, 'detail', 'No game found with id "999"');
    }

    public function testRequiresAuthentication()
    {
        $response = $this->client->post('api/games', [
            'body' => '[]'
        ]);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testBadToken()
    {
        $response = $this->client->post('api/games', [
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