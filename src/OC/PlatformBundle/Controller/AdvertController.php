<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

// N'oubliez pas ce use :
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// N'oubliez pas ce use pour le service security.authorization_checker
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
// N'oubliez pas ce use pour l'annotation
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
// N'oubliez pas ce use pour la gestion de notre évènement
use OC\PlatformBundle\Event\PlatformEvents;
use OC\PlatformBundle\Event\MessagePostEvent;

// Entity
use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Entity\Image;
use OC\PlatformBundle\Entity\Application;
use OC\PlatformBundle\Entity\AdvertSkill;

// Type objet Formulaire
use OC\PlatformBundle\Form\AdvertType;
use OC\PlatformBundle\Form\AdvertEditType;

// ParamConverter
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;

class AdvertController extends Controller
{
    public function indexAction($page)
    {
        // On ne sait pas combien de pages il y a
        // Mais on sait qu'une page doit être supérieure ou égale à 1
        if ($page < 1){
            // On déclenche une exception NotFoundHttpException, cela va afficher
            // une page d'erreur 404 (qu'on pourra personnaliser plus tard d'ailleurs)
            // throw new NotFoundHttpException('Page "'.$page.'" inexistante.');
            return $this->render('OCPlatformBundle:Advert:error404.html.twig');
        }

        // Ici, on récupére la liste des annonces, puis on la passe au template
        $repository = $this->getDoctrine()->getManager()->getRepository('OCPlatformBundle:Advert');
        $listAdverts = $repository->getAdverts($page,3);
        $nbPageMax = (ceil(count($listAdverts)/3));
        if ($page > $nbPageMax){
            return $this->render('OCPlatformBundle:Advert:error404.html.twig');
        }
        
       
        // Mais pour l'instant, on ne fait qu'appeler le template
        return $this->render('OCPlatformBundle:Advert:index.html.twig', 
                             array('listAdverts' => $listAdverts,
                                   'nbPages'     => $nbPageMax,
                                   'page'        => $page));
    }
    
    // Utilisation de DoctrineParamConverter
    public function viewAction(Advert $advert, $id)
    {
        // Ici, $advert est une instance de l'entité Advert, portant l'id $id
        
        $em = $this->getDoctrine()->getManager();
        // On récupère la liste des candidatures de cette annonce
        $listApplications = $em->getRepository('OCPlatformBundle:Application')
                               ->findBy(array('advert' => $advert));
        
        // On récupère maintenant la liste des AdvertSkill
        $listAdvertSkills = $em->getRepository('OCPlatformBundle:AdvertSkill')
                               ->findBy(array('advert' => $advert));

        return $this->render('OCPlatformBundle:Advert:view.html.twig', 
                             array('advert'=> $advert, 
                                   'listApplications' => $listApplications,
                                   'listAdvertSkills' => $listAdvertSkills));        
    }
    
    public function viewOldAction($id)
    {
       // On récupère le EntityManager
        $em = $this->getDoctrine()->getManager();

        // On récupère l'entité correspondante à l'id $id
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        // $advert est donc une instance de OC\PlatformBundle\Entity\Advert
        // ou null si l'id $id  n'existe pas, d'où ce if :
        if (null === $advert) {
            return $this->render('OCPlatformBundle:Advert:error404.html.twig'); // throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }
        
        // On récupère la liste des candidatures de cette annonce
        $listApplications = $em->getRepository('OCPlatformBundle:Application')
                               ->findBy(array('advert' => $advert));
        
        // On récupère maintenant la liste des AdvertSkill
        $listAdvertSkills = $em->getRepository('OCPlatformBundle:AdvertSkill')
                               ->findBy(array('advert' => $advert));

        return $this->render('OCPlatformBundle:Advert:view.html.twig', 
                             array('advert'=> $advert, 
                                   'listApplications' => $listApplications,
                                   'listAdvertSkills' => $listAdvertSkills));        
    }
    
    public function listAction()
    {
        $listAdverts = $this->getDoctrine()->getManager()->getRepository('OCPlatformBundle:Advert')
            ->getAdvertWithApplications();

        foreach ($listAdverts as $advert) {
        // Ne déclenche pas de requête : les candidatures sont déjà chargées !
        // Vous pourriez faire une boucle dessus pour les afficher toutes
            $advert->getApplications();
          }
    }
    
    /**
    * @Security("has_role('ROLE_AUTEUR')")
    */
    public function addAction(Request $request)
    {
        // Plus besoin du if avec le security.context, l'annotation s'occupe de tout !
        // Dans cette méthode, vous êtes sûrs que l'utilisateur courant dispose du rôle ROLE_AUTEUR
        
        // Utilisation du service security.authorization_checker On vérifie que l'utilisateur dispose bien du rôle ROLE_AUTEUR
        /*if (!$this->get('security.authorization_checker')->isGranted('ROLE_AUTEUR')) {
            // Sinon on déclenche une exception « Accès interdit »
            throw new AccessDeniedException('Accès limité aux auteurs.');
        }*/
        
        // On crée un objet Advert
        $advert = new Advert();

        // On crée le formulaire directement
        // Méthode longue ... $form = $this->get('form.factory')->create(AdvertType::class, $advert);
        $form = $this->createForm(AdvertType::class, $advert);
            
        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            // On fait le lien Requête <-> Formulaire
            // À partir de maintenant, la variable $advert contient les valeurs entrées dans le formulaire par le visiteur
            $form->handleRequest($request);
            
            // On vérifie que les valeurs entrées sont correctes
            // (Nous verrons la validation des objets en détail dans le prochain chapitre)
            if ($form->isValid()) {
                // c'est elle qui déplace l'image là où on veut les stocker
                // Plus besoin car on a tout automatiser par les evenements $advert->getImage()->upload();
                
                // Ajout de la gestion de l'évènment Post Messsage
                // On crée l'évènement avec ses 2 arguments
                //$event = new MessagePostEvent($advert->getContent(), $advert->getUser());

                // On déclenche l'évènement
                //$this->get('event_dispatcher')->dispatch(PlatformEvents::POST_MESSAGE, $event);

                // On récupère ce qui a été modifié par le ou les listeners, ici le message
                //$advert->setContent($event->getMessage());

                // On enregistre notre objet $advert dans la base de données, par exemple
                $em = $this->getDoctrine()->getManager();
                $em->persist($advert);
                $em->flush();

                $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

                // On redirige vers la page de visualisation de l'annonce nouvellement créée
                return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
            }
        }

        // À ce stade, le formulaire n'est pas valide car :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire contient des valeurs invalides, donc on l'affiche de nouveau        
        return $this->render('OCPlatformBundle:Advert:add.html.twig', array('form' => $form->createView()));
    }
    
    public function addformBuilderAction(Request $request)
    {
        // On crée un objet Advert
        $advert = new Advert();

        // On crée le formulaire directement
        // Méthode longue ... $form = $this->get('form.factory')->create(AdvertType::class, $advert);
        $form = $this->createForm(AdvertType::class, $advert);
        
        // Si la requête est en POST
        if ($request->isMethod('POST')) {
            // On fait le lien Requête <-> Formulaire
            // À partir de maintenant, la variable $advert contient les valeurs entrées dans le formulaire par le visiteur
            $form->handleRequest($request);

            // On vérifie que les valeurs entrées sont correctes
            // (Nous verrons la validation des objets en détail dans le prochain chapitre)
            if ($form->isValid()) {
                // On enregistre notre objet $advert dans la base de données, par exemple
                $em = $this->getDoctrine()->getManager();
                $em->persist($advert);
                $em->flush();

                $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

                // On redirige vers la page de visualisation de l'annonce nouvellement créée
                return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
            }
        }

        // À ce stade, le formulaire n'est pas valide car :
        // - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
        // - Soit la requête est de type POST, mais le formulaire contient des valeurs invalides, donc on l'affiche de nouveau        
        return $this->render('OCPlatformBundle:Advert:add.html.twig', array('form' => $form->createView()));
    } 
    public function addOldAction(Request $request)
    {
        // On récupère l'EntityManager
        $em = $this->getDoctrine()->getManager();
        
        // Création de l'entité
        $advert = new Advert();
        $advert->setTitle('Recherche développeur Symfony.');
        $advert->setAuthor('Alexandre');
        $advert->setContent("Nous recherchons un développeur Symfony débutant sur Lyon. Blabla…");
        $advert->setDate(new \Datetime('-30 day'));
        $advert->setUpdatedAt(new \Datetime('-30 day'));
        // On peut ne pas définir ni la date ni la publication,
        // car ces attributs sont définis automatiquement dans le constructeur

        // Création de l'entité Image
        $image = new Image();
        //$image->setUrl('https://www.ecoleprovence.fr/local/cache-gd2/c6/53c9f7d6f1304e92265c4b38ce9a76.jpg?1486106925');
        //$image->setAlt('Ecole de provence');
        $image->setUrl('http://sdz-upload.s3.amazonaws.com/prod/upload/job-de-reve.jpg');
        $image->setAlt('Job de rêve');

        // On lie l'image à l'annonce
        $advert->setImage($image);

        // Création d'une première candidature
        /*$application1 = new Application();
        $application1->setAuthor('Marine 6');
        $application1->setContent("J'ai toutes les qualités requises."); */
        
        // Création d'une deuxième candidature par exemple
       /* $application2 = new Application();
        $application2->setAuthor('Pierre 6');
        $application2->setContent("Je suis très motivé.");*/
        
        // On lie les candidatures à l'annonce 
      /*  $advert->addApplication($application1);
        $advert->addApplication($application2);
        */
        // On récupère toutes les compétences possibles
        //$listSkills = $em->getRepository('OCPlatformBundle:Skill')->findAll();

        // Pour chaque compétence
        /*foreach ($listSkills as $skill) {
            // On crée une nouvelle « relation entre 1 annonce et 1 compétence »
            $advertSkill = new AdvertSkill();

            // On la lie à l'annonce, qui est ici toujours la même
            $advertSkill->setAdvert($advert);
            // On la lie à la compétence, qui change ici dans la boucle foreach
            $advertSkill->setSkill($skill);

            // Arbitrairement, on dit que chaque compétence est requise au niveau 'Expert'
            $advertSkill->setLevel('Expert');

            // Et bien sûr, on persiste cette entité de relation, propriétaire des deux autres relations
            $em->persist($advertSkill);
        }*/

        // Doctrine ne connait pas encore l'entité $advert. Si vous n'avez pas défini la relation AdvertSkill
        // avec un cascade persist (ce qui est le cas si vous avez utilisé mon code), alors on doit persister $advert
       
        // Étape 1 : On « persiste » l'entité
        $em->persist($advert);
        // Étape 1 bis : si on n'avait pas défini le cascade={"persist"},
        // on devrait persister à la main l'entité $image
        // $em->persist($image);

        // Étape 1 ter : pour cette relation pas de cascade lorsqu'on persiste Advert, car la relation est
        // définie dans l'entité Application et non Advert. On doit donc tout persister à la main ici.
        /*$em->persist($application1);
        $em->persist($application2);*/
        
        // Étape 2 : On « flush » tout ce qui a été persisté avant
        $em->flush();

        // Reste de la méthode qu'on avait déjà écrit
        if ($request->isMethod('POST')) {
          $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

          // Puis on redirige vers la page de visualisation de cettte annonce
          return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }

        // Si on n'est pas en POST, alors on affiche le formulaire
        return $this->render('OCPlatformBundle:Advert:add.html.twig', array('advert' => $advert));
    }

    public function editAction($id, Request $request)
    {
        // Ici, on récupérera l'annonce correspondante à $id
        $em = $this->getDoctrine()->getManager();

        // On récupère l'annonce $id
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert) {
            //throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
            return $this->render('OCPlatformBundle:Advert:error404.html.twig');
            
        }
        $form = $this->createForm(AdvertEditType::class, $advert);
        
        // Si la requête est en POST
        
        // La méthode findAll retourne toutes les catégories de la base de données
        //$listCategories = $em->getRepository('OCPlatformBundle:Category')->findAll();

        // On boucle sur les catégories pour les lier à l'annonce
        /*foreach ($listCategories as $category) {
          $advert->addCategory($category);
        }*/

        // Pour persister le changement dans la relation, il faut persister l'entité propriétaire
        // Ici, Advert est le propriétaire, donc inutile de la persister car on l'a récupérée depuis Doctrine

        // Étape 2 : On déclenche l'enregistrement
        //$em->flush();
        
        // Même mécanisme que pour l'ajout
        if ($request->isMethod('POST')) {
            // On fait le lien Requête <-> Formulaire
            // À partir de maintenant, la variable $advert contient les valeurs entrées dans le formulaire par le visiteur
            $form->handleRequest($request);

            // On vérifie que les valeurs entrées sont correctes
            // (Nous verrons la validation des objets en détail dans le prochain chapitre)
            if ($form->isValid()) {
                // On enregistre notre objet $advert dans la base de données, par exemple
                $em = $this->getDoctrine()->getManager();
                // Inutile de persister ici, Doctrine connait déjà notre annonce  $em->persist($advert);
                $em->flush();

                $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');

                // On redirige vers la page de visualisation de l'annonce nouvellement créée
                return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
            }
        }
        
        return $this->render('OCPlatformBundle:Advert:edit.html.twig', array(
            'advert' => $advert,
            'form' => $form->createView(),
        ));
    }
    public function editformBuilderAction($id, Request $request)
    {
        // Ici, on récupérera l'annonce correspondante à $id
        $em = $this->getDoctrine()->getManager();

        // On récupère l'annonce $id
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert) {
            //throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
            return $this->render('OCPlatformBundle:Advert:error404.html.twig');
            
        }
        // On crée le FormBuilder grâce au service form factory
        $formBuilder = $this->get('form.factory')->createBuilder(FormType::class, $advert);

        // On ajoute les champs de l'entité que l'on veut à notre formulaire
        $formBuilder->add('date',      DateType::class)
                    ->add('title',     TextType::class)
                    ->add('content',   TextareaType::class)
                    ->add('author',    TextType::class)
                    ->add('published', CheckboxType::class)
                    ->add('save',      SubmitType::class)
        ;
        // Pour l'instant, pas de candidatures, catégories, etc., on les gérera plus tard

        // À partir du formBuilder, on génère le formulaire
        $form = $formBuilder->getForm();
        
        // Si la requête est en POST
        
        // La méthode findAll retourne toutes les catégories de la base de données
        //$listCategories = $em->getRepository('OCPlatformBundle:Category')->findAll();

        // On boucle sur les catégories pour les lier à l'annonce
        /*foreach ($listCategories as $category) {
          $advert->addCategory($category);
        }*/

        // Pour persister le changement dans la relation, il faut persister l'entité propriétaire
        // Ici, Advert est le propriétaire, donc inutile de la persister car on l'a récupérée depuis Doctrine

        // Étape 2 : On déclenche l'enregistrement
        //$em->flush();
        
        // Même mécanisme que pour l'ajout
        if ($request->isMethod('POST')) {
            // On fait le lien Requête <-> Formulaire
            // À partir de maintenant, la variable $advert contient les valeurs entrées dans le formulaire par le visiteur
            $form->handleRequest($request);

            // On vérifie que les valeurs entrées sont correctes
            // (Nous verrons la validation des objets en détail dans le prochain chapitre)
            if ($form->isValid()) {
                // On enregistre notre objet $advert dans la base de données, par exemple
                $em = $this->getDoctrine()->getManager();
                $em->persist($advert);
                $em->flush();

                $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');

                // On redirige vers la page de visualisation de l'annonce nouvellement créée
                return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
            }
        }
        
        return $this->render('OCPlatformBundle:Advert:edit.html.twig', array(
            'advert' => $advert,
            'form' => $form->createView(),
        ));
    }

    public function deleteAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // On récupère l'annonce $id
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert) {
            //throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
            return $this->render('OCPlatformBundle:Advert:error404.html.twig');
        }

        // On crée un formulaire vide, qui ne contiendra que le champ CSRF
        // Cela permet de protéger la suppression d'annonce contre cette faille
        $form = $this->get('form.factory')->create();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em->remove($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('info', "L'annonce a bien été supprimée.");

            return $this->redirectToRoute('oc_platform_home');
        }

        return $this->render('OCPlatformBundle:Advert:delete.html.twig', array(
            'advert' => $advert,
            'form'   => $form->createView(),
        ));
            
    }
    
    // On récupère tous les paramètres en arguments de la méthode
    public function viewSlugAction($slug, $year, $_format)
    {
        return new Response(
            "On pourrait afficher l'annonce correspondant au
            slug '".$slug."', créée en ".$year." et au format ".$_format."."
        );
    }
    
    public function menuAction($limit)
    {
        $em = $this->getDoctrine()->getManager();
        $listAdverts = $em->getRepository('OCPlatformBundle:Advert')->findBy(
                       array(),                 // Pas de critère
                       array('date' => 'desc'), // On trie par date décroissante
                       $limit,                  // On sélectionne $limit annonces
                       0                        // À partir du premier
    );

        return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
          // Tout l'intérêt est ici : le contrôleur passe
          // les variables nécessaires au template !
          'listAdverts' => $listAdverts
        ));
    }
    
    public function testAction()
    {
        $advert = new Advert;

        $advert->setDate(new \Datetime());  // Champ « date » OK
        $advert->setTitle('abc');           // Champ « title » incorrect : moins de 10 caractères
        //$advert->setContent('blabla');    // Champ « content » incorrect : on ne le définit pas
        $advert->setAuthor('A');            // Champ « author » incorrect : moins de 2 caractères

        // On récupère le service validator
        $validator = $this->get('validator');

        // On déclenche la validation sur notre object
        $listErrors = $validator->validate($advert);

        // Si $listErrors n'est pas vide, on affiche les erreurs
        if(count($listErrors) > 0) {
        // $listErrors est un objet, sa méthode __toString permet de lister joliement les erreurs
            return new Response((string) $listErrors);
        } else {
            return new Response("L'annonce est valide !");
        }    
        
        /*$advert = new Advert();
        $advert->setTitle("Recherche développeur !");
        $advert->setAuthor('ItsMe');
        $advert->setContent("Nous recherchons un développeur Symfony débutant sur Lyon. Genialllll …");
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($advert);
        $em->flush(); // C'est à ce moment qu'est généré le slug

        return new Response('Slug généré : '.$advert->getSlug());*/ // Affiche « Slug généré : recherche-developpeur »
        
    }
    
    public function translationAction($name)
    {
        return $this->render('OCPlatformBundle:Advert:translation.html.twig', array(
            'name' => $name
        ));
    }
    
    /**
    * @ParamConverter("json")
    */
    public function ParamConverterAction($json)
    {
        return new Response(print_r($json, true));
    }
}