<?php

namespace AdminBundle\Controller;

use AppBundle\Entity\AdvertLang;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @Route("/admin")
 */
class AdvertController extends Controller
{
    /**
     * @Route("/advert", name="admin_general_advert")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $pages = $em->getRepository('AppBundle:Advert')->findAll();
        $langs = $em->getRepository('AppBundle:Language')->findAll();

        return array(
            'adverts' => $pages,
            'langs' => $langs
        );
    }

    /**
     * @Route("/advert/{id}", name="admin_general_advert_edit", requirements={
     *  "id": "\d+",
     * })
     * @Template()
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('AppBundle:Advert')->find($id);
        if(!$advert)
            throw $this->createNotFoundException();

        $lang = $em->getRepository('AppBundle:Language')->find($request->get('language'));
        if(!$lang)
            throw $this->createNotFoundException();

        $pageLang = $em->getRepository('AppBundle:AdvertLang')->createQueryBuilder('pl')
            ->select('pl')
            ->where('pl.advert = :advert')
            ->andWhere('pl.lang = :lang')
            ->setParameter(':lang', $lang)
            ->setParameter(':advert', $advert)
            ->getQuery()->getResult();
        if(!$pageLang)
        {
            $pageLang = new AdvertLang();
            $pageLang->setLang($lang)->setAdvert($advert);
        } else {$pageLang = $pageLang[0];}

        $form = $this->createFormBuilder($pageLang)
            ->add('content', CKEditorType::class)
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $pageLang->setEditDate(new \DateTime());
            $em->persist($pageLang);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('ogolne.reklamy.zapisane', [], 'admin'));
            return new RedirectResponse($this->generateUrl('admin_general_advert'));
        }

        return array(
            'advert' => $advert,
            'lang' => $lang,
            'form' => $form->createView()
        );
    }

}
