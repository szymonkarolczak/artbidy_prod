<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @Route("/user/services")
 */
class ServicesController extends Controller
{
    /**
     * @Route("/{prefix}", name="user_services_role", requirements={
     *  "prefix": "(artist|gallery|auction-house|museum)"
     * })
     * @Template()
     */
    public function roleAction($prefix)
    {
        if(!($user = $this->getUser()))
            throw $this->createAccessDeniedException();

        switch($prefix)
        {
            case 'artist':
                if($this->isGranted('ROLE_GALERIA') || $this->isGranted('ROLE_DOM_AUKCYJNY') || $this->isGranted('ROLE_GALERIA'))
                    return $this->render('@User/Services/deny.html.twig');
                $role = 'artysta';
                break;
            case 'auction-house':
                if($this->isGranted('ROLE_ARTYSTA') || $this->isGranted('ROLE_GALERIA') || $this->isGranted('ROLE_GALERIA'))
                    return $this->render('@User/Services/deny.html.twig');
                $role = 'dom_aukcyjny';
                break;
            case 'gallery':
                if($this->isGranted('ROLE_ARTYSTA') || $this->isGranted('ROLE_DOM_AUKCYJNY') || $this->isGranted('ROLE_GALERIA'))
                    return $this->render('@User/Services/deny.html.twig');
                $role = 'galeria';
                break;
            case 'museum':
                if($this->isGranted('ROLE_ARTYSTA') || $this->isGranted('ROLE_DOM_AUKCYJNY') || $this->isGranted('ROLE_GALERIA'))
                    return $this->render('@User/Services/deny.html.twig');
                $role = 'muzeum';
                break;
        }

        return array(
            'price_3' => $this->getParameter($role . '_3msc'),
            'price_6' => $this->getParameter($role . '_6msc'),
            'price_12' => $this->getParameter($role . '_12msc'),
            'prefix' => $prefix
        );
    }

    /**
     * @Route("/{prefix}", name="user_services_service", requirements={
     *  "prefix": "(database|annoucements)"
     * })
     * @Template()
     */
    public function serviceAction($prefix, Request $request)
    {
        if(!($user = $this->getUser()))
            throw $this->createAccessDeniedException();

        $now = new \DateTime('now');
        $em = $this->getDoctrine()->getManager();

        $text = $em->getRepository('AppBundle:StaticPage')->createQueryBuilder('s')
            ->join('s.langs', 'sl')->select('sl.content')
            ->join('sl.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->andWhere('s.url = :url')
            ->setParameter(':url', $prefix)
            ->setParameter(':shortcut', $request->getLocale())
            ->getQuery()->getResult();

        switch($prefix)
        {
            case 'database':
                $userTime = $this->getUser()->getDatabase();

                break;
            case 'annoucements':
                $userTime = $this->getUser()->getAnnoucement();

                if($users = $request->get('showUsers'))
                {
                    $qb = $em->getRepository('AppBundle:ProfileObserve')->createQueryBuilder('po');
                    $observedUsers = $qb->select('po')
                        ->addSelect('u')
                        ->join('po.targetUser', 'u')
                        ->where('po.user = :user')
                        ->setParameter(':user', $this->getUser());

                    $observedUsers->andWhere($qb->expr()->like('u.roles', ':role'));
                    if($users == 'artists')
                    {
                        $observedUsers->setParameter(':role', '%ROLE_ARTYSTA%');
                    }
                    elseif($users == 'galleries')
                    {
                        $observedUsers->setParameter(':role', '%ROLE_GALERIA%');
                    }
                    elseif($users == 'auction-houses')
                    {
                        $observedUsers->setParameter(':role', '%ROLE_DOM_AUKCYJNY%');
                    }
                    else {throw $this->createNotFoundException();}

                    $observedUsers = $observedUsers->getQuery()->getResult();
                }
                else
                {
                    $observedHouses = $em->getRepository('AppBundle:HouseAuctionObserve')->createQueryBuilder('hao')
                        ->select('hao')
                        ->addSelect('a')
                        ->join('hao.auction', 'a')
                        ->where('hao.user = :user')
                        ->setParameter(':user', $this->getUser())
                        ->getQuery()->getResult();
                }


                break;
        }

        if($userTime)
        {
            $diff = $now->diff($userTime);
            if(!$diff->invert)
                $userEndTime = $userTime;
        }


        return array(
            'price_3' => $this->getParameter($prefix . '_3msc'),
            'price_6' => $this->getParameter($prefix . '_6msc'),
            'price_12' => $this->getParameter($prefix . '_12msc'),
            'prefix' => $prefix,
            'endTime' => isset($userEndTime) ? $userEndTime : false,
            'observedHouses' => isset($observedHouses) ? $observedHouses : false,
            'observedUsers' => isset($observedUsers) ? $observedUsers : false,
            'text' => isset($text[0]['content']) ? $text[0]['content'] : null
        );
    }

    /**
     * @Route("/library/get/{id}", name="user_services_reports_get", requirements={"id": "\d+"})
     */
    public function getReport($id)
    {
        if(!($user = $this->getUser()))
            throw $this->createNotFoundException();

        $em = $this->getDoctrine()->getManager();

        $report = $em->getRepository('AppBundle:Report')->createQueryBuilder('r')
            ->join('r.users', 'u', 'WITH', 'u.id = :id')
            ->where('r.id = :report_id')
            ->setParameter(':id', $user->getId())
            ->setParameter(':report_id', $id)
            ->getQuery()->getResult();

        if(!$report)
            return new RedirectResponse($this->generateUrl('user_services_reports'));

        $file = $report[0]->getFilename();
        $path = $this->getParameter('reports_directory') . DIRECTORY_SEPARATOR . $file;
        if(!$file || !file_exists($path))
        {
            $this->addFlash('error', $this->get('translator')->trans('raporty.poczekaj'));
            return new RedirectResponse($this->generateUrl('user_services_reports'));
        }

        list($name, $ext) = explode('.', $file);
        $tmpPath = $this->getParameter('tmp_directory') . DIRECTORY_SEPARATOR . $this->get('slugify')->slugify($report[0]->getTitle()) . '.'.$ext;
        if(copy($path, $tmpPath))
        {
            $response = new BinaryFileResponse($tmpPath);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
            return $response;
        }

        $this->addFlash('error', $this->get('translator')->trans('raporty.blad_pobierania'));
        return new RedirectResponse($this->generateUrl('user_services_reports'));
    }

    /**
     * @Route("/library", name="user_services_reports")
     * @Template()
     */
    public function serviceReportsAction(Request $request)
    {
        $userId = 0;

        if($user = $this->getUser())
        {
            $userId = $user->getId();
        }

        $em = $this->getDoctrine()->getManager();

        if($reportId = $request->request->get('reportId'))
        {
            $report = $em->getRepository('AppBundle:Report')->find($reportId);
            if(!$report)
                return new RedirectResponse($this->generateUrl('user_services_reports'));
        }

        $reports = $em->getRepository('AppBundle:Report')->createQueryBuilder('r')
            ->leftJoin('r.users', 'u', 'WITH', 'u.id = :id')->addSelect('COUNT(u.id) AS bought')
            ->groupBy('r.id')
            ->setParameter(':id', $userId)
            ->getQuery()->getResult();

        return array(
            'reports' => $reports
        );
    }

    private function getRoles(array $roles)
    {
        $role = null;
        if(in_array('ROLE_ARTYSTA', $roles)) { $role = $this->get('translator')->trans('roles.artysta'); }
        else if(in_array('ROLE_DOM_AUKCYJNY', $roles)) { $role = $this->get('translator')->trans('roles.dom_aukcyjny'); }
        else if(in_array('ROLE_EDYTOR', $roles)) { $role = $this->get('translator')->trans('roles.edytor'); }
        else if(in_array('ROLE_REDAKTOR', $roles)) { $role = $this->get('translator')->trans('roles.redaktor'); }
        else if(in_array('ROLE_GALERIA', $roles)) { $role = $this->get('translator')->trans('roles.galeria'); }
        else if(in_array('ROLE_ADMIN', $roles) || in_array('ROLE_SUPER_ADMIN', $roles)) { $role = $this->get('translator')->trans('roles.administrator'); }
        else {$role = $this->get('translator')->trans('roles.darmowe');}
        return $role;
    }

}
