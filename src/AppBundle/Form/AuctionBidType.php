<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use AppBundle\Repository\WorkRepository;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class AuctionBidType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('amount', IntegerType::class, array(
            'label'    => 'auctions.obiekt.cena_wywolawcza',
            'constraints' => array(new GreaterThanOrEqual(array(
                'value' => $options['minPrice']
            )))
        ))->add('submit', SubmitType::class, array(
            'label'    => 'auctions.obiekt.licytuj',
            'attr'     => array('class' => 'btn-primary')
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\AuctionBid',
            'minPrice' => 0
        ));
    }

    public function getBlockPrefix()
    {
        return 'app_auction_bid';
    }
}