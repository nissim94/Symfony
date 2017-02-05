<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Event\MessagePostEvent;
use OC\PlatformBundle\Event\PlatformEvents;
use OC\PlatformBundle\Form\AdvertEditType;
use OC\PlatformBundle\Form\AdvertType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Compiler\RepeatablePassInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AdvertController extends Controller
{
    /**
     * @param $page
     * @return Response
     */
    public function indexAction(int $page) : Response
    {
        if ($page < 1)
            throw new NotFoundHttpException("Page n°$page inexistante. ");

        // Ici je fixe le nombre d'annonces par page à 3
        // Mais bien sûr il faudrait utiliser un paramètre, et y accéder via $this->container->getParameter('nb_per_page')
        $nbPerPage = 3;

        // On récupère notre objet Paginator
        $listAdverts = $this->getDoctrine()
                            ->getManager()
                            ->getRepository('OCPlatformBundle:Advert')
                            ->getAdverts($page, $nbPerPage);

        // On calcule le nombre total de pages grâce au count($listAdverts) qui retourne le nombre total d'annonces
        $nbPages = ceil(count($listAdverts) / $nbPerPage);

        // Si la page n'existe pas, on retourne une 404
        if ($page > $nbPages)
            throw $this->createNotFoundException("La page $page n'existe pas.");

        // On donne toutes les informations nécessaires à la vue
        return $this->render('OCPlatformBundle:Advert:index.html.twig', [
            'listAdverts' => $listAdverts,
            'nbPages'     => $nbPages,
            'page'        => $page,
        ]);
    }

    /**
     * @return Response
     *///@ParamConverter("advert", options={"mapping": {"advert_id": "id"}})
    public function viewAction(Advert $advert) : Response
    {
        $em = $this->getDoctrine()->getManager();

        /* Pour récupérer une seule annonce, on utilise la méthode find($id)
        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        // $advert est donc une instance de OC\PlatformBundle\Entity\Advert
        // ou null si l'id $id n'existe pas, d'où ce if :
        if (null === $advert)
            throw new NotFoundHttpException("L'annonce d'id $id n'existe pas.");*/

        //Récupération de la liste des candidatures de l'annonce
        $listApplications = $em
            ->getRepository('OCPlatformBundle:Application')
            ->findBy(array('advert' => $advert));


        // Récupération des AdvertSkill de l'annonce
        $listAdvertSkills = $em
            ->getRepository('OCPlatformBundle:AdvertSkill')
            ->findBy(array('advert' => $advert));

        return $this->render('OCPlatformBundle:Advert:view.html.twig', [
            'advert'           => $advert,
            'listApplications' => $listApplications,
            'listAdvertSkills' => $listAdvertSkills,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @Security("has_role('ROLE_AUTEUR')")
     */
    public function addAction(Request $request) : Response
    {
        $advert = new Advert();
        $form   = $this->get('form.factory')->create(AdvertType::class, $advert);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid())
        {
            // on instancie notre evenement pour ensuite la passer au service 'event_dispatcher'
            $event = new MessagePostEvent($advert->getContent(), $this->getUser());

            // on recupere le gestionnaire d'evts on lui donne le nom de l'evt
            // ainsi que l'evt instancié plus haut et on déclenche l'evt
            $this->get('event_dispatcher')->dispatch(PlatformEvents::POST_MESSAGE,$event);

            //ensuite on applique les changements a notre objet
            $advert->setContent($event->getMessage());

            $em = $this->getDoctrine()->getManager();
            $em->persist($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            return $this->redirectToRoute('oc_platform_view', ['id' => $advert->getId()]);
        }

        return $this->render('OCPlatformBundle:Advert:add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function editAction(int $id, Request $request) : Response
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert)
            throw new NotFoundHttpException("L'annonce d'id $id n'existe pas.");

        $form = $this->get('form.factory')->create(AdvertEditType::class, $advert);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid())
        {
            // Inutile de persister ici, Doctrine connait déjà notre annonce
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');

            return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
        }

        return $this->render('OCPlatformBundle:Advert:edit.html.twig', [
            'advert' => $advert,
            'form'   => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function deleteAction(Request $request, int $id) : Response
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert)
            throw new NotFoundHttpException("L'annonce d'id $id n'existe pas.");

        // On crée un formulaire vide, qui ne contiendra que le champ CSRF
        // Cela permet de protéger la suppression d'annonce contre cette faille
        $form = $this->get('form.factory')->create();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em->remove($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('info', "L'annonce a bien été supprimée.");

            return $this->redirectToRoute('oc_platform_home');
        }

        return $this->render('OCPlatformBundle:Advert:delete.html.twig', [
            'advert' => $advert,
            'form'   => $form->createView()
        ]);
    }

    /**
     * @param $limit
     * @return Response
     */
    public function menuAction(int $limit) : Response
    {
        $em = $this->getDoctrine()->getManager();

        $listAdverts = $em->getRepository('OCPlatformBundle:Advert')->findBy(
        [],                 // Pas de critère
        ['date' => 'desc'], // On trie par date décroissante
        $limit,                  // On sélectionne $limit annonces
        0                        // À partir du premier
        );

        return $this->render('OCPlatformBundle:Advert:menu.html.twig', [
            'listAdverts' => $listAdverts
        ]);
    }


    /**
     * Méthode facultative pour tester la purge
     * @param $days
     * @param Request $request
     * @return Response
     */
    public function purgeAction(int $days, Request $request) : Response
    {
        // On récupère notre service
        $purger = $this->get('oc_platform.purger.advert');

        // On purge les annonces
        $purger->purge($days);

        // On ajoute un message flash arbitraire
        $request->getSession()->getFlashBag()->add('info', "Les annonces plus vieilles que $days jours ont été purgées. ");

        // On redirige vers la page d'accueil
        return $this->redirectToRoute('oc_platform_home');
    }

    public function translationAction($name) : Response
    {
        return $this->render('OCPlatformBundle:Advert:translation.html.twig',
            ['name' => $name]
        );
    }

    /**
     * @ParamConverter("json")
     */
    public function ParamConverterAction($json) : Response
    {
        return new Response(print_r($json, true));
    }

}