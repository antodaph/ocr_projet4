<?php

namespace OC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class CoreController extends Controller
{
    public function indexAction()
    {
        // Ici, on récupére la liste des annonces, puis on la passe au template
        $repository = $this->getDoctrine()->getManager()->getRepository('OCPlatformBundle:Advert');
        $listAdverts = $repository->getAdverts(1,3);
       
        // Mais pour l'instant, on ne fait qu'appeler le template
        return $this->render('OCCoreBundle:Core:index.html.twig', 
                             array('listAdverts' => $listAdverts));
    }
    
    public function contactAction(Request $request)
    {
        $session = $request->getSession();
    
        $session->getFlashBag()->add('info', 'La page de contact n\'est pas encore disponible, merci de revenir plus tard');

        // Puis on redirige vers la page d'accueil'
        return $this->redirectToRoute('oc_core_homepage');
    }
}
