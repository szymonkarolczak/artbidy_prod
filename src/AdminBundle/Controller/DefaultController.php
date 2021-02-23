<?php

namespace AdminBundle\Controller;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use AppBundle\Entity\SocialMedia;
use AdminBundle\Form\SocialMediaType;

/**
 * @Route("/admin")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="admin_homepage")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        //Newest work
        $newestWorks = $em->getRepository('AppBundle:Work')->createQueryBuilder('w')
            ->select('w')
            ->where('w.approved = :approved')
            ->setParameter(':approved', true)
            ->orderBy('w.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        //Newest auctions works
        $newestAuctionsWorks = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->join('aw.work', 'w')->addSelect('w')
            ->join('aw.auction', 'a')
            ->join('a.langs', 'l')->addSelect('l.title')
            ->join('l.lang', 'll')
            ->where('aw.approved = :approved')
            ->andWhere('ll.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':approved', false)
            ->orderBy('aw.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        $newestBids = $em->getRepository('AppBundle:AuctionBid')->createQueryBuilder('ab')
            ->join('ab.currency', 'c')->addSelect('c')
            ->join('ab.auctionWork', 'aw')->addSelect('aw')
            ->join('aw.work', 'w')->addSelect('w')
            ->join('aw.auction', 'a')
            ->join('a.langs', 'l')->addSelect('l.title')
            ->join('l.lang', 'll')
            ->where('ll.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->orderBy('ab.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        return array(
            'newestWorks' => $newestWorks,
            'newestBids' => $newestBids,
            'newestAuctionsWorks' => $newestAuctionsWorks
        );
    }

    /**
     * @Route("/deletecache", name="admin_delete_cache")
     * @Template()
     */
    public function deleteCacheAction()
    {
        set_time_limit(0);
        $script = $this->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'console';

        $devCache = shell_exec('php '.$script.' cache:clear');
        $prodCache = shell_exec('php '.$script.' cache:clear --env=prod');

        $t = $this->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'translations';
        $v = $this->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR;
        $w = $this->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR;


        $chmod = shell_exec('chmod -R 777 '.$t.' '.$v.' '.$w);

        return array(
            'devCache' => $devCache,
            'prodCache' => $prodCache,
            'chmod' => $chmod
        );
    }

    /**
     * @Route("/ogolne/social-media", name="admin_general_social_media")
     * @Template()
     */
    public function socialMediaAction()
    {
        $em = $this->getDoctrine()->getManager();
        $socialMedias = $em->getRepository('AppBundle:SocialMedia')->findAll();
        
        return array(
            'socialMedias' => $socialMedias
        );
    }

    /**
     * @Route("/ogolne/social-media/add", name="admin_general_social_media_add")
     * @Template()
     */
    public function addSocialMediaAction(Request $request)
    {
        $socialMedia = new SocialMedia();
        $form = $this->createForm(SocialMediaType::class, $socialMedia);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($socialMedia);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('ogolne.social_media.utworzone', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_general_social_media'));
            }
            catch (\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('ogolne.social_media.nie_utworzone', [], 'admin'));
            }
        }
        
        return $this->render('AdminBundle:Default:add_edit_socialMedia.html.twig', array(
            'form' => $form->createView()
        ));
    }
    
    /**
     * @Route("/ogolne/social-media/edit/{id}", name="admin_general_social_media_edit", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function editSocialMediaAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $socialMedia = $em->getRepository('AppBundle:SocialMedia')->find($id);
        if(!$socialMedia)
            throw $this->createNotFoundException ();
        
        $form = $this->createForm(SocialMediaType::class, $socialMedia);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em->persist($socialMedia);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('ogolne.social_media.zmienione', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_general_social_media'));
            }
            catch (\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('ogolne.social_media.nie_zmienione', [], 'admin'));
            }
        }
        
        return $this->render('AdminBundle:Default:add_edit_socialMedia.html.twig', array(
            'form' => $form->createView(),
            'media' => $socialMedia
        ));
    }
    
    /**
     * @Route("/ogolne/social-media/delete/{id}", name="admin_general_social_media_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteSocialMediaAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $socialMedia = $em->getRepository('AppBundle:SocialMedia')->find($id);
        if(!$socialMedia)
            throw $this->createNotFoundException ();
        
        $em->remove($socialMedia);
        $em->flush();
        $this->addFlash('success', $this->get('translator')->trans('ogolne.social_media.usuniete', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_general_social_media'));
    }
}
