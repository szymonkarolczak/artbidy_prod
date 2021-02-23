<?php

namespace UserBundle\Controller;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/user/payments")
 */
class PaymentController extends Controller
{
    /**
     * @Route("/role/{role}", name="user_payments_role", requirements={
     *  "role": "(artist|gallery|auction-house|museum)"
     * })
     * @Template()
     */
    public function buyRoleAction(Request $request, $role)
    {
        $user = $this->getUser();
        if(!$user)
        {
            $user = $request->getSession()->get('_paymentUser');
            if(!$user)
                throw $this->createAccessDeniedException();
            $afterRegister = true;
        }
        else {$afterRegister = false;}

        switch($role) {
            case 'artist':
                $roleName = $this->get('translator')->trans('roles.artysta');
                $price = [
                    '3' => $this->getParameter('artysta_3msc'),
                    '6' => $this->getParameter('artysta_6msc'),
                    '12' => $this->getParameter('artysta_12msc'),
                ];
                break;
            case 'gallery':
                $roleName = $this->get('translator')->trans('roles.galeria');
                $price = [
                    '3' => $this->getParameter('galeria_3msc'),
                    '6' => $this->getParameter('galeria_6msc'),
                    '12' => $this->getParameter('galeria_12msc'),
                ];
                break;
            case 'museum':
                $roleName = $this->get('translator')->trans('roles.muzeum');
                $price = [
                    '3' => $this->getParameter('muzeum_3msc'),
                    '6' => $this->getParameter('muzeum_6msc'),
                    '12' => $this->getParameter('muzeum_12msc'),
                ];
                break;
            case 'auction-house':
                $roleName = $this->get('translator')->trans('roles.dom_aukcyjny');
                $price = [
                    '3' => $this->getParameter('dom_aukcyjny_3msc'),
                    '6' => $this->getParameter('dom_aukcyjny_6msc'),
                    '12' => $this->getParameter('dom_aukcyjny_12msc'),
                ];
                break;
        }

        $package = $request->request->get('package');
        if($package && !empty($package))
        {
            if(!isset($price[$package]))
                throw $this->createNotFoundException();

            $totalPrice = $price[$package];

            $result = $this->doPayPalTransaction(
                $roleName,
                $totalPrice,
                'PLN',
                $this->get('translator')->trans('profile.platnosci.opis', array(
                    '%nazwa%' => $roleName
                ))
            );

            if($result)
                return new RedirectResponse($result);

        }

        return array(
            'user' => $user,
            'role' => $role,
            'roleName' => $roleName,
            'price' => $price,
            'afterRegister' => $afterRegister
        );
    }

    /**
     * @Route("/service/{service}", name="user_payments_service", requirements={
     *  "role": "(database|annoucements)"
     * })
     * @Template()
     */
    public function buyServiceAction($service)
    {
        switch($service)
        {
            case 'database':
                $serviceName = $this->get('translator')->trans('home.baza_cen');
                $price = [
                    '3' => $this->getParameter('database_3msc'),
                    '6' => $this->getParameter('database_6msc'),
                    '12' => $this->getParameter('database_12msc'),
                ];
                break;
            case 'annoucements':
                $serviceName = $this->get('translator')->trans('home.powiadomienia');
                $price = [
                    '3' => $this->getParameter('annoucements_3msc'),
                    '6' => $this->getParameter('annoucements_6msc'),
                    '12' => $this->getParameter('annoucements_12msc'),
                ];
                break;
        }

        return array(
            'serviceName' => $serviceName,
            'service' => $service,
            'price' => $price
        );
    }


    /**
     * @Route("/cancel", name="user_payments_cancel")
     * @Template()
     */
    public function cancelAction(Request $request)
    {
        $token = $request->get('token');

        return array();
    }

    /**
     * @Route("/parse", name="user_payments_return")
     * @Template()
     */
    public function returnAction(Request $request)
    {
        $logger = $this->get('logger');
        
        $post = $request->request->all();
        
        $logger->error(var_export($post, true));

        return array();
    }

    private function doPayPalTransaction($itemName, $totalPrice, $currency, $description)
    {
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        $item = new Item();
        $item->setName($itemName)
            ->setCurrency($currency)
            ->setQuantity(1)
            ->setPrice($totalPrice);

        $itemList = new ItemList();
        $itemList->setItems(array($item));

        $details = new Details();
        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($totalPrice);

        $amount = new Amount();
        $amount->setCurrency($currency)
            ->setTotal($totalPrice)
            ->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription($description)
            ->setCustom($this->getUser()->getId())
            ->setInvoiceNumber(uniqid());

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setCancelUrl($this->generateUrl('user_payments_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL))
            ->setReturnUrl($this->generateUrl('user_payments_return', [], UrlGeneratorInterface::ABSOLUTE_URL));

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));

//        $tokenCredential = new \PayPal\Auth\OAuthTokenCredential(
//            'AdN0xOZJiRmmqGWfdxM4OvDRukafBul4GDkoPCcWfsBhNi6nZELQWQd7JyrNLG9_A_wE6egr0v0N6RML',     // ClientID
//            'EFYf_5Kwmp_iP2FsmATcgakojxd8Nrx37sfpNjOGGF3p-1qh0PSjacX5rmjCwgJCd8z0la-CoYjAntT3'      // ClientSecret
//        );
        $tokenCredential = new \PayPal\Auth\OAuthTokenCredential(
            'AZ8T7-OzADrs_CZXfKvbVS4JszDLFrUy2jI6tMr7Uvi9kiO4do1c8LFA9QxGcaBJ4pDzEAvRnerXugt3',
            'EFlL8P-eiK6rGft6n8Zg4I-KFjc3NsLy1AuRFv5L8YvQJ76HKqblTrn1G9r8E5VePlL4LfUONiTcLFpf'
        );

        try {
            $apiContext = new \PayPal\Rest\ApiContext($tokenCredential);
            $apiContext->setConfig(array(
                'mode' => 'live'
            ));
            $payment->create($apiContext);
            return $payment->getApprovalLink();
        } catch(\Exception $e)
        {
            $logger = $this->get('logger');
            $logger->error($e->getMessage());
            $this->addFlash('error', $this->get('translator')->trans('profile.platnosci.nie_udalo_sie'));
            return false;
        }
    }

}
