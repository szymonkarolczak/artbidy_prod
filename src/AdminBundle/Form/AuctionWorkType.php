<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Intl\Intl;

class AuctionWorkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('startPrice', TextType::class, array(
            'label' => 'auctions.obiekt.cena_wywolawcza'
        ))->add('estimationStart', TextType::class, array(
            'label' => false,
            'required' => false
        ))->add('estimationEnd', TextType::class, array(
            'label' => false,
            'required' => false
        ))->add('payment', ChoiceType::class, array(
            'label' => 'auctions.sposob_platnosci',
            'choices' => array(
                'auctions.przelew_bankowy' => 'transfer',
                'auctions.gotowka' => 'cash',
                'auctions.platnosc_online' => 'online'
            )
        ))->add('shipsFrom', CountryType::class, array(
            'label' => 'auctions.kraj_wysylki'
        ))->add('condition', TextareaType::class, array(
            'label' => 'auctions.stan'
        ))->add('approved', CheckboxType::class, array(
            'label' => 'admin.zaakceptowane',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('allowBuyNow', CheckboxType::class, array(
            'label' => 'auctions.obiekt.zakup_po_aukcji',
            'required' => false
        ))->add('submit', SubmitType::class, array(
            'label'    => 'main.zapisz',
            'attr'     => array('class' => 'btn-success')
        ));

        $builder->get('shipsFrom')->addModelTransformer(new CallbackTransformer(
            function ($countryName) {
                $countries = Intl::getRegionBundle()->getCountryNames();
                $countries = array_flip($countries);
                return isset($countries[$countryName]) ? $countries[$countryName] : null;
            },
            function ($code) {
                return Intl::getRegionBundle()->getCountryName($code);
            }
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_auction_work';
    }
}