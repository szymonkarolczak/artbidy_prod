<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronNewsletterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:newsletter')
            ->setDescription('Send newsletter to the next part of signed users');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $translator = $this->getContainer()->get('translator');
        $mailer = $this->getContainer()->get('mailer');
        $senderEmail = $this->getContainer()->getParameter('mail_address_from');
        $limit = $this->getContainer()->getParameter('newsletter_limit');

        $newsletterCron = $em->getRepository('AppBundle:Cron')->find(1);
        if (!$newsletterCron->getActive())
            return;

        $options = $newsletterCron->getParams();
        $sended = (int)$options['start'];

        $emails = $em->getRepository('AppBundle:Newsletter')->createQueryBuilder('n')
            ->setFirstResult($options['start'])
            ->setMaxResults($limit)
            ->getQuery()->getResult();

        if ($emails) {
            $msgpl = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
                ->where('s.name = :name')
                ->setParameter(':name', 'newsletterHtml-pl')
                ->getQuery()->getSingleResult();

            $title = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
                ->where('s.name = :name')
                ->setParameter(':name', 'newsletterTitle-pl')
                ->getQuery()->getSingleResult();


            $msgen = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
                ->where('s.name = :name')
                ->setParameter(':name', 'newsletterHtml-en')
                ->getQuery()->getSingleResult();

            $titleEn = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
                ->where('s.name = :name')
                ->setParameter(':name', 'newsletterTitle-en')
                ->getQuery()->getSingleResult();
            foreach ($emails as $email) {
                $msg = $email->getLocale() == 'pl' ?
                    $msgpl->getValue() :
                    $msgen->getValue();

                if ($email->getLocale() == 'pl') {
                    $subject = !empty($title->getValue()) ? $title->getValue() : $translator->trans('mail.newsletter');
                } else {
                    $subject = !empty($titleEn->getValue()) ? $titleEn->getValue() : $translator->trans('mail.newsletter');
                }

                try {
                    $message = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom(array(
                            $senderEmail => 'Artbidy'
                        ))
                        ->setTo($email->getEmail())
                        //->setTo($senderEmail)
                        ->setBody($msg, 'text/html');
                    $mailer->send($message);
                } catch (\Exception $e) {
                    $output->writeln($e->getMessage());
                }
                $sended++;
            }
        }

        if ($sended == $options['max']) {
            $msgen->setValue(null);
            $msgpl->setValue(null);
            $em->persist($msgen);
            $em->persist($msgpl);

            $newsletterCron->setActive(false);
            $newsletterCron->setParams(null);
            $em->persist($newsletterCron);
            $em->flush();
            return;
        }

        $newStart = $options['start'] + $limit;
        $newsletterCron->setParams(array(
            'start' => $newStart,
            'max' => $options['max']
        ));
        $em->persist($newsletterCron);
        $em->flush();

    }
}