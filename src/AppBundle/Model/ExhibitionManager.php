<?php

namespace AppBundle\Model;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventLang;
use AppBundle\Entity\Exhibition;
use Doctrine\ORM\EntityManager;

class ExhibitionManager
{
    private $em;
    private $exhibitionsFolder;
    private $eventsFolder;

    const EVENT_CATEGORY_ID = 6; //Id kategorii wystaw

    public function __construct(EntityManager $em, $exhibitionsFolder, $eventsFolder)
    {
        $this->em = $em;
        $this->exhibitionsFolder = $exhibitionsFolder;
        $this->eventsFolder = $eventsFolder;
    }

    public function createExhibitionEvent(Exhibition $exhibition)
    {
        $exhibitionLangs = $exhibition->getLangs();

        if($this->eventExsists($exhibition))
            return;

        $event = new Event();
        $event->setImage($exhibition->getImage())
            ->setAddress($exhibition->getAddress())
            ->setPinned(true)
            ->setCity($exhibition->getCity())
            ->setCategory($this->em->getRepository('AppBundle:EventCategory')->find(self::EVENT_CATEGORY_ID))
            ->setStartDate($exhibition->getStartDate())
            ->setEndDate($exhibition->getEndDate())
            ->setExhibition($exhibition);

        $this->em->persist($event);

        copy(
            $this->exhibitionsFolder . DIRECTORY_SEPARATOR . $exhibition->getImage(),
            $this->eventsFolder . DIRECTORY_SEPARATOR . $exhibition->getImage()
        );

        foreach($exhibitionLangs as $lang)
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

    public function deleteExhibitionEvent(Exhibition $exhibition)
    {
        $event = $this->eventExsists($exhibition);
        if($event)
        {
            $this->em->remove($event);
            $this->em->flush();
        }
    }

    private function eventExsists(Exhibition $exhibition)
    {
        return $this->em->getRepository('AppBundle:Event')->findOneBy([
            'exhibition' => $exhibition
        ]);
    }

}