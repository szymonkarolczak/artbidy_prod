<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/work")
 */
class SearchWorkController extends Controller
{
    /**
     * @Route("/search/artist", name="work_search_artist")
     */
    public function searchArtistAction(Request $request)
    {
        $response = new JsonResponse();
        $em = $this->getDoctrine()->getManager();
        $q = $request->get('term');

        $result = $this->_prepare($q, 'artist');
        if(!$result) $result = [];
        
        $qb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');
        $data = $qb->select('u.fullname, u.username')
            ->where($qb->expr()->like('u.fullname', ':q'))
            ->andWhere('u.enabled = :approved')
            ->andWhere($qb->expr()->like('u.roles', ':role'))
            ->setParameter(':approved', true)
            ->setParameter(':role', '%ROLE_ARTYSTA%')
            ->setParameter(':q', '%'.$q.'%')
            ->distinct()
            ->getQuery()->getResult();
        
        if($data)
        {
            foreach($data as $value)
            {
                $result[] = array(
                    'id' => $value['fullname'],
                    'label' => $value['fullname'],
                    'value' => $value['username'],
                );
            }
        }

        $response->setData($result);
        return $response;
    }

    /**
     * @Route("/search/technique", name="work_search_technique")
     */
    public function searchTechniqueAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $response = new JsonResponse();
        $data = array();

        $lang = $em->getRepository('AppBundle:Language')->findOneBy(array(
            'shortcut' => $request->getLocale()
        ));

        $qb = $em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc');
        $query = $qb
            ->where($qb->expr()->like('wc.value', ':val'))
            ->andWhere('wc.type = :type')
            ->andWhere('wc.lang = :lang')
            ->setParameter(':type', 'technika')
            ->setParameter(':lang', $lang)
            ->setParameter(':val', '%'.$request->get('term').'%')
            ->getQuery()->getResult();

        foreach($query as $row)
            $data[] = array(
                'id' => $row->getValue(),
                'label' => $row->getValue(),
                'value' => $row->getValue(),
            );

        $response->setData($data);
        return $response;
    }

    /**
     * @Route("/search/style", name="work_search_style")
     */
    public function searchStyleAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $response = new JsonResponse();
        $data = array();

        $lang = $em->getRepository('AppBundle:Language')->findOneBy(array(
            'shortcut' => $request->getLocale()
        ));

        $qb = $em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc');
        $query = $qb
            ->where($qb->expr()->like('wc.value', ':val'))
            ->andWhere('wc.type = :type')
            ->andWhere('wc.lang = :lang')
            ->setParameter(':type', 'styl')
            ->setParameter(':lang', $lang)
            ->setParameter(':val', '%'.$request->get('term').'%')
            ->getQuery()->getResult();

        foreach($query as $row)
            $data[] = array(
                'id' => $row->getValue(),
                'label' => $row->getValue(),
                'value' => $row->getValue(),
            );

        $response->setData($data);
        return $response;
    }

    private function _prepare($q, $field)
    {
        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');
        $data = $qb->select('w.artist')
            ->where($qb->expr()->like('w.'.$field, ':q'))
            ->andWhere('w.approved = :approved')
            ->setParameter(':approved', true)
            ->setParameter(':q', '%'.$q.'%')
            ->distinct()
            ->getQuery()->getResult();

        if(!$data)
            return false;

        $return = array();
        foreach($data as $value)
        {
            $return[] = array(
                'id' => $value['artist'],
                'label' => $value['artist'],
                'value' => $value['artist'],
            );
        }

        return $return;
    }
}
