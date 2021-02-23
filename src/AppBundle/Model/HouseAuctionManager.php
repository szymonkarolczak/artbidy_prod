<?php

namespace AppBundle\Model;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventLang;
use AppBundle\Entity\HouseAuction;
use Doctrine\ORM\EntityManager;

class HouseAuctionManager
{
    private $em;
    private $auctionsFolder;
    private $eventsFolder;

    const EVENT_AUCTION_ID = 9; //Id kategorii wystaw

    public function __construct(EntityManager $em, $auctionsFolder, $eventsFolder)
    {
        $this->em = $em;
        $this->auctionsFolder = $auctionsFolder;
        $this->eventsFolder = $eventsFolder;
    }

    public function createAuctionEvent(HouseAuction $auction)
    {
        $auctionLangs = $auction->getLangs();

        if($this->eventExsists($auction))
            return;

        $event = new Event();
        $event->setImage($auction->getImage())
            ->setAddress($auction->getAddress())
            ->setPinned(true)
            ->setCity($auction->getCity())
            ->setCategory($this->em->getRepository('AppBundle:EventCategory')->find(self::EVENT_AUCTION_ID))
            ->setStartDate($auction->getStartDate())
            ->setEndDate(null)
            ->setHouseAuction($auction);

        $this->em->persist($event);

        copy(
            $this->auctionsFolder . DIRECTORY_SEPARATOR . $auction->getImage(),
            $this->eventsFolder . DIRECTORY_SEPARATOR . $auction->getImage()
        );

        foreach($auctionLangs as $lang)
        {
            $eventLang = new EventLang();
            $eventLang->setEvent($event)
                ->setTitle($lang->getTitle())
                ->setDescription($lang->getDescription())
                ->setLang($lang->getLang());

            $this->em->persist($eventLang);
        }

        $this->em->flush();

    }

    public function deleteAuctionEvent(HouseAuction $auction)
    {
        $event = $this->eventExsists($auction);
        if($event)
        {
            $this->em->remove($event);
            $this->em->flush();
        }
    }

    private function eventExsists(HouseAuction $auction)
    {
        return $this->em->getRepository('AppBundle:Event')->findOneBy([
            'houseAuction' => $auction
        ]);
    }

}