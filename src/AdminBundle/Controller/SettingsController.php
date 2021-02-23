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
class SettingsController extends Controller
{
    /**
     * @Route("/settings/edit/{name}", name="admin_settings_edit")
     * @Template()
     */
    public function indexAction(Request $request, $name)
    {
        $em = $this->getDoctrine()->getManager();

        $banner = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->where('s.name = :name')
            ->setParameter(':name', $name)
            ->getQuery()->getSingleResult();
        $form = $this->createFormBuilder($banner)
            ->add('value', CKEditorType::class, array('label' => false))
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $em->persist($banner);
            $em->flush();
            return new RedirectResponse($this->generateUrl('admin_settings_edit', array(
                'name' => $name
            )));
        }

        return $this->render('AdminBundle:Settings:'.$name.'.html.twig', array(
            'form' => $form->createView()
        ));
    }

}
