<?php

namespace Pilotabai\CompetitionDbBundle\Controller\Api;

use GuzzleHttp\Psr7\Response;
use Pilotabai\CompetitionDbBundle\Api\ApiProbException;
use Pilotabai\CompetitionDbBundle\Api\ApiProblem;
use Pilotabai\CompetitionDbBundle\Controller\BaseController;
use Pilotabai\CompetitionDbBundle\Entity\Game;
use Pilotabai\CompetitionDbBundle\Form\GameType;
use Pilotabai\CompetitionDbBundle\Form\UpdateGameType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class GameController extends BaseController
{
    /**
     * @Route("/api/games", name="api_games_new")
     * @Method("POST")
     */
    public function newAction(Request $request)
    {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $this->processForm($request, $form);
        if (!$form->isValid()) {
            return $this->throwApiProblemValidationException($form);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($game);
        $em->flush();

        $response = $this->createApiResponse($game, 201);
        $gameUrl = $this->generateUrl(
            'api_games_show',
            ['id' => $game->getId()]
        );
        $response->headers->set('Location', $gameUrl);

        return $response;
    }

    /**
     * @Route("/api/games/{id}", name="api_games_update",
     *     requirements={
     *          "id": "\d+"
     *     })
     * @Method({"PUT", "PATCH"})
     */
    public function updateAction($id, Request $request)
    {
        $game = $this->getGameRepository()
            ->findOneBy(array('id' => $id));
        if (!$game) {
            throw $this->createNotFoundException(sprintf(
                'No game found with id "%s"',
                $id
            ));
        }

        $form = $this->createForm(UpdateGameType::class, $game);

        $this->processForm($request, $form);
        if (!$form->isValid()) {
            return $this->throwApiProblemValidationException($form);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($game);
        $em->flush();

        $response = $this->createApiResponse($game, 200);

        return $response;
    }

    /**
     *  @Route("/api/games/{id}", name="api_games_delete",
     *     requirements={
     *          "id": "\d+"
     *     })
     * @Method("DELETE")
     *
     * @param integer $id
     * @param Request $request
     * @return Response
     */
    public function deleteAction($id, Request $request)
    {
        $game = $this->getGameRepository()
            ->findOneBy(array('id' => $id));
        if (!$game) {
            throw $this->createNotFoundException(sprintf(
                'No game found with id "%s"',
                $id
            ));
        } else {
            // debated point: should we 404 on an unknown id?
            // or should we just return a nice 204 in all cases?
            $catId = $game->getId();
            $em = $this->getDoctrine()->getManager();
            $em->remove($game);
            $em->flush();
            $game->setId($catId);
        }
        $response = $this->createApiResponse($game, 204); // Should I return null and remove the setId method in Game?

        return $response;
    }

    /**
     * @Route("/api/games/{id}", name="api_games_show",
     *     requirements={
     *          "id": "\d+"
     *     })
     * @Method("GET")
     *
     * @param integer $id
     * @return Response
     */
    public function showAction($id)
    {
        $game = $this->getGameRepository()
            ->findOneBy(array('id' => $id));
        if (!$game) {
            throw $this->createNotFoundException(sprintf(
                'No game found with id "%s"',
                $id
            ));
        }

        $response = $this->createApiResponse($game, 200);
        return $response;
    }

    /**
     * @Route("/api/games", name="api_games_list")
     * @Method("GET")
     */
    public function listAction(Request $request)
    {
        $categoryId = $request->query->get('categoryFilter');
        $phase = $request->query->get('phaseFilter');

        $qb = $this->getGameRepository()
            ->findAllQueryBuilder($categoryId, $phase);

        $paginatedCollection = $this->get('pilotabai_competition_db.pagination_factory')
            ->createCollection($qb, $request, 'api_games_list');

        $response = $this->createApiResponse($paginatedCollection, 200);

        return $response;
    }

    private function processForm(Request $request, FormInterface $form)
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            $apiProblem = new ApiProblem(
                400,
                ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT
            );
            throw new ApiProbException($apiProblem);
        }
        $clearMissing = $request->getMethod() != 'PATCH';
        $form->submit($data, $clearMissing);
    }

    private function throwApiProblemValidationException(FormInterface $form)
    {
        $errors = $this->getErrorsFromForm($form);
        $apiProblem = new ApiProblem(
            400,
            ApiProblem::TYPE_VALIDATION_ERROR
        );
        $apiProblem->set('errors', $errors);
        throw new ApiProbException($apiProblem);
    }

    private function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }
        return $errors;
    }
}
