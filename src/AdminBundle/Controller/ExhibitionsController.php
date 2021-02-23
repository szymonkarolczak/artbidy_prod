<?php

namespace AdminBundle\Controller;

use AppBundle\Entity\Exhibition;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use AdminBundle\Form\ExhibitionType;

/**
 * @Route("/admin/exhibitions")
 */
class ExhibitionsController extends Controller
{
    /**
     * @Route("/", name="admin_exhibitions_index")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $qb = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e');
        $query = $qb->select('e, a')
                    ->join('e.author', 'a')
                    ->where('e.approved = :approved')
                    ->orderBy('e.id', 'DESC')
                    ->setParameter(':approved', true);
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination
        );
    }
    
      /**
     * @Route("/toaccept", name="admin_exhibitions_to_accept")
     * @Template()
     */
    public function toAcceptAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $qb = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e');
        $query = $qb->select('e')
                    ->where('e.approved = :approved')
                    ->setParameter(':approved', false);
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * @Route("/add", name="admin_exhibitions_add")
     */
    public function addAction(Request $request)
    {
        $exhibition = new Exhibition();
        $exhibition->setApproved(true);
        $exhibition->setPinned(true);
        $exhibition->setFinearts(true);

        $form = $this->createForm(ExhibitionType::class, $exhibition);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em = $this->getDoctrine()->getManager();

                foreach($exhibition->getLangs() as $lang)
                    $lang->setExhibition($exhibition);

                $exhibition->setAuthor($this->getUser());
                $file = $exhibition->getImage();
                if($file instanceof UploadedFile)
                {
                    $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('exhibition_files_directory'));
                    $exhibition->setImage($fileName);
                }

                $em->persist($exhibition);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('wystawa.dodane', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_exhibitions_index'));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('exhibition.nie_dodane').$e->getMessage());
            }
        }

        return $this->render('AdminBundle:Exhibitions:add_edit.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="admin_exhibitions_edit", requirements={
     *  "id": "\d+"
     * })
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $ex = $em->getRepository('AppBundle:Exhibition')->find($id);
        if(!$ex)
            throw $this->createNotFoundException();

        $image = $ex->getImage();
        if(!empty($image) && file_exists($this->getParameter('exhibition_files_directory').'/'.$image))
        {
            $ex->setImage(new File($this->getParameter('exhibition_files_directory').'/'.$image));
        } else {
            $ex->setImage(null);
        }

        $form = $this->createForm(ExhibitionType::class, $ex);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $file = $ex->getImage();
            if($file instanceof UploadedFile)
            {
                $image = $this->get('app.uploader')->upload($file, $this->getParameter('exhibition_files_directory'));
            }
            $ex->setImage($image);

            $em->persist($ex);
            $em->flush();

//            if($ex->getApproved())
//                $this->get('app.exhibition_manager')->createExhibitionEvent($ex);
//            else
//                $this->get('app.exhibition_manager')->deleteExhibitionEvent($ex);

            $this->addFlash('success', $this->get('translator')->trans('wystawy.zmienione', [], 'admin'));
            return new RedirectResponse($this->generateUrl('admin_exhibitions_edit', array(
                'id' => $ex->getId()
            )));
        }

        return $this->render('AdminBundle:Exhibitions:add_edit.html.twig', array(
            'form' => $form->createView(),
            'exhibition' => $ex
        ));
    }
    
    /**
     * @Route("/accept/{id}", name="admin_exhibitions_accept", requirements={
     *  "id": "\d+"
     * })
     */
    public function acceptAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $ex = $em->getRepository('AppBundle:Exhibition')->find($id);
        if(!$ex)
            throw $this->createNotFoundException();

        foreach($ex->getUsers() as $user)
        {
            $usersObserving = $em->getRepository('AppBundle:ProfileObserve')->createQueryBuilder('po')
                ->join('po.user', 'u')->addSelect('u')
                ->where('po.targetUser = :user')
                ->setParameter(':user', $user)
                ->getQuery()->getResult();
            foreach($usersObserving as $obUser)
            {
                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('notifications.user_wystawa'))
                    ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                    ->setTo($obUser->getUser()->getEmail())
                    ->setBody(
                        $this->renderView(
                            '@App/Emails/notification_user_exhibition.html.twig',
                            array(
                                'exhibition' => $ex,
                                'user' => $obUser->getUser(),
                                'target' => $user,
                                'lang' => $obUser->getUser()->getCountry() == 'Polska' || $obUser->getUser()->getCountry() == 'Poland' ? 'pl' : 'en'
                            )
                        ),
                        'text/html'
                    )
                ;
                $this->get('mailer')->send($message);
            }
        }

        $ex->setApproved(true);
        $em->persist($ex);
        $em->flush();

//        $this->get('app.exhibition_manager')->createExhibitionEvent($ex);

        $this->addFlash('success', $this->get('translator')->trans('wystawy.zaakceptowana', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_exhibitions_to_accept'));
    }
    
    /**
     * @Route("/delete/{id}", name="admin_exhibitions_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $ex = $em->getRepository('AppBundle:Exhibition')->find($id);
        if(!$ex)
            throw $this->createNotFoundException();

        $em->remove($ex);
        $em->flush();
        $this->addFlash('success', 'Wystawa została poprawnie usunięta.');

        $lastRoute = $this->get('session')->get('last_route');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

}
