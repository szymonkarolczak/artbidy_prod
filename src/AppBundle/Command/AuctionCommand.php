<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuctionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:auction')
            ->setDescription('Send info about ended auction');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $translator = $this->getContainer()->get('translator');
        $mailer = $this->getContainer()->get('mailer');
        $senderEmail = $this->getContainer()->getParameter('mail_address_from');
        $twig = $this->getContainer()->get('templating');

        $auctionEnded = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a')
            ->where('a.endDate < :date')
            ->andWhere('a.cronSend = :cron')
            ->setParameter(':cron', false)
            ->setParameter(':date', new \DateTime('now'))
            ->getQuery()->getResult();

        if(!$auctionEnded)
            return;

        foreach($auctionEnded as $auction)
        {
            $works = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
                ->join('aw.work', 'w')->addSelect('w')
                ->leftJoin('aw.bids', 'b')->addSelect('b')
                ->join('w.author', 'a')->addSelect('a')
                ->where('aw.auction = :auction')
                ->setParameter(':auction', $auction)
                ->getQuery()->getResult();
            if(!$works)
                continue;

            foreach($works as $auctionWork)
            {
                $author = $auctionWork->getWork()->getAuthor();
                $authorLocale = $author->getCountry() == 'Polska' || $author->getCountry() == 'Poland' ? 'pl' : 'en';
                if($auctionWork->getBids()->count())
                {
                    //Dzieło sprzedane
                    $bid = $auctionWork->getBids()->last();
                    $bidAuthor = $bid->getAuthor();
                    $bidAuthorLocale = $bidAuthor->getCountry() == 'Polska' || $bidAuthor->getCountry() == 'Poland' ? 'pl' : 'en';

                    try {
                        //Email dla kupującego
                        $message = \Swift_Message::newInstance()
                            ->setSubject($translator->trans('mail.auction.zakonczenie_wygrana_temat', [
                                '%obiekt%' => $auctionWork->getWork()->getTitle(),
                                '%aukcja%' => $this->getAuctionLang($authorLocale, $auction->getLangs())->getTitle()
                            ], 'messages', $authorLocale))
                            ->setFrom(array(
                                $senderEmail => 'Artbidy'
                            ))
                            ->setTo($bidAuthor->getEmail())
                            ->setBody($twig->render('@App/Emails/cronuction_work_winner.html.twig', array(
                                'user' => $bidAuthor,
                                'work' => $auctionWork->getWork(),
                                'bid' => $bid,
                                'locale' => $bidAuthorLocale,
                            )), 'text/html');
                        $mailer->send($message);

                        //Email dla sprzedającego
                        $message = \Swift_Message::newInstance()
                            ->setSubject($translator->trans('mail.auction.zakonczenie_aukcji_temat', [
                                '%aukcja%' => $this->getAuctionLang($authorLocale, $auction->getLangs())->getTitle()
                            ], 'messages', $authorLocale))
                            ->setFrom(array(
                                $senderEmail => 'Artbidy'
                            ))
                            ->setTo($author->getEmail())
                            ->setBody($twig->render('@App/Emails/cronuction_work_sold.html.twig', array(
                                'user' => $author,
                                'work' => $auctionWork->getWork(),
                                'bid' => $bid,
                                'locale' => $authorLocale,
                            )), 'text/html');
                        $mailer->send($message);
                    }
                    catch(\Exception $e)
                    {
                        $output->writeln($e->getMessage());
                    }
                }
                else
                {
                    //Dzieło niesprzedane
                    try {
                        $message = \Swift_Message::newInstance()
                            ->setSubject($translator->trans('mail.auction.zakonczenie_aukcji_temat', [
                                '%aukcja%' => $this->getAuctionLang($authorLocale, $auction->getLangs())->getTitle()
                            ], 'messages', $authorLocale))
                            ->setFrom(array(
                                $senderEmail => 'Artbidy'
                            ))
                            ->setTo($author->getEmail())
                            ->setBody($twig->render('@App/Emails/cronuction_work_not_sold.html.twig', array(
                                'user' => $author,
                                'work' => $auctionWork->getWork(),
                                'locale' => $authorLocale,
                            )), 'text/html');
                        $mailer->send($message);
                    }
                    catch(\Exception $e)
                    {
                        $output->writeln($e->getMessage());
                    }
                }
            }

            $auction->setCronSend(true);
            $em->persist($auction);
            $em->flush();
        }
    }

    private function getAuctionLang($locale, $langs)
    {
        foreach($langs as $lang)
        {
            if($lang->getLang()->getShortcut() == $locale)
                return $lang;
        }
    }
}