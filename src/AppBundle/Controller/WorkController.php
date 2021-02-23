<?php

namespace AppBundle\Controller;

use AppBundle\Entity\AuctionBid;
use AppBundle\Model\Currency;
use AppBundle\Repository\WorkRepository;
use Doctrine\ORM\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use AppBundle\Entity\Work;
use AppBundle\Form\WorkType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Cocur\Slugify\Slugify;
use AppBundle\Entity\WorkBid;


/**
 * @Route("/work")
 */
class WorkController extends Controller
{
    /**
     * @Route("s/all", name="works_all")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $no_user_view = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->select('s.value')
            ->where('s.name = :name')
            ->setParameter(':name', 'security_user_logged_view')
            ->getQuery()->getSingleScalarResult();

        if (strip_tags($no_user_view) !== 'TAK' && !$this->getUser() && $request->query->getInt('page', 1) > 1) {
            return $this->render('AppBundle::loggedEx.html.twig');
        }

        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');
        $pinned = $qb
            ->select('w')
            ->leftJoin('w.auctionWorks', 'aw')->addSelect('aw')
            ->leftJoin('UserBundle:User', 'au', 'WITH', 'au.fullname = w.artist and au.roles LIKE :role_a')->addSelect('au.id, au.fullname')
            ->where('w.approved = :approved')
            ->andWhere('w.display = :approved')
            ->andWhere('w.pinned = :pinned')
            ->setMaxResults(6)
            ->orderBy('w.id', 'DESC')
            ->setParameter(':approved', true)
            ->setParameter(':pinned', true)
            ->setParameter(':role_a', '%ROLE_ARTYSTA%')
            ->getQuery()->getResult();
        $pinned = null;

        $qb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');
        $latest = $qb
            ->select('w')
            ->leftJoin('w.auctionWorks', 'aw')->addSelect('aw')
            ->leftJoin('UserBundle:User', 'au', 'WITH', 'au.fullname = w.artist and au.roles LIKE :role_a')->addSelect('au.id, au.fullname')
            ->where('w.approved = :approved')
            ->andWhere('w.display = :approved')
            ->setMaxResults(6)
            ->orderBy('w.id', 'DESC')
            ->setParameter(':approved', true)
            ->setParameter(':role_a', '%ROLE_ARTYSTA%')
            ->getQuery()->getResult();

        $latest = null;

        $qb1 = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');

        $qb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');
        $query = $qb->select('w')
            ->join('w.author', 'a')
            ->leftJoin('UserBundle:User', 'au', 'WITH', 'au.fullname = w.artist and au.roles LIKE :role_a')
            ->addSelect('au.id, au.fullname')
            ->where('w.approved = :approved')
            ->andWhere('w.display = :approved')
            ->setParameter(':approved', 1)
            ->setParameter(':role_a', '%ROLE_ARTYSTA%')
            ->orderBy('w.id', 'DESC');


        $currency = new Currency();

        $filters = $request->get('_filter');

        $paramClear = $request->get('clear');
        $clear = isset($paramClear) ? true : false;

        if (!$clear) {
            $filterPriceFrom = isset($filters['price_from']) ? $filters['price_from'] : $request->getSession()->get('filter_price_from');
            $filterPriceTo = isset($filters['price_to']) ? $filters['price_to'] : $request->getSession()->get('filter_price_to');
            $filterType = isset($filters['type']) ? $filters['type'] : $request->getSession()->get('filter_type');
            $filterArtist = isset($filters['artist']) ? $filters['artist'] : $request->getSession()->get('filter_artist');
            $filterTitle = isset($filters['title']) ? $filters['title'] : $request->getSession()->get('filter_title');
            $filterTechnique = isset($filters['technique']) ? $filters['technique'] : $request->getSession()->get('filter_technique');
            $filterStyle = isset($filters['style']) ? $filters['style'] : $request->getSession()->get('filter_style');

            $request->getSession()->set('filter_price_from', $filterPriceFrom);
            $request->getSession()->set('filter_price_to', $filterPriceTo);
            $request->getSession()->set('filter_type', $filterType);
            $request->getSession()->set('filter_artist', $filterArtist);
            $request->getSession()->set('filter_title', $filterTitle);
            $request->getSession()->set('filter_technique', $filterTechnique);
            $request->getSession()->set('filter_style', $filterStyle);
        } else {
            $this->clearFilter($request, 'filter_price_from');
            $this->clearFilter($request, 'filter_price_to');
            $this->clearFilter($request, 'filter_type');
            $this->clearFilter($request, 'filter_artist');
            $this->clearFilter($request, 'filter_title');
            $this->clearFilter($request, 'filter_technique');
            $this->clearFilter($request, 'filter_style');
        }

        $otherLang = $request->getLocale() === 'en' ? 'pl' : 'en';

        if (isset($filterPriceTo) && !empty($filterPriceTo)) {
            $query->andWhere('COALESCE(w.price,0) >= :price_from')->andWhere('COALESCE(w.price,0) <= :price_to');
            $query->setParameter(':price_from', $currency->convert($request->getSession()->get('_currency'), 'PLN', $filterPriceFrom));
            $query->setParameter(':price_to', $currency->convert($request->getSession()->get('_currency'), 'PLN', $filterPriceTo));
        }

        if (isset($filterType) && !empty($filterType)) {
			
			//print $filterType.'<br/>';
			//print $this->get('translator')->trans('Craft', [],'work' , 'pl');die;
			
            $query->andWhere($qb->expr()->like('w.type', ':type') . ' OR ' . $qb->expr()->like('w.type', ':type_l'));
            $query->setParameter(':type', '%' . $filterType . '%');
            $query->setParameter(':type_l', '%' . $this->get('translator')->trans($filterType, [], 'work', $otherLang) . '%');

        }
        if (isset($filterArtist) && !empty($filterArtist)) {
            $query->andWhere($qb->expr()->like('w.artist', ':artist'));
            $query->setParameter(':artist', '%' . $filterArtist . '%');
        }
        if (isset($filterTitle) && !empty($filterTitle)) {
            $query->andWhere($qb->expr()->like('w.title', ':title'));
            $query->setParameter(':title', '%' . $filterTitle . '%');
        }
        if (isset($filterTechnique) && !empty($filterTechnique)) {
            $query->andWhere($qb->expr()->like('w.technique', ':technique') . ' OR ' . $qb->expr()->like('w.technique', ':technique_l'));
            $query->setParameter(':technique', '%' . $filterTechnique . '%');
            $query->setParameter(':technique_l', '%' . $this->get('translator')->trans($filterTechnique, [], 'work', $otherLang) . '%');
        }
        if (isset($filterStyle) && !empty($filterStyle)) {
            $query->andWhere($qb->expr()->like('w.style', ':style') . ' OR ' . $qb->expr()->like('w.style', ':style_l'));
            $query->setParameter(':style', '%' . $filterStyle . '%');
            $query->setParameter(':style_l', '%' . $this->get('translator')->trans($filterStyle, [], 'work', $otherLang) . '%');
        }


        $fromWhere = [];
//        if(isset($filters['from_gallery']) && !empty($filters['from_gallery']))
//        {
//            $fromWhere[] = $qb->expr()->like('a.roles', ':role_g');
//            $query->setParameter(':role_g', '%ROLE_GALERIA%');
//        }
//        if(isset($filters['from_museum']) && !empty($filters['from_museum']))
//        {
//            $fromWhere[] = $qb->expr()->like('a.roles', ':role_m');
//            $query->setParameter(':role_m', '%ROLE_MUZEUM%');
//        }
//        if(isset($filters['from_auction_house']) && !empty($filters['from_auction_house']))
//        {
//            $fromWhere[] = $qb->expr()->like('a.roles', ':role_ac');
//            $query->setParameter(':role_ac', '%ROLE_DOM_AUKCYJNY%');
//        }
//        if(isset($filters['from_artist']) && !empty($filters['from_artist']))
//        {
//            $fromWhere[] = $qb->expr()->like('a.roles', ':role_ar');
//            $query->setParameter(':role_ar', '%ROLE_ARTYSTA%');
//        }

        if ($fromWhere) {
            $query->andWhere(implode(' OR ', $fromWhere));
        }

        if (isset($filters['auctions']) && !empty($filters['auctions'])) {
            $query->join('w.auctionWorks', 'aucw')
                ->join('aucw.auction', 'auc')
                ->andWhere('aucw.approved = :approved')
                ->setParameter(':approved', true);
            if ($filters['auctions'] == 'ongoing') {
                $query->andWhere('auc.endDate > :date')
                    ->andWhere('auc.startDate < :date')
                    ->setParameter(':date', new \DateTime('now'));
            } elseif ($filters['auctions'] == 'next') {
                $query->andWhere('auc.startDate > :date')
                    ->setParameter(':date', new \DateTime('now'));
            } elseif ($filters['auctions'] == 'ended') {
                $query->andWhere('auc.endDate < :date')
                    ->setParameter(':date', new \DateTime('now'));
            }
        }

        /**
         * On Page
         */
        $onPage = $request->get('onPage') ? $request->get('onPage') : 24;
        if ($onPage && !in_array($onPage, array(24, 48, 96)))
            $onPage = 24;

        /**
         * SORT
         */
        if ($sort = $request->get('sort')) {
            list($fieldName, $direction) = explode(',', $sort);
            if ($direction && !in_array($direction, array('DESC', 'ASC')))
                $direction = 'DESC';
            switch ($fieldName) {
                case 'id':
                    $query->orderBy('w.id', $direction);
                    break;
                case 'price':
                    $query->orderBy('w.price', $direction);
                    break;
                case 'add_date':
                    $query->orderBy('w.add_date', $direction);
                    break;
                case 'title':
                    $query->orderBy('w.title', $direction);
                    break;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query->getQuery()->getResult(),
            $request->query->getInt('page', 1),
            $onPage
        );

        $adsQb = $em->getRepository('AppBundle:Advert')->createQueryBuilder('a');
        $ads = $adsQb->select('l.content')
            ->join('a.langs', 'l')
            ->join('l.lang', 'la')
            ->where($adsQb->expr()->in('a.id', array(3)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

        $types = $em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc')
            ->select('wc.value')
            ->where('wc.lang = :lang')
            ->andWhere('wc.type = :type')
            ->setParameter(':type', 'typ')
            ->setParameter(':lang', $em->getRepository('AppBundle:Language')->findOneBy(array(
                'shortcut' => $request->getLocale()
            )))
            ->getQuery()->getScalarResult();


        $typesAr = array();
        foreach ($types as $typ) {
            $typesAr[] = $typ['value'];
        }

        $techniques = $em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc')
            ->select('wc.value')
            ->where('wc.lang = :lang')
            ->andWhere('wc.type = :type')
            ->setParameter(':type', 'technika')
            ->setParameter(':lang', $em->getRepository('AppBundle:Language')->findOneBy(array(
                'shortcut' => $request->getLocale()
            )))
            ->getQuery()->getScalarResult();

        $techniqueAr = array();
        foreach ($techniques as $technique) {
            $techniqueAr[] = $technique['value'];
        }

        $styles = $em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc')
            ->select('wc.value')
            ->where('wc.lang = :lang')
            ->andWhere('wc.type = :type')
            ->setParameter(':type', 'styl')
            ->setParameter(':lang', $em->getRepository('AppBundle:Language')->findOneBy(array(
                'shortcut' => $request->getLocale()
            )))
            ->getQuery()->getScalarResult();

        $stylesAr = array();
        foreach ($styles as $style) {
            $stylesAr[] = $style['value'];
        }

        $priceQuery = $qb1->select('MIN(COALESCE(w.price,0)) as min_price,MAX(COALESCE(w.price,0)) as max_price')
            ->where('w.approved = :approved')
            ->andWhere('w.display = :approved')
            ->setParameter(':approved', 1);

        $prices = $priceQuery->getQuery()->getSingleResult();

        $step = round((round($prices['max_price']) - floor($prices['min_price'])) / 100);

        return array(
            'pagination' => $pagination,
            'pinned' => $pinned,
            'latest' => $latest,
            'ads' => $ads,
            'types' => $typesAr,
            'techniques' => $techniqueAr,
            'styles' => $stylesAr,
            'min_price' => floor($prices['min_price']),
            'max_price' => round($prices['max_price']),
            'step' => $step,
            'filterPriceFrom' => !empty($filterPriceFrom) ? $filterPriceFrom : floor($prices['min_price']),
            'filterPriceTo' => !empty($filterPriceTo) ? $filterPriceTo : round($prices['max_price']),
            'filterType' => isset($filterType) ? $filterType : '',
            'filterArtist' => isset($filterArtist) ? $filterArtist : '',
            'filterTitle' => isset($filterTitle) ? $filterTitle : '',
            'filterTechnique' => isset($filterTechnique) ? $filterTechnique : '',
            'filterStyle' => isset($filterStyle) ? $filterStyle : ''
        );
    }

    /**
     * @Route("s", name="user_works")
     * @Template()
     */
    public function userWorksAction(Request $request)
    {
        if (!$this->getUser())
            throw $this->createNotFoundException();

        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');
        $query = $qb
            ->where('w.author = :author')
            ->setParameter(':author', $this->getUser());

        $filters = $request->get('_filter');
        if (!$filters) $filters = [];
        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'title':
                    $query->andWhere($qb->expr()->like('w.title', ':title'));
                    $query->setParameter(':title', '%' . $value . '%');
                    break;
                case 'artist':
                    $query->andWhere($qb->expr()->like('w.artist', ':artist'));
                    $query->setParameter(':artist', '%' . $value . '%');
                    break;
                case 'approved':
                    $query->andWhere('w.approved = :approved');
                    $query->setParameter(':approved', (bool)$value);
                    break;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * @Route("s/{id}", name="user_work_info", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function userWorkInfoAction($id)
    {
        if (!($user = $this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $work = $em->getRepository('AppBundle:Work')->find($id);
        if (!$work)
            throw $this->createNotFoundException();

        return array(
            'work' => $work
        );
    }

    /**
     * @Route("/add", name="work_add")
     * @Template()
     */
    public function addAction(Request $request)
    {
        if (!($user = $this->getUser()))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();
        $types = $em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc')
            ->select('wc.value')
            ->where('wc.lang = :lang')
            ->andWhere('wc.type = :type')
            ->setParameter(':type', 'typ')
            ->setParameter(':lang', $em->getRepository('AppBundle:Language')->findOneBy(array(
                'shortcut' => $request->getLocale()
            )))
            ->getQuery()->getScalarResult();

        $typesAr = array();
        foreach ($types as $typ) {
            $typesAr[] = $typ['value'];
        }



        $work = new Work();
        $work->setAuthor($this->getUser());
        $form = $this->createForm(WorkType::class, $work, array(
            'types' => $typesAr,
            'roles' => $this->getUser()->getRoles(),
            'locale' => 'pl'
        ));

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return array(
                'form' => $form->createView()
            );
        }

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $work->getImage();
            if ($file instanceof UploadedFile) {
                $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('work_files_directory'));
                $work->setImage($fileName);
            }

            $file = $work->getGallery();
            if ($file instanceof UploadedFile) {
                $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('work_files_directory'));
                $work->setGallery($fileName);
            }

            $dimensionType = $form->get('dimension_type')->getData();
            $dx = str_replace(',', '.', $form->get('dimensions_x')->getData());
            $dy = str_replace(',', '.', $form->get('dimensions_y')->getData());
            $dz = str_replace(',', '.', $form->get('dimensions_z')->getData());
            if ($dimensionType == 'in') {
                $dx = $dx * 2.54;
                $dy = $dy * 2.54;
                $dz = $dz * 2.54;
            }
            $work->setDimensions($dx . 'x' . $dy . 'x' . $dz);

            $work = $this->get('app.work')->parse($work);
            $slugify = new Slugify();
            $slug = '';
            if (!empty($work->getTitle())) {
                $slug = $slugify->slugify($work->getTitle());
            } else {
                $slug = $slugify->slugify(uniqid('work', true));
            }
            /** @var WorkRepository $work_repository */
            $work_repository = $em->getRepository('AppBundle:Work');
            $work->setSlug($work_repository->checkSlug($slug, $work->getId()));

            $work->setCreator($this->getUser()->getId());
            $work->setDescription(($form->get('description')->getData() ? $form->get('description')->getData() : ''));
            $em->persist($work);
            $em->flush();

            try {

                if ($auction = $request->get('auction')) {
                    $auction = $em->getRepository('AppBundle:Auction')->find($auction);
                    if (!$auction) {
                        throw $this->createNotFoundException();
                    }

                    return new RedirectResponse($this->generateUrl('auction_add_work', array(
                        'id' => $auction->getId(),
                        'slug' => $this->get('slugify')->slugify($auction->getLangs()->first()->getTitle()),
                        'work_id' => $work->getId()
                    )));
                }

                $this->addFlash('success', $this->get('translator')->trans('add_work.dodane'));
                return new RedirectResponse($this->generateUrl('user_works'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('add_work.nie_dodane'));
            }
        }

        return array(
            'form' => $form->createView(),
            'errors' => $form->getErrors()
        );
    }

    /**
     * @Route("/edit/{id}", name="work_edit")
     * @Template()
     */
    public function editAction($id, Request $request)
    {
        if (!($user = $this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $work = $em->getRepository('AppBundle:Work')->find($id);
        if (!$work)
            throw $this->createNotFoundException();

        if (!$this->getUser()->hasRole('ROLE_ADMIN') && $work->getAuthor()->getId() != $this->getUser()->getId())
            throw $this->createNotFoundException();

        $form = $this->createForm(WorkType::class, $work, array(
            'edit' => true,
            'types' => explode(',', $this->getParameter('work_types')),
            'roles' => $this->getUser()->getRoles()
        ));
        $dim = $work->getDimensions();
        $dimInc = explode('x', $dim);
        $form->get('dimensions_x')->setData($dimInc[0]);
        $form->get('dimensions_y')->setData($dimInc[1]);
        $form->get('dimensions_z')->setData($dimInc[2]);

        $image = $work->getImage();
        if ($work->getImage() && file_exists($this->getParameter('work_files_directory') . '/' . $work->getImage()))
            $work->setImage(
                new File($this->getParameter('work_files_directory') . '/' . $work->getImage())
            );
        else $work->setImage(null);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $file = $work->getImage();
            if ($file instanceof UploadedFile) {
                $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('work_files_directory'));
                $work->setImage($fileName);
            } else {
                $work->setImage($image);
            }

            $dimensionType = $form->get('dimension_type')->getData();
            $dx = str_replace(',', '.', $form->get('dimensions_x')->getData());
            $dy = str_replace(',', '.', $form->get('dimensions_y')->getData());
            $dz = str_replace(',', '.', $form->get('dimensions_z')->getData());
            if ($dimensionType == 'in') {
                $dx = $dx * 2.54;
                $dy = $dy * 2.54;
                $dz = $dz * 2.54;
            }
            $work->setDimensions($dx . 'x' . $dy . 'x' . $dz);

            try {

                $work->setApproved(false);

                $em->persist($work);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('add_work.zmienione'));
                return new RedirectResponse($this->generateUrl('user_works'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('add_work.nie_zmienione') . $e->getMessage());
            }
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/delete/{id}", name="work_delete")
     * @param $id
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $work = $em->getRepository('AppBundle:Work')->find($id);
        if (!$work || $work->getAuthor()->getId() != $this->getUser()->getId())
            throw $this->createNotFoundException();

        $image = $work->getImage();
        if ($work->getImage() && file_exists($this->getParameter('work_files_directory') . '/' . $work->getImage()))
            $work->setImage(
                new File($this->getParameter('work_files_directory') . '/' . $work->getImage())
            );
        else $work->setImage(null);

        try {
            $em->remove($work);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('works.usunieto'));
        } catch (\Exception $e) {
            $this->addFlash('error', $this->get('translator')->trans('works.nie_usunieto'));
        }

        $lastRoute = $request->getSession()->get('last_route');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        }
        
        return new RedirectResponse(($this->generateUrl('homepage')));

    }

    /**
     * @Route("/{slug}", name="work_see", requirements={
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function seeAction($slug, Request $request)
    {
        $id = ($request->query->has('id')) ? (int)$request->query->get('id') : '';
        $em = $this->getDoctrine()->getManager();
        /** @var QueryBuilder $qb */
        $qb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');
        $qb
            ->leftJoin('w.author', 'a')->addSelect('a')
            ->leftJoin('UserBundle:User', 'au', 'WITH', 'au.fullname = w.artist and au.roles LIKE :role_a')->addSelect('au.id, au.fullname')
            ->where('w.approved = :approved')
            ->where('w.display = :approved')
            ->andWhere('w.slug = :slug')
            ->setParameter(':slug', $slug)
            ->setParameter(':approved', true)
            ->setParameter(':role_a', '%ROLE_USER%');

        if (!empty($id)) {
            $qb->andWhere('w.id = :id')->setParameter(':id', $id);
        }

        $workData = $qb->getQuery()->getOneOrNullResult();

        if (!$workData)
            throw $this->createNotFoundException();

        $work = $workData[0];

        $auction = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->join('aw.auction', 'a')->addSelect('a')
            ->leftJoin('aw.bids', 'b')->addSelect('b')
            ->where('aw.work = :work')
            ->setParameter(':work', $work)
            ->setMaxResults(1)
            ->getQuery()->getResult();

        if ($user = $work->getAuthor()) {
            $form = $this->createFormBuilder(array(
                'content' => ''
            ));

            $form->add('content', TextareaType::class, array(
                'label' => false,
                'attr' => array('style' => 'min-height: 150px'),
                'constraints' => array(new NotBlank())
            ));
            $form->add('fullname', TextType::class, array(
                'label' => 'user.imie_i_nazwisko'
            ))->add('email', EmailType::class, array(
                'label' => 'kontakt.adres_email',
                'constraints' => array(new NotBlank())
            ))->add('phone', TextType::class, array(
                'label' => 'user.numer_telefonu'
            ));
            $form = $form->getForm();
            $form->handleRequest($request);
            if ($form->isValid() && $form->isSubmitted()) {
                try {

                    $message = \Swift_Message::newInstance()
                        ->setSubject($this->get('translator')->trans('mail.zapytanie_o_dzielo'))
                        ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                        ->setTo($this->getParameter('mail_address_from'))
                        ->setBody(
                            $this->renderView('AppBundle:Emails:work_ask.html.twig', array(
                                'email' => $form->get('email')->getData(),
                                'phone' => $form->get('phone')->getData(),
                                'fullname' => $form->get('fullname')->getData(),
                                'work' => $work,
                                'author' => $user,
                                'content' => $form->get('content')->getData(),
                                'for_admin' => true
                            )),
                            'text/html'
                        );
                    $this->get('mailer')->send($message);

                    $this->addFlash('success', $this->get('translator')->trans('works.zapytanie_wyslane'));
                } catch (\Exception $e) {
                    var_dump($e);
                    $this->addFlash('error', $this->get('translator')->trans('mail.nie_udalo_sie'));
                }
            }
        }

        $work->setViews($work->getViews() + 1);
        $em->persist($work);
        $em->flush();

        return array(
            'work' => $workData,
            'auction' => isset($auction[0]) ? $auction[0] : false,
            'form' => isset($form) ? $form->createView() : null,
        );
    }

    /**
     * @Route("/buynow/{workId}", name="work_work_buy_now", requirements={
     *  "workId": "\d+"
     * })
     */
    public function afterAuctionBuyAction($workId, Request $request)
    {
        $redirect = false;

        if (!($user = $this->getUser())) {
            $this->addFlash('error', $this->get('translator')->trans('auctions.poaukcyjna.zaloguj'));
            $lastRoute = $request->getSession()->get('last_route');
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            }

            return new RedirectResponse(($this->generateUrl('homepage')));
        }

        $em = $this->getDoctrine()->getManager();

        $work = $em->getRepository('AppBundle:Work')->find($workId);
        if (!$work)
            throw $this->createNotFoundException();

        if (!$work->getBids()->isEmpty()) {
            $this->addFlash('error', $this->get('translator')->trans('auctions.poaukcyjna.zlozone_oferty'));
            $redirect = true;
        }

        if ($redirect === true) {
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            }

            return new RedirectResponse(($this->generateUrl('homepage')));
        }

        $currency = $em->getRepository('AppBundle:Currency')->findOneBy(array(
            'code' => $request->getSession()->get('_currency')
        ));

        try {
            $bid = new WorkBid();
            $bid->setAuthor($this->getUser());
            $bid->setWork($work);
            $bid->setCurrency($currency);
            $bid->setAmount(
                $this->get('app.currency')->convert(
                    $work->getCurrency()->getCode(),
                    $request->getSession()->get('_currency'),
                    $work->getPrice()
                )
            );
            $em->persist($bid);
            $em->flush();

            $message = \Swift_Message::newInstance()
                ->setSubject($this->get('translator')->trans('works.mail_temat'))
                ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                ->setTo($this->getUser()->getEmail())
                ->setBody($this->renderView('@App/Emails/work_afterbuy_buyer.html.twig', array(
                    'work' => $work,
                    'bid' => $bid
                )), 'text/html');

            $this->get('mailer')->send($message);

            $message = \Swift_Message::newInstance()
                ->setSubject($this->get('translator')->trans('works.mail_temat_seller'))
                ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                ->setTo($this->getParameter('contact_address_from'))
                ->setBody($this->renderView('@App/Emails/work_afterbuy_seller.html.twig', array(
                    'work' => $work,
                    'bid' => $bid
                )), 'text/html');
            $this->get('mailer')->send($message);

            $this->addFlash('success', $this->get('translator')->trans('auctions.dzielo.kupione'));
            return new RedirectResponse($this->generateUrl('user_works_bids'));

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            //$this->addFlash('error', $this->get('translator')->trans('auctions.poaukcyjna.nie_udalo_sie'));
        }

        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

    private function clearFilter($request, $param)
    {
        if ($request->getSession()->has($param)) {
            $request->getSession()->remove($param);
        }
    }

}
