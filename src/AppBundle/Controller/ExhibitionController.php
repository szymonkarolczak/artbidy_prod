<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ExhibitionLang;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use AppBundle\Form\ExhibitionType;
use AppBundle\Entity\Exhibition;

/**
 * @Route("exhibition")
 */
class ExhibitionController extends Controller
{
    /**
     * @Route("/add", name="exhibition_add")
     * @Template()
     */
    public function addAction(Request $request)
    {
        if(!($user = $this->getUser()))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $lang = $em->getRepository('AppBundle:Language')->findOneBy(array(
            'shortcut' => $request->getLocale()
        ));
        $Elang = new ExhibitionLang();
        $Elang->setLang($lang);

        $exhibition = new Exhibition();
        $exhibition->addLang($Elang);

        $form = $this->createForm(ExhibitionType::class, $exhibition);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {

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
                $this->addFlash('success', $this->get('translator')->trans('exhibition.dodane'));
                return new RedirectResponse($this->generateUrl('users_exhibitions'));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('exhibition.nie_dodane').$e->getMessage());
            }
        }

        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/list", name="users_exhibitions")
     * @Template()
     */
    public function usersExhibitionsAction(Request $request)
    {
        if(!$this->isGranted('ROLE_GALERIA') && !$this->isGranted('ROLE_MUZEUM') && !$this->isGranted('ROLE_DOM_AUKCYJNY') && !$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException();
        
        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e');
        $query = $qb
            ->select('e.id, e.approved, e.image, e.startDate, e.endDate')
            ->join('e.langs', 'el')->addSelect('el.title, el.description')
            ->join('el.lang', 'l')
            ->where('e.author = :author')
            ->andWhere('l.shortcut = :shortcut')
            ->groupBy('e.id')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':author', $this->getUser());
        
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

}
