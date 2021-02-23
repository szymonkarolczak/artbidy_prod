<?php

namespace UserBundle\Controller;

use AppBundle\Entity\Newsletter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

use UserBundle\Form\CardType;
use UserBundle\Form\GeneralType;
use UserBundle\Form\InvoiceType;
use UserBundle\Form\SocialMediaType;

/**
 * @Route("/profile/settings")
 */
class SettingsController extends Controller
{
    
    /**
     * @Route("/general", name="profile_settings_general")
     * @Template()
     */
    public function generalAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        
        $form = $this->createForm(GeneralType::class, $user);
        $form->handleRequest($request);

        if(($form->isSubmitted() && $form->isValid()))
        {
            $userManager = $this->get('fos_user.user_manager');
            $em = $this->getDoctrine()->getManager();

            $newsletter = $em->getRepository('AppBundle:Newsletter')->findBy(array(
                'email' => $user->getEmail()
            ));
            if($user->getNewsletter())
            {
                if(!$newsletter)
                {
                    $locale = $user->getCountry() == 'Polska' || $user->getCountry() == 'Poland' ? 'pl' : 'en';
                    $newsletter = new Newsletter();
                    $newsletter->setEmail($user->getEmail());
                    $newsletter->setLocale($locale);
                    $em->persist($newsletter);
                }
            }
            else
            {
                if($newsletter)
                    $em->remove($newsletter[0]);
            }
            $em->flush();

            $userManager->updateUser($user);
            return new RedirectResponse($this->generateUrl('profile_settings_general'));
        }
        
        return array(
            'form' => $form->createView(),
            'success' => isset($success) ? $success : false
        );
    }
    
    /**
     * @Route("/change-pass", name="profile_settings_changepass")
     * @Template()
     */
    public function changePassAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        
        $formFactory = $this->get('fos_user.change_password.form.factory');

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);
            $success = $this->get('translator')->trans('user.haslo_zmienione');
        }
        
        return array(
            'form' => $form->createView(),
            'success' => isset($success) ? $success : false
        );
    }
    
    /**
     * @Route("/invoice", name="profile_settings_invoice")
     * @Template()
     */
    public function invoiceAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        
        $form = $this->createForm(InvoiceType::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);
            $success = $this->get('translator')->trans('main.dane_zmienione');
        }
        
        return array(
            'form' => $form->createView(),
            'success' => isset($success) ? $success : false
        );
    }
    
}
