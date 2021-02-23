<?php

namespace AppBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


use AppBundle\Repository\WorkRepository;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class AuctionWorkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('work', EntityType::class, array(
            'label'    => 'auctions.obiekt.wybierz',
            'class'    => 'AppBundle\Entity\Work',
            'choice_label' => 'title',
            'query_builder' => function (WorkRepository $er) use($options) {
                return $er->createQueryBuilder('w')
                    ->where('w.author = :author')
                    //->andWhere('w.approved = :approved')
                    ->setParameter(':author', $options['user'])
                    //->setParameter(':approved', true)
                    ->orderBy('w.title');
            },
        ));

        if($options['customStartPrice'])
        {
            $builder->add('startPrice', TextType::class, array(
                'label' => 'auctions.obiekt.cena_wywolawcza',
                'constraints' => array(new GreaterThanOrEqual(array(
                    'value' => $options['minStartPrice']
                )))
            ));
        }

        $builder->add('payment', ChoiceType::class, array(
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
        ))->add('allowBuyNow', CheckboxType::class, array(
            'label' => 'auctions.obiekt.zakup_po_aukcji',
            'required' => false
        ))->add('buyNowPrice', IntegerType::class, array(
            'label' => 'auctions.obiekt.cena_poaukcyjna',
            'required' => false
        ))->add('submit', SubmitType::class, array(
            'label'    => 'auctions.dodaj_obiekt',
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\AuctionWork',
            'user' => null,
            'auction' => null,
            'minStartPrice' => 0,
            'customStartPrice' => true
        ));
    }

    public function getBlockPrefix()
    {
        return 'app_auction_work';
    }
}