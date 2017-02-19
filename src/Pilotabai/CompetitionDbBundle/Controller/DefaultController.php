<?php

namespace Pilotabai\CompetitionDbBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('PilotabaiCompetitionDbBundle:Default:index.html.twig');
    }
}
