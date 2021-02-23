<?php

namespace AdminBundle\Controller;

use AdminBundle\Entity\Banner;
use AdminBundle\Form\BannerType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/banner")
 */
class BannerController extends Controller
{
    /**
     * @Route("/", name="admin_banner_index")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $banners = $em->getRepository('AdminBundle:Banner')->createQueryBuilder('b')
            ->join('b.langs', 'l')->addSelect('l')
            ->join('l.lang', 'lg')
            ->where('lg.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->getQuery()->getResult();

        return array(
            'banners' => $banners
        );
    }

    /**
     * @Route("/dodaj", name="admin_banner_add")
     */
    public function addAction(Request $request)
    {
        $banner = new Banner();
        $form = $this->createForm(BannerType::class, $banner);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            foreach($banner->getLangs() as $lang)
            {
                $lang->setBanner($banner);
                $em->persist($lang);
            }

            $file = $banner->getImage();
            if($file instanceof UploadedFile)
            {
                $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('banner_files_directory'));
                $banner->setImage($fileName);
            }
            $em->persist($banner);
            try {
                $em->flush();
                $this->addFlash('success', 'Banner został poprawnie dodany.');
            } catch(\Exception $e)
            {
                $this->addFlash('error', $e->getMessage());
            }
            return $this->redirectToRoute('admin_banner_index');
        }

        return $this->render('@Admin/Banner/add_edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edytuj/{id}", name="admin_banner_edit")
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $banner = $em->getRepository('AdminBundle:Banner')->find($id);
        if(!$banner)
            throw $this->createNotFoundException();

        $image = $banner->getImage();
        if($image and file_exists($this->getParameter('banner_files_directory') . DIRECTORY_SEPARATOR . $image))
            $banner->setImage(new File($this->getParameter('banner_files_directory') . DIRECTORY_SEPARATOR . $image));

        $form = $this->createForm(BannerType::class, $banner, array(
            'edit' => true
        ));
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $file = $banner->getImage();
            if($file instanceof UploadedFile)
            {
                $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('banner_files_directory'));
                $banner->setImage($fileName);
            } else {$banner->setImage($image);}

            $em = $this->getDoctrine()->getManager();
            $em->persist($banner);
            try {
                $em->flush();
                $this->addFlash('success', 'Banner został poprawnie zmieniony.');
            } catch(\Exception $e)
            {
                $this->addFlash('error', $e->getMessage());
            }
            return $this->redirectToRoute('admin_banner_index');
        }

        return $this->render('@Admin/Banner/add_edit.html.twig', [
            'form' => $form->createView(),
            'banner' => $banner
        ]);
    }

    /**
     * @Route("/usun/{id}", name="admin_banner_delete")
     */
    public function removeAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $banner = $em->getRepository('AdminBundle:Banner')->find($id);
        if(!$banner)
            throw $this->createNotFoundException();

        $em->remove($banner);
        $em->flush();
        return $this->redirectToRoute('admin_banner_index');
    }
}
