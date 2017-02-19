<?php

namespace Pilotabai\CompetitionDbBundle\Controller\Api;

use GuzzleHttp\Psr7\Response;
use Pilotabai\CompetitionDbBundle\Api\ApiProbException;
use Pilotabai\CompetitionDbBundle\Api\ApiProblem;
use Pilotabai\CompetitionDbBundle\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class TokenController extends BaseController
{
    /**
     * @Route("/api/tokens")
     * @Method("POST")
     */
    public function newTokenAction(Request $request)
    {
        $user = $this->getDoctrine()
            ->getRepository('AppBundle:User')
            ->findOneBy(['username' => $request->getUser()]);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $isValid = $this->get('security.password_encoder')
            ->isPasswordValid($user, $request->getPassword());
        if (!$isValid) {
            throw new BadCredentialsException();
        }

        $token = $this->get('lexik_jwt_authentication.encoder')
            ->encode([
                'username' => $user->getUsername(),
                'exp' => time() + 3600 // 1 hour expiration
            ]);

        return new JsonResponse(['token' => $token]);
    }
}