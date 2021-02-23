<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AccountRemindCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('account:remind')
            ->setDescription('Send email with info about account expiration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $translator = $this->getContainer()->get('translator');
        $mailer = $this->getContainer()->get('mailer');
        $senderEmail = $this->getContainer()->getParameter('mail_address_from');
        $twig = $this->getContainer()->get('templating');

        $dateMonth = new \DateTime('+1 month');
        $dateWeek = new \DateTime('+7 days');
        $dateDay = new \DateTime('+1 day');
        $users = $em->getRepository('UserBundle:User')->createQueryBuilder('u')
            ->select('u.email, u.country, u.fullname, u.roleEnd')
            ->where('u.roleEnd >= :date_start_month AND u.roleEnd <= :date_end_month')
            ->orWhere('u.roleEnd >= :date_start_week AND u.roleEnd <= :date_end_week')
            ->orWhere('u.roleEnd >= :date_start_day AND u.roleEnd <= :date_end_day')
            ->setParameter('date_start_month', $dateMonth->format('Y-m-d 00:00:00'))
            ->setParameter('date_end_month', $dateMonth->format('Y-m-d 23:59:59'))
            ->setParameter('date_start_week', $dateWeek->format('Y-m-d 00:00:00'))
            ->setParameter('date_end_week', $dateWeek->format('Y-m-d 23:59:59'))
            ->setParameter('date_start_day', $dateDay->format('Y-m-d 00:00:00'))
            ->setParameter('date_end_day', $dateDay->format('Y-m-d 23:59:59'))
            ->getQuery()
            ->getResult();
        foreach($users as $user)
        {
            $output->writeln($user['email']);
            $lang = $user['country'] == 'Polska' || $user['country'] == 'Poland' ? 'pl' : 'en';
            try {
                $message = \Swift_Message::newInstance()
                    ->setSubject($translator->trans('mail.konczace_sie_konto', [], 'messages', $lang))
                    ->setFrom(array(
                        $senderEmail => 'Artbidy'
                    ))
                    ->setTo($user['email'])
                    ->setBody($twig->render('AppBundle:Emails:account_remind.html.twig', array(
                        'fullname' => $user['fullname'],
                        'locale' => $lang,
                        'data' => $user['roleEnd']
                    )), 'text/html');
                $mailer->send($message);
            }
            catch(\Exception $e)
            {
                $output->writeln($e->getMessage());
            }
        }
    }
}