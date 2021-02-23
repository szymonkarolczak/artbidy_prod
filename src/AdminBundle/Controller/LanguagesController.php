<?php

namespace AdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use AdminBundle\Form\LanguageType;
use AppBundle\Entity\Language;

/**
 * @Route("/admin/languages")
 */
class LanguagesController extends Controller
{
    /**
     * @Route("/", name="admin_general_languages")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $langs = $em->getRepository('AppBundle:Language')->findAll();
                
        return array(
            'langs' => $langs
        );
    }
    
    /**
     * @Route("/add", name="admin_general_languages_add")
     */
    public function addAction(Request $request)
    {
        $lang = new Language();
        $form = $this->createForm(LanguageType::class, $lang);
        
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($lang);
                $em->flush();
                
                if(!copy($this->get('kernel')->getRootDir() . '/Resources/translations/admin.pl.yml',
                        $this->get('kernel')->getRootDir() . '/Resources/translations/admin.'.$lang->getShortcut().'.yml'))
                        
                if(!copy($this->get('kernel')->getRootDir() . '/Resources/translations/messages.pl.yml',
                    $this->get('kernel')->getRootDir() . '/Resources/translations/messages.'.$lang->getShortcut().'.yml'))
                
                $this->addFlash('success', $this->get('translator')->trans('ogolne.jezyki.dodany', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_general_languages'));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('ogolne.jezyki.nie_dodany', [], 'admin').' '.$e->getMessage());
            }
        }
        
        return $this->render('AdminBundle:Languages:add_edit.html.twig', array(
            'form' => $form->createView()
        ));
    }
    
    /**
     * @Route("/edit/{id}", name="admin_general_languages_edit", requirements={
     *  "id": "\d+"
     * })
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $lang = $em->getRepository('AppBundle:Language')->find($id);
        if(!$lang)
            throw $this->createNotFoundException ();
        
        $form = $this->createForm(LanguageType::class, $lang);
        
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em->persist($lang);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('ogolne.jezyki.zmieniony', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_general_languages'));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('ogolne.jezyki.nie_zmieniony', [], 'admin'));
            }
        }
        
        return $this->render('AdminBundle:Languages:add_edit.html.twig', array(
            'form' => $form->createView(),
            'lang' => $lang
        ));
    }
    
    /**
     * @Route("/delete/{id}", name="admin_general_languages_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $lang = $em->getRepository('AppBundle:Language')->find($id);
        if(!$lang)
            throw $this->createNotFoundException ();
        
        $em->remove($lang);
        $em->flush();
        $this->addFlash('success', $this->get('translator')->trans('ogolne.jezyki.usuniety', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_general_languages'));
    }
    
    /**
     * @Route("/files/{id}", name="admin_general_languages_files", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function filesAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $lang = $em->getRepository('AppBundle:Language')->find($id);
        if(!$lang)
            throw $this->createNotFoundException ();
        
        $files = glob($this->get('kernel')->getRootDir() . '/Resources/translations/*.'.$lang->getShortcut().'.yml');
        $files = array_map(function($file) {
            return basename($file);
        }, $files);
        
        return array(
            'lang' => $lang,
            'files' => $files
        );
    }
    
    /**
     * @Route("/files/edit/{file}", name="admin_general_languages_files_edit")
     * @Template()
     */
    public function fileEditAction($file, Request $request)
    {
        $contentFile = $request->request->get('contentFile');

        if($contentFile)
        {
            file_put_contents($this->get('kernel')->getRootDir() . '/Resources/translations/'.$file, $contentFile);
            $this->addFlash('success', 'Plik został poprawnie zapisany.');
        }

        return array(
            'content' => file_get_contents($this->get('kernel')->getRootDir() . '/Resources/translations/'.$file),
        );
    }
}
