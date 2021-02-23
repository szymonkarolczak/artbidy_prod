<?php

namespace AdminBundle\Controller;

use AppBundle\Entity\StaticPageLang;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use kcfinder\text;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use AppBundle\Entity\SocialMedia;
use AdminBundle\Form\SocialMediaType;

/**
 * @Route("/admin")
 */
class StaticController extends Controller
{
    /**
     * @Route("/static", name="admin_general_static")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $pages = $em->getRepository('AppBundle:StaticPage')->findAll();
        $langs = $em->getRepository('AppBundle:Language')->findAll();

        return array(
            'pages' => $pages,
            'langs' => $langs
        );
    }

    /**
     * @Route("/static/{id}", name="admin_general_static_edit", requirements={
     *  "id": "\d+",
     * })
     * @Template()
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $page = $em->getRepository('AppBundle:StaticPage')->find($id);
        if(!$page)
            throw $this->createNotFoundException();

        $lang = $em->getRepository('AppBundle:Language')->find($request->get('language'));
        if(!$lang)
            throw $this->createNotFoundException();

        $pageLang = $em->getRepository('AppBundle:StaticPageLang')->createQueryBuilder('pl')
            ->select('pl')
            ->where('pl.page = :page')
            ->andWhere('pl.lang = :lang')
            ->setParameter(':lang', $lang)
            ->setParameter(':page', $page)
            ->getQuery()->getResult();
        if(!$pageLang)
        {
            $pageLang = new StaticPageLang();
            $pageLang->setLang($lang)->setPage($page);
        } else {$pageLang = $pageLang[0];}

        $form = $this->createFormBuilder($pageLang)
            ->add('content', CKEditorType::class,array('label'=>'Treść'))
            ->add('metatitle', TextType::class,array('label'=>'MetaTitle'))
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $pageLang->setEditDate(new \DateTime());
            $em->persist($pageLang);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('ogolne.statyczne.zapisane', [], 'admin'));
            return new RedirectResponse($this->generateUrl('admin_general_static'));
        }

        return array(
            'page' => $page,
            'lang' => $lang,
            'form' => $form->createView()
        );
    }

}
