<?php

namespace AppBundle\Controller;

use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * @Route("events")
 */
class EventsController extends Controller
{

    public function selectFromAuction(Request $request) { 
        
        // GET 
        $filters = $request->get('_filter');
        
        $selectedDate = array('time' => $filters['time'], 'selected' => false);
        switch($selectedDate['time']) {
            case "Dzisiaj" : $selectedDate['time'] = 'now';
                             $selectedDate['selected'] = true;
                break;
            case "Ten tydzień" : $selectedDate['time'] = '+1 week';
                                 $selectedDate['selected'] = true;
                break;
            case "Ten miesiąc" : $selectedDate['time']= 'first day of next month';
                                 $selectedDate['selected'] = true;
                break;
            default : $selectedDate['time']= null;
                      $selectedDate['selected'] = false;
                break;
        } 
        
        
        // create auctions select          
        $emm = $this->getDoctrine()->getManager();
   
        $qb = $emm->getRepository('AppBundle:Auction')->createQueryBuilder('a');
        $auctions = $qb->select('a.id, a.image, a.startDate, a.endDate')                        
                        ->join('a.langs', 'l')->addSelect('l.title, l.description')
                        ->join('l.lang', 'Language')->addSelect('Language.shortcut')
                        ->andWhere('Language.shortcut = :shortcut')
                        ->setParameter(':shortcut', $request->getLocale()); 
        
        if(empty($filters['type']) and empty($filters['city']) and empty($filters['time'])) {
            $auctions ->andWhere('a.endDate > :endDate')
                    ->setParameter(':endDate', new \DateTime('now'));
            $auctions ->getQuery()->getResult();        
            
            return $auctions->getQuery()->getResult();
        }  

        if(!empty($filters['type']) and empty($filters['city']) and empty($filters['time'])) {
            if($filters['type'] != 16 || $filters['type'] != 17) {
                return false;
            }
            $auctions ->andWhere('a.endDate > :endDate')
                        ->setParameter(':endDate', new \DateTime('now'));
            $auctions ->getQuery()->getResult();   
            
            return $auctions->getQuery()->getResult();
        } 
        
        
        return false;   
    }
 
 
    /* SELECT CITY
     * ===============================
     */
    public function exhibitionCity($request) {
                
        $em = $this->getDoctrine()->getManager();
        $city = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e');
        $city->select('e.city')
                ->distinct()
                ->join('e.langs', 'langs')
                ->join('langs.lang', 'Language')
                ->andWhere('e.approved = :approved')
                ->andWhere('e.enabled = :enabled')
                ->andWhere('Language.shortcut= :shortcut')
                ->andWhere('e.endDate >:endDate')
                ->setParameter(':endDate', new \DateTime('now'))
                ->setParameter(':approved', 1)
                ->setParameter(':enabled', 1)
                ->setParameter(':shortcut', $request->getLocale()); 

        $result = $city->getQuery()->getResult();
        
        return $result;

    }
    
    public function houseAuctionCity($request) {
                
        $em = $this->getDoctrine()->getManager();
        $city = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha');
        $city->select('ha.city')
                ->distinct()
                ->join('ha.langs', 'langs')
                ->join('langs.lang', 'Language')      
                ->andWhere('Language.shortcut= :shortcut')
                ->andWhere('ha.approved = :approved')
                ->andWhere('ha.status IN(0,1)')
                ->setParameter(':approved', 1)
                ->setParameter(':shortcut', $request->getLocale()); 

        $result = $city->getQuery()->getResult();
        
        return $result;

    }
    
    public function eventCity($request, $category=null) {
        $em = $this->getDoctrine()->getManager();
        $city = $em->getRepository('AppBundle:Event')->createQueryBuilder('e');
        $city->select('e.city')
                ->distinct()
                ->join('e.langs', 'langs')
                ->join('langs.lang', 'Language')     
                ->andWhere('Language.shortcut= :shortcut')
                ->andWhere('e.endDate >:endDate')
                ->setParameter(':endDate', new \DateTime('now'))
                ->setParameter(':shortcut', $request->getLocale()); 
        if($category != null) {
            $city->join('e.category', 'ec')
                    ->andWhere('ec.id = :id')
                    ->setParameter(':id', $category);
        }

        $result = $city->getQuery()->getResult();
        
        return $result;
    }
    
    /* MERGE CITY
     * ==================================
     */
    public function mergeCity($request) {
        $ha = $this->houseAuctionCity($request); 
        $ex = $this->exhibitionCity($request); 
        $ev = $this->eventCity($request);
        
        $evAndEx = array_merge_recursive($ev, $ex);
        $evAndEx = $this->super_unique($evAndEx);
        
        array_multisort($ha);
        array_multisort($ex);
        array_multisort($ev);
        array_multisort($evAndEx);
        
        $filters = $request->get('_filter');
        // Aukcje
        if($filters['type'] == 16 || $filters['type'] == 17) {
            return $ha;
        } 
        // Wystawy
        elseif($filters['type'] == 8 || $filters['type'] == 12) {
            $ex = $this->exhibitionCity($request); 
            $ev = $this->eventCity($request, $filters['type'] = 6);

            $evAndEx = array_merge_recursive($ev, $ex);
            $evAndEx = $this->super_unique($evAndEx);
            return $evAndEx;
        } 
        // Targi
        elseif($filters['type'] == 18 || $filters['type'] == 21) {
            $ev = $this->eventCity($request, 10);
            return $ev;
        }
        // Warsztaty 
        elseif($filters['type'] == 22 || $filters['type'] == 23) {
            $ev = $this->eventCity($request, 11);
            return $ev;
        }
        $merge = array_merge_recursive($ha, $ex);
        $allCity = array_merge_recursive($merge, $ev);
        $allCity = $this->super_unique($allCity);
        array_multisort($allCity);


        return $allCity;
    }
    
    public function super_unique($array)
    {
      $result = array_map("unserialize", array_unique(array_map("serialize", $array)));

      foreach ($result as $key => $value)
      {
        if ( is_array($value) )
        {
          $result[$key] = $this->super_unique($value);
        }
      }

      return $result;
    }
    
    
    /* SELECT RESULTS
     * =======================================
     */   
    public function houseAuction($request)
    {        
        // GET 
        $filters = $request->get('_filter');
        
        $selectedDate = $this->selectedDate($request);
                
        $em = $this->getDoctrine()->getManager();
        $house = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha');
        $house->select('ha.id, ha.city, ha.image, ha.addDate, ha.startDate, ha.approved, ha.status')
                ->join('ha.langs', 'hal')->addSelect('hal.title, hal.description')
                ->join('hal.lang', 'lang')->addSelect('lang.shortcut, lang.enabled')
                ->andWhere('ha.approved = :approved')
                ->andWhere('lang.shortcut= :shortcut')
                ->andWhere('ha.status IN(0,1)')
                ->setParameter(':approved', 1)
                ->setParameter(':shortcut', $request->getLocale());        
        
        if(empty($filters['type']) and empty($filters['city']) and empty($filters['time'])) {
            $house ->getQuery()->getResult();        
            
            return $house->getQuery()->getResult();
        } elseif(!empty($filters['type']) and empty($filters['city']) and empty($filters['time'])) {
            if($filters['type'] == 16 ||  $filters['type'] == 17) {
                $house ->getQuery()->getResult();     
            
                 return $house->getQuery()->getResult();
            } else {
                return false;
            }     

        } elseif(empty($filters['type']) and !empty($filters['city']) and empty($filters['time']) ) {
            $house->andWhere('ha.city = :city')
                    ->setParameter(':city', $filters['city']);
            $house ->getQuery()->getResult();   
            
            return $house->getQuery()->getResult();
                
        } elseif(empty($filters['type']) and empty($filters['city']) and !empty($filters['time']) ) {
            $house ->getQuery()->getResult();     
            
            return $house->getQuery()->getResult();
        } elseif(!empty($filters['type']) and !empty($filters['city']) and empty($filters['time']) ) {
            if($filters['type'] == 16 ||  $filters['type'] == 17) {
                $house->andWhere('ha.city = :city')
                        ->setParameter(':city', $filters['city']);
                $house ->getQuery()->getResult();  
                return $house->getQuery()->getResult(); 
            } else {
                return false;
            }         
  
        } elseif(!empty($filters['type']) and empty($filters['city']) and !empty($filters['time']) ) {
            if($filters['type'] == 16 ||  $filters['type'] == 17) {
                $house ->getQuery()->getResult();  
                return $house->getQuery()->getResult(); 
            } else {
                return false;
            }                 
        } elseif(empty($filters['type']) and !empty($filters['city']) and !empty($filters['time']) ) {            
            $house->andWhere('ha.city = :city')
                    ->setParameter(':city', $filters['city']);
            $house ->getQuery()->getResult();  
            return $house->getQuery()->getResult();             
                
        } elseif(!empty($filters['type']) and !empty($filters['city']) and !empty($filters['time']) ) {
            if($filters['type'] == 16 ||  $filters['type'] == 17) {
                    $house->andWhere('ha.city = :city')
                            ->setParameter(':city', $filters['city']);
                    $house ->getQuery()->getResult();  
                    return $house->getQuery()->getResult(); 
            } else {
                return false;
            } 
        }
        
        return false;      
        
        
        
//        if(empty($filters['type']) and empty($filters['city']) and empty($filters['time'])) {
//                  
//        } elseif(!empty($filters['type']) and empty($filters['city']) and empty($filters['time']) ) {
//                
//        } elseif(empty($filters['type']) and !empty($filters['city']) and empty($filters['time']) ) {
//                
//        } elseif(empty($filters['type']) and empty($filters['city']) and !empty($filters['time']) ) {
//                
//        } elseif(!empty($filters['type']) and !empty($filters['city']) and empty($filters['time']) ) {
//                
//        } elseif(!empty($filters['type']) and empty($filters['city']) and !empty($filters['time']) ) {
//                
//        } elseif(empty($filters['type']) and !empty($filters['city']) and !empty($filters['time']) ) {
//                
//        } elseif(!empty($filters['type']) and !empty($filters['city']) and !empty($filters['time']) ) {
//        
//        }
        
    }
    
    
    
    
    public function museumExhibition($request)
    {
        $filters = $request->get('_filter');
        
        $selectedDate = $this->selectedDate($request);
                
        $em = $this->getDoctrine()->getManager();
        $exhib = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e');
        $exhib->select('e.id, e.image, e.startDate, e.endDate, e.approved, e.enabled, e.address, e.city')
                ->join('e.langs', 'langs')->addSelect('langs.title, langs.description')
                ->join('langs.lang', 'Language')->addSelect('Language.shortcut')
                ->andWhere('e.approved = :approved')
                ->andWhere('e.enabled = :enabled')
                ->andWhere('Language.shortcut= :shortcut')
                ->setParameter(':approved', 1)
                ->setParameter(':enabled', 1)
                ->setParameter(':shortcut', $request->getLocale());

        if(empty($filters['type']) and empty($filters['city']) and empty($filters['time'])) { 
            $exhib->andWhere('e.endDate > :endDate')
                        ->setParameter(':endDate', new \DateTime('now'));
                $exhib ->getQuery()->getResult();  
                return $exhib->getQuery()->getResult();
            
        } elseif(!empty($filters['type']) and empty($filters['city']) and empty($filters['time']) ) {
            if($filters['type'] == 8 ||  $filters['type'] == 12) {
                $exhib->andWhere('e.endDate > :endDate')
                        ->setParameter(':endDate', new \DateTime('now'));
                $exhib ->getQuery()->getResult();  
                return $exhib->getQuery()->getResult();
            } else {
                return false;
            }
                
        } elseif(empty($filters['type']) and !empty($filters['city']) and empty($filters['time']) ) {
            $exhib->andWhere('e.endDate > :endDate')
                        ->setParameter(':endDate', new \DateTime('now'))
                    ->andwhere('e.city = :city')
                    ->setParameter(':city', $filters['city'] );
                $exhib ->getQuery()->getResult();  
                
                return $exhib->getQuery()->getResult(); 
                
        } elseif(empty($filters['type']) and empty($filters['city']) and !empty($filters['time']) ) {
            $exhib ->andWhere('e.startDate > :startDate')
                        ->andWhere('e.endDate < :endDate')
                        ->setParameter(':startDate', new \DateTime('now' . '-8 hours'))
                        ->setParameter(':endDate', new \DateTime($selectedDate['time'] . '+14 hours'));
                $exhib ->getQuery()->getResult();  
                
                return $exhib->getQuery()->getResult();   
                
        } elseif(!empty($filters['type']) and !empty($filters['city']) and empty($filters['time']) ) {
            if($filters['type'] == 8 ||  $filters['type'] == 12) {
                $exhib->andWhere('e.endDate > :endDate')
                        ->andWhere('e.city = :city')
                        ->setParameter(':city', $filters['city'])
                        ->setParameter(':endDate', new \DateTime('now'));
                $exhib ->getQuery()->getResult();  
                return $exhib->getQuery()->getResult();
            } else {
                return false;
            }
                
        } elseif(!empty($filters['type']) and empty($filters['city']) and !empty($filters['time']) ) {
            if($filters['type'] == 8 ||  $filters['type'] == 12) {
                $exhib->andWhere('e.startDate > :startDate')
                        ->andWhere('e.endDate < :endDate')
                        ->setParameter(':startDate', new \DateTime('now' . '-8 hours'))
                        ->setParameter(':endDate', new \DateTime($selectedDate['time'] . '+14 hours'));
                $exhib ->getQuery()->getResult();  
            return $exhib->getQuery()->getResult();
            } else {
                return false;
            }
        } elseif(empty($filters['type']) and !empty($filters['city']) and !empty($filters['time']) ) {
            $exhib->andWhere('e.startDate > :startDate')
                        ->andWhere('e.endDate < :endDate')
                        ->andWhere('e.city = :city')
                        ->setParameter(':city', $filters['city'])
                        ->setParameter(':startDate', new \DateTime('now' . '-8 hours'))
                        ->setParameter(':endDate', new \DateTime($selectedDate['time'] . '+14 hours'));
                $exhib ->getQuery()->getResult();  
            return $exhib->getQuery()->getResult();
            
        } elseif(!empty($filters['type']) and !empty($filters['city']) and !empty($filters['time']) ) {
            if($filters['type'] == 8 ||  $filters['type'] == 12) {
                $exhib->andWhere('e.startDate > :startDate')
                        ->andWhere('e.endDate < :endDate')
                        ->andWhere('e.city = :city')
                        ->setParameter(':city', $filters['city'])
                        ->setParameter(':startDate', new \DateTime('now' . '-8 hours'))
                        ->setParameter(':endDate', new \DateTime($selectedDate['time'] . '+14 hours'));
                $exhib ->getQuery()->getResult();  
            return $exhib->getQuery()->getResult();
            } else {
                return false;
            }
        }        
        
        return false;
    }    

    public function selectedDate($request) {
        
        $filters = $request->get('_filter');
        
        $selectedDate = array('time' => $filters['time'], 'selected' => false);
         switch($selectedDate['time']) {
            case "Dzisiaj" : $selectedDate['time'] = 'now';
                             $selectedDate['selected'] = true;
                break;
            case "Today" : $selectedDate['time'] = 'now';
                            $selectedDate['selected'] = true;
                break;
            case "Ten tydzień" : $selectedDate['time'] = '+1 week';
                                 $selectedDate['selected'] = true;
                break;
            case "This+week" : $selectedDate['time'] = '+1 week';
                                $selectedDate['selected'] = true;
                break;
            case "Ten miesiąc" : $selectedDate['time']= 'first day of next month';
                                 $selectedDate['selected'] = true;
                break;
            case "This+month" : $selectedDate['time']= 'first day of next month';
                                 $selectedDate['selected'] = true;
                break;
            default : $selectedDate['time']= null;
                      $selectedDate['selected'] = false;
                break;
        }
        return $selectedDate;
    }
    
    /**
     * @Route("/", name="events")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        // GET 
        $filters = $request->get('_filter');
        
         $selectedDate = $this->selectedDate($request);

        if(is_array($auctions = $this->selectFromAuction($request))) {
            array_unique($auctions, SORT_REGULAR);
        } else {
            $auctions = array();
        }

        
        // DLA wystaw 
        // 
        // getDoctrine
        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('AppBundle:Event')->createQueryBuilder('e');
        $select = $qb->select('e.id, e.image, e.slug, e.startDate, e.city')
                        ->join('e.langs', 'langs')->addSelect('langs.title, langs.description')
                        ->join('langs.lang', 'Language')->addSelect('Language.shortcut')
                        ->where('Language.shortcut = :shortcut')
                        ->andWhere()
                        ->setParameter(':shortcut', $request->getLocale());                        
        
        if(empty($filters['type']) and empty($filters['city']) and empty($filters['time'])) {
                $select ->andWhere('e.endDate > :endDate')
                        ->setParameter(':endDate', new \DateTime('now'));
                $select ->getQuery()->getResult();  
        } elseif(!empty($filters['type']) and empty($filters['city']) and empty($filters['time']) ) {
                $select ->join('e.category', 'ec')
                        ->join('ec.langs', 'ecl')->addSelect('ecl.id')   
                        ->andWhere('e.endDate > :endDate')
                        ->andWhere('ecl.id = :eclLang')
                        ->setParameter(':eclLang', $filters['type'])
                        ->setParameter(':endDate', new \DateTime('now'));
                $select->getQuery()->getResult();
        } elseif(empty($filters['type']) and !empty($filters['city']) and empty($filters['time']) ) {
                $select ->andWhere('e.city = :city')
                        ->andWhere('e.endDate > :endDate')
                        ->setParameter(':city', $filters['city'])
                        ->setParameter(':endDate', new \DateTime('now'));
                $select->getQuery()->getResult();
        } elseif(empty($filters['type']) and empty($filters['city']) and !empty($filters['time']) ) {
                $select ->andWhere('e.startDate > :startDate')
                        ->andWhere('e.endDate < :endDate')
                        ->setParameter(':startDate', new \DateTime('now' . '-6 hours'))
                        ->setParameter(':endDate', new \DateTime($selectedDate['time'] . '+14 hours'));
                $select->getQuery()->getResult();
        } elseif(!empty($filters['type']) and !empty($filters['city']) and empty($filters['time']) ) {
                $select ->join('e.category', 'ec')
                        ->join('ec.langs', 'ecl')->addSelect('ecl.id')   
                        ->andWhere('e.endDate > :endDate')
                        ->andWhere('ecl.id = :eclLang')
                        ->setParameter(':eclLang', $filters['type'])
                        ->setParameter(':endDate', new \DateTime('now'))
                        ->andWhere('e.city = :city')
                        ->setParameter(':city', $filters['city']); 
                $select->getQuery()->getResult();
        } elseif(!empty($filters['type']) and empty($filters['city']) and !empty($filters['time']) ) {
                $select ->join('e.category', 'ec')
                        ->join('ec.langs', 'ecl')->addSelect('ecl.id')   
                        ->andWhere('e.startDate > :startDate')
                        ->andWhere('e.endDate < :endDate')
                        ->andWhere('ecl.id = :eclLang')
                        ->setParameter(':eclLang', $filters['type'])
                        ->setParameter(':startDate', new \DateTime('now' . '-12 hours'))
                        ->setParameter(':endDate', new \DateTime($selectedDate['time'] . '+14 hours'));
                $select->getQuery()->getResult();
        } elseif(empty($filters['type']) and !empty($filters['city']) and !empty($filters['time']) ) {
                $select ->andWhere('e.city = :city')
                        ->andWhere('e.startDate > :startDate')
                        ->andWhere('e.endDate < :endDate')
                        ->setParameter(':city', $filters['city'])
                        ->setParameter(':startDate', new \DateTime('now' . '-12 hours'))
                        ->setParameter(':endDate', new \DateTime($selectedDate['time'] . '+14 hours'));
                $select->getQuery()->getResult();
        } elseif(!empty($filters['type']) and !empty($filters['city']) and !empty($filters['time']) ) {
                $select ->join('e.category', 'ec')
                        ->join('ec.langs', 'ecl')->addSelect('ecl.id') 
                        ->andWhere('e.city = :city')
                        ->andWhere('ecl.id = :eclLang')
                        ->andWhere('e.startDate > :startDate')
                        ->andWhere('e.endDate < :endDate')
                        ->setParameter(':eclLang', $filters['type'])
                        ->setParameter(':city', $filters['city'])
                        ->setParameter(':startDate', new \DateTime('now' . '-12 hours'))
                        ->setParameter(':endDate', new \DateTime($selectedDate['time'] . '+14 hours'));
                $select->getQuery()->getResult();
        }
        
//        $select->getQuery()->getResult();
        

          
        // categories
        $qb = $em->getRepository('AppBundle:EventCategoryLang')->createQueryBuilder('c');
        $categories = $qb->select('c.id, c.title')
                          ->join('c.lang', 'Language')->addSelect('Language.shortcut')
                          ->where('Language.shortcut = :shortcut')
                          ->setParameter(':shortcut', $request->getLocale())
                          ->getQuery()
                          ->getResult();
        
        $i = 0;
        foreach($categories as $key => $value) {
            if($value['id'] == $filters['type']) {
                $categories[$i]['selected'] = true;
            } else {
                $categories[$i]['selected'] = false;
            }
        $i++;
        }       
    

        
        // cities         
//        $rsm = new ResultSetMapping();
//        $rsm->addScalarResult('city', 'c');
//        $cities = $em->createNativeQuery('SELECT DISTINCT c.city FROM `event` c WHERE c.endDate > "'.date('Y-m-d H:i:s').'"', $rsm)->getResult();
 
        $cities = $this->mergeCity($request);
        
        foreach( $cities as &$city ){
            if( $city['city'] == $filters['city'] )
            {
                $city['select'] = true;
            }
            else
            {
                $city['select'] = false;
            }
        }
//        print_r($cities);   
  
        
        
        $allCities = $this->mergeCity($request);
        $house = $this->houseAuction($request);
        $museum = $this->museumExhibition($request);
        
        // Paginator
        $paginator  = $this->get('knp_paginator');
          $pagination = $paginator->paginate(
              $select,
              $request->query->getInt('page', 1),
              150
          );

        // return
        return array(
            'select'     => $select,
            'pagination' => $pagination,
            'categories' => $categories,
            'cities'     => $cities,
            'times'      => $selectedDate,
            'auctions'   => $auctions,
            'house' => $house,
            'museum' => $museum,
            'allcities' => $allCities
        );

    }

    /**
     * @Route("/filter", name="events_filter")
     * @Template()
     */
    public function filterAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $time = $request->get('time');
        $city = $request->get('city');
        $type = $request->get('type');

        if(!empty($time))
        {
            $startTime = new \DateTime('now');
            switch($time) {
                case 'today': $endTime = new \DateTime('now'); break;
                case '+1 week': $endTime = new \DateTime('+1 week'); break;
                case '+1 month': $startTime = new \DateTime('first day of this month'); $endTime = new \DateTime('first day of next month'); break;
                default: $endTime = new \DateTime('today'); break;
            }
        }

        //Get All
        $events = $em->getRepository('AppBundle:Event')->createQueryBuilder('e')
            ->join('e.langs', 'el')->addSelect('el.title, el.description')
            ->join('el.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setMaxResults(6);
        $exhibitions = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e')
            ->join('e.langs', 'lg')->addSelect('lg.title, lg.description')
            ->join('lg.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setMaxResults(6)
            ->join('e.author', 'a')->addSelect('a');
        $auctions = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('e')
            ->join('e.langs', 'hl')->addSelect('hl.title, hl.description')
            ->join('hl.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setMaxResults(6)
            ->join('e.author', 'a')->addSelect('a');

        if(isset($startTime))
        {
            $events->andWhere('e.startDate >= :start_date')
                ->andWhere('e.startDate <= :end_date')
                ->setParameter(':start_date', $startTime)
                ->setParameter(':end_date', $endTime);
            $exhibitions->andWhere('e.startDate >= :start_date')
                ->andWhere('e.startDate <= :end_date')
                ->setParameter(':start_date', $startTime)
                ->setParameter(':end_date', $endTime);
            $auctions->andWhere('e.startDate >= :start_date')
                ->andWhere('e.startDate <= :end_date')
                ->setParameter(':start_date', $startTime)
                ->setParameter(':end_date', $endTime);
        }

        if(!empty($city))
        {
            $events->andWhere('e.city = :city')
                ->setParameter(':city', $city);
            $exhibitions->andWhere('e.city = :city')
                ->setParameter(':city', $city);
            $auctions->andWhere('e.city = :city')
                ->setParameter(':city', $city);
        }

        $events->orderBy('e.startDate', 'DESC');
        $exhibitions->orderBy('e.startDate', 'DESC');
        $auctions->orderBy('e.startDate', 'DESC');

        if(!empty($type))
        {
            $events->join('e.category', 'c')
                ->andWhere('c.id = :id')
                ->setParameter(':id', $type);
            return array(
                'events' => $events->getQuery()->getResult(),
                'exhibitions' => false,
                'auctions' => false
            );
        }

        return array(
            'events' => $events->getQuery()->getResult(),
            'exhibitions' => $exhibitions->getQuery()->getResult(),
            'auctions' => $auctions->getQuery()->getResult()
        );
    }

//    /**
//     * @Route("/{id}-{slug}", name="event", requirements={
//     *  "id": "\d+",
//     *  "slug": "^[a-z0-9]+(?:-[a-z0-9]+)*$"
//     * })
//     * @Template()
//     */
//    public function eventAction($id, Request $request)
//    {
//        $em = $this->getDoctrine()->getManager();
//
//        $event = $em->getRepository('AppBundle:Event')->createQueryBuilder('e')
//            ->join('e.langs', 'el')->addSelect('el.title, el.description')
//            ->join('el.lang', 'l')
//            ->join('e.category', 'c')
//            ->join('c.langs', 'cl')->addSelect('cl.title AS category_title')
//            ->join('cl.lang', 'cll')
//            ->where('e.id = :id')
//            ->andWhere('l.shortcut = :shortcut')
//            ->andWhere('cll.shortcut = :shortcut')
//            ->setParameter(':shortcut', $request->getLocale())
//            ->setParameter(':id', $id)
//            ->setMaxResults(6)->getQuery()->getSingleResult();
//        if(!$event)
//            throw $this->createNotFoundException();
//
//        return array(
//            'event' => $event
//        );
//    }
    
    /**
     * @Route("/{slug}", name="event")
     * @Template()
     */
    public function eventAction($slug, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $event = $em->getRepository('AppBundle:Event')->createQueryBuilder('e')
            ->join('e.langs', 'el')->addSelect('el.title, el.description')
            ->join('el.lang', 'l')
            ->join('e.category', 'c')
            ->join('c.langs', 'cl')->addSelect('cl.title AS category_title')
            ->join('cl.lang', 'cll')
            ->where('e.slug Like :slug')
            ->andWhere('l.shortcut = :shortcut')
            ->andWhere('cll.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':slug', $slug)
            ->setMaxResults(6)->getQuery()->getSingleResult();
        if(!$event)
            throw $this->createNotFoundException();

        return array(
            'event' => $event
        );
    }
    
//    /**
//     * @Route("/exhibition/{id}-{slug}", name="exhibition", requirements={
//     *  "id": "\d+",
//     *  "slug": "^[a-z0-9]+(?:-[a-z0-9]+)*$"
//     * })
//     * @Template()
//     */
//    public function exhibitionAction($id, $slug, Request $request)
//    {
//        $em = $this->getDoctrine()->getManager();
//        
//        $exhibition = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e')
//            ->select('e, a')
//            ->join('e.author', 'a')
//            ->join('e.langs', 'el')->addSelect('el.title, el.description')
//            ->join('el.lang', 'l')
//            ->where('e.id = :id')
//            ->andWhere('l.shortcut = :shortcut')
//            ->setParameter(':shortcut', $request->getLocale())
//            ->setParameter(':id', $id)
//            ->getQuery()->getSingleResult();
//        if(!$exhibition)
//            throw $this->createNotFoundException ();
//        
//        return array(
//            'exhibition' => $exhibition
//        );
//    }
    
    /**
     * @Route("/wystawy/{id}", name="exhibition")
     * @Template()
     */
    public function exhibitionAction(Request $request, $id) 
    {
        $em = $this->getDoctrine()->getManager();
        
        $exhibition = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e')
            ->select('e.id, e.image, e.startDate, e.endDate, e.enabled, e.approved, e.address, e.city')
            ->join('e.author', 'a')->addSelect('a.fullname')
            ->join('e.langs', 'el')->addSelect('el.title, el.description')
            ->join('el.lang', 'l')
            ->andWhere('e.id = :id')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':id', $id)
            ->getQuery()->getSingleResult();
        if(!$exhibition)
            throw $this->createNotFoundException ();
        
        return array(
            'exhibition' => $exhibition
        );
    }
    
//    /**
//     * @Route("/exhibition/{id}", name="exhibition")
//     * @Template()
//     */
//    public function exhibitionAction($id, Request $request)
//    {
//        $em = $this->getDoctrine()->getManager();
//        
//        $exhibition = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e')
//            ->select('e.id')
////            ->join('e.author', 'a')
////            ->join('e.langs', 'el')->addSelect('el.title, el.description')
////            ->join('el.lang', 'l')
//            ->where('e.id = :id')
////            ->andWhere('l.shortcut = :shortcut')
////            ->setParameter(':shortcut', $request->getLocale())
//            ->setParameter(':id', $id)
//            ->getQuery()->getSingleResult();
//        if(!$exhibition)
//            throw $this->createNotFoundException ();
//        
//        return array(
//            'exhibition' => $exhibition
//        );
//    }
    

    
    /**
     * @Route("/houseauction/{id}-{slug}", name="houseauction", requirements={
     *  "id": "\d+",
     *  "slug": "^[a-z0-9]+(?:-[a-z0-9]+)*$"
     * })
     * @Template()
     */
    public function auctionAction($id, $slug, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $auction = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('au')
            ->join('au.langs', 'hl')->addSelect('hl.title, hl.description,h1.metatitle')
            ->join('hl.lang', 'l')
            ->join('au.author', 'a')->addSelect('a')
            ->where('au.id = :id')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':id', $id)
            ->getQuery()->getResult();
        if(!$auction)
            throw $this->createNotFoundException ();

        $auction = $auction[0];
        
        $worksQb = $em->getRepository('AppBundle:HouseAuctionWork')->createQueryBuilder('aw');
        $worksQuery = $worksQb->select('aw, w')
            ->join('aw.work', 'w')
            ->andWhere('aw.auction = :auction')
            ->setParameter(':auction', $auction[0])
            ->getQuery()->getResult();
        
        if($user = $this->getUser())
        {
            $form = $this->createFormBuilder();
            $form->add('content', TextareaType::class, array(
                'label' => false,
                'attr' => array('style' => 'min-height: 150px', 'placeholder' => 'main.wpisz_wiadomosc'),
                'constraints' => array(new NotBlank())
            ));
            $form = $form->getForm();
            $form->handleRequest($request);
            if($form->isValid() && $form->isSubmitted())
            {
                try
                {
                    $message = \Swift_Message::newInstance()
                        ->setSubject($this->get('translator')->trans('mail.zgloszenie_na_aukcje'))
                        ->setFrom($user->getEmail())
                        ->setTo($auction[0]->getAuthor()->getEmail())
                        ->setBody(
                            $this->renderView('AppBundle:Emails:auction_ask.html.twig', array(
                                'auction' => $auction,
                                'author' => $user,
                                'content' => $form->get('content')->getData()
                            )),
                            'text/html'
                        );
                    $this->get('mailer')->send($message);

                    $message = \Swift_Message::newInstance()
                        ->setSubject($this->get('translator')->trans('mail.zgloszenie_na_aukcje'))
                        ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                        ->setTo($this->getParameter('mail_address_from'))
                        ->setBody(
                            $this->renderView('AppBundle:Emails:auction_ask.html.twig', array(
                                'auction' => $auction,
                                'author' => $user,
                                'content' => $form->get('content')->getData(),
                                'for_admin' => true
                            )),
                            'text/html'
                        );
                    $this->get('mailer')->send($message);

                    $this->addFlash('success', $this->get('translator')->trans('auctions.zapytanie_wyslane'));
                }
                catch(\Exception $e)
                {
                    $this->addFlash('error', $this->get('translator')->trans('mail.nie_udalo_sie'));
                }
            }
        }
        
        if($user = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:HouseAuctionObserve')->findBy(array(
                'auction' => $auction[0],
                'user' => $user
            ));
        }
        return array(
            'auction' => $auction,
            'works' => $worksQuery,
            'observe' => isset($observe) ? $observe : null,
            'form' => isset($form) ? $form->createView(): null
        );
    }
}
