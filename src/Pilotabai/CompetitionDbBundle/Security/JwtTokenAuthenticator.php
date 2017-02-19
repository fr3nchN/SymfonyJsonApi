<?php

namespace Pilotabai\CompetitionDbBundle\Security;

use Doctrine\ORM\EntityManager;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Pilotabai\CompetitionDbBundle\Api\ApiProblem;
use Pilotabai\CompetitionDbBundle\Api\ResponseFactory;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class JwtTokenAuthenticator extends AbstractGuardAuthenticator
{
    private $jwtEncoder;
    private $em;
    private $logger;
    private $responseFactory;

    public function __construct(JWTEncoderInterface $jwtEncoder, EntityManager $em, Logger $logger, ResponseFactory $responseFactory)
    {
        $this->jwtEncoder = $jwtEncoder;
        $this->em = $em;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
    }

    public function getCredentials(Request $request)
    {
        $extractor = new AuthorizationHeaderTokenExtractor(
            'Bearer',
            'Authorization'
        );
        $token = $extractor->extract($request);
        if (!$token) {
//            $this->logger->debug('Did not get the token');
            return;
        }
//        $this->logger->debug("Got the token");
        return $token;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
//        $data = $this->jwtEncoder->decode($credentials);
//
//        if ($data === false) {
//            throw new CustomUserMessageAuthenticationException('Invalid Token');
//        }

        try {
            $data = $this->jwtEncoder->decode($credentials);
        } catch (JWTDecodeFailureException $e) {

            // if you want to, use can use $e->getReason() to find out which of the 3 possible things went wrong
            // and tweak the message accordingly
            // https://github.com/lexik/LexikJWTAuthenticationBundle/blob/05e15967f4dab94c8a75b275692d928a2fbf6d18/Exception/JWTDecodeFailureException.php

            throw new CustomUserMessageAuthenticationException('Invalid Token');
        }

//        $this->logger->debug('No problem decoding token');
        $username = $data['username'];

        $user = $this->em
            ->getRepository('AppBundle:User')
            ->findOneBy(['username' => $username]);
//        $this->logger->debug('Found matching user at '.$user->getEmail());

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
//        $this->logger->debug('checkCredentials');
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
//        $this->logger->debug('onAuthenticationFailure');
//        return new JsonResponse([
//            'message' => $exception->getMessage()
//        ], 401);

        $apiProblem = new ApiProblem(401);
        // you could translate this
        $apiProblem->set('detail', $exception->getMessageKey());
        return $this->responseFactory->createResponse($apiProblem);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
//        $this->logger->debug('onAuthenticationSuccess');
    }

    public function supportsRememberMe()
    {
        return false;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        // called when authentication info is missing from a
        // request that requires it

        $apiProblem = new ApiProblem(401);

        $message = $authException ? $authException->getMessageKey() : 'Missing credentials';
        $apiProblem->set('detail', $message);

        return $this->responseFactory->createResponse($apiProblem);
//        return new JsonResponse($apiProblem->toArray(), 401);
    }

}