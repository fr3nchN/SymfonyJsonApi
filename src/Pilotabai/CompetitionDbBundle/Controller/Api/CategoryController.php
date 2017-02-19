<?php

namespace Pilotabai\CompetitionDbBundle\Controller\Api;

use GuzzleHttp\Psr7\Response;
use Pilotabai\CompetitionDbBundle\Api\ApiProbException;
use Pilotabai\CompetitionDbBundle\Api\ApiProblem;
use Pilotabai\CompetitionDbBundle\Controller\BaseController;
use Pilotabai\CompetitionDbBundle\Entity\Category;
use Pilotabai\CompetitionDbBundle\Form\CategoryType;
use Pilotabai\CompetitionDbBundle\Form\UpdateCategoryType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class CategoryController extends BaseController
{
    /**
     * @Route("/api/categories", name="api_categories_new")
     * @Method("POST")
     */
    public function newAction(Request $request)
    {
        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        //$this->getUser();
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $this->processForm($request, $form);
        if (!$form->isValid()) {
            return $this->throwApiProblemValidationException($form);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($category);
        $em->flush();

        $response = $this->createApiResponse($category, 201);
        $categoryUrl = $this->generateUrl(
            'api_categories_show',
            ['id' => $category->getId()]
        );
        $response->headers->set('Location', $categoryUrl);

        return $response;
    }

    /**
     * @Route("/api/categories/{id}", name="api_categories_update",
     *     requirements={
     *          "id": "\d+"
     *     })
     * @Method({"PUT", "PATCH"})
     */
    public function updateAction($id, Request $request)
    {
        $category = $this->getCategoryRepository()
            ->findOneBy(array('id' => $id));
        if (!$category) {
            throw $this->createNotFoundException(sprintf(
                'No category found with id "%s"',
                $id
            ));
        }

        $form = $this->createForm(UpdateCategoryType::class, $category);
//        $form = $this->createForm(CategoryType::class, $category, array( Use this notation if the UpdateCategoryType is not created
//            'is_edit' => true,
//        ));
        $this->processForm($request, $form);
        if (!$form->isValid()) {
            return $this->throwApiProblemValidationException($form);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($category);
        $em->flush();

        $response = $this->createApiResponse($category, 200);

        return $response;
    }

    /**
     *  @Route("/api/categories/{id}", name="api_categories_delete",
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
        $category = $this->getCategoryRepository()
            ->findOneBy(array('id' => $id));
        if (!$category) {
            throw $this->createNotFoundException(sprintf(
                'No category found with id "%s"',
                $id
            ));
        } else {
            // debated point: should we 404 on an unknown id?
            // or should we just return a nice 204 in all cases?
            $catId = $category->getId();
            $em = $this->getDoctrine()->getManager();
            $em->remove($category);
            $em->flush();
            $category->setId($catId);
        }
        $response = $this->createApiResponse($category, 204); // Should I return null and remove the setId method in Category?

        return $response;
    }

    /**
     * @Route("/api/categories/{id}", name="api_categories_show",
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
        $category = $this->getCategoryRepository()
            ->findOneBy(array('id' => $id));
        if (!$category) {
            throw $this->createNotFoundException(sprintf(
                'No category found with id "%s"',
                $id
            ));
        }

        $response = $this->createApiResponse($category, 200);
        return $response;
    }

    /**
     * @Route("/api/categories", name="api_categories_list")
     * @Method("GET")
     */
    public function listAction(Request $request)
    {
        $websiteFilter = $request->query->get('websiteFilter');
        $competitionValFilter = $request->query->get('competitionValFilter');
        $specialityValFilter = $request->query->get('specialityValFilter');
        $levelValFilter = $request->query->get('levelValFilter');

        $qb = $this->getCategoryRepository()
            ->findAllQueryBuilder($websiteFilter, $competitionValFilter, $specialityValFilter, $levelValFilter);

//        $page = $request->query->get('page', 1);
//        $adapter = new DoctrineORMAdapter($qb);
//        $pagerfanta = new Pagerfanta($adapter);
//        $pagerfanta->setMaxPerPage(10);
//        $pagerfanta->setCurrentPage($page);
//        $categories = [];
//        foreach ($pagerfanta->getCurrentPageResults() as $result) {
//            $categories[] = $result;
//        }
//        $paginatedCollection = new PaginatedCollection($categories, $pagerfanta->getNbResults());
//
//        $route = 'api_categories_list';
//        $routeParams = array();
//        $createLinkUrl = function($targetPage) use ($route, $routeParams) {
//            return $this->generateUrl($route, array_merge(
//                $routeParams,
//                array('page' => $targetPage)
//            ));
//        };
//        $paginatedCollection->addLink('self', $createLinkUrl($page));
//        $paginatedCollection->addLink('first', $createLinkUrl(1));
//        $paginatedCollection->addLink('last', $createLinkUrl($pagerfanta->getNbPages()));
//        if ($pagerfanta->hasNextPage()) {
//            $paginatedCollection->addLink('next', $createLinkUrl($pagerfanta->getNextPage()));
//        }
//        if ($pagerfanta->hasPreviousPage()) {
//            $paginatedCollection->addLink('prev', $createLinkUrl($pagerfanta->getPreviousPage()));
//        }

        $paginatedCollection = $this->get('pilotabai_competition_db.pagination_factory')
            ->createCollection($qb, $request, 'api_categories_list');

        $response = $this->createApiResponse($paginatedCollection, 200);

        //$categories = $this->getCategoryRepository()
        //    ->findAll();
        //$response = $this->createApiResponse([
        //    'total' => $pagerfanta->getNbResults(),
        //    'count' => count($categories),
        //    'categories' => $categories
        //], 200);

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
