<?php

namespace Pilotabai\CompetitionDbBundle\Controller;

use GuzzleHttp\Client;
use JMS\Serializer\SerializationContext;
use Pilotabai\CompetitionDbBundle\Repository\CategoryRepository;
use Pilotabai\CompetitionDbBundle\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends Controller
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        return $this->getDoctrine()
            ->getRepository('PilotabaiCompetitionDbBundle:Category');
    }

    /**
     * @return GameRepository
     */
    protected function getGameRepository()
    {
        return $this->getDoctrine()
            ->getRepository('PilotabaiCompetitionDbBundle:Game');
    }

    protected function createApiResponse($data, $statusCode = 200)
    {
        $json = $this->serialize($data);
        return new Response($json, $statusCode, array(
            'Content-Type' => 'application/json'
        ));
    }
    protected function serialize($data, $format = 'json')
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true); // Required to avoid error when value of attribute is null
        return $this->container->get('jms_serializer')
            ->serialize($data, $format, $context);
    }

    /**
     * @return Client
     */
    protected function getGuzzleHttpClient()
    {
        $client = new Client([
            'base_uri' => $this->getParameter('competitiondb_base_url'),
        ]);

        $this->client = $client;
        return $client;
    }
}
