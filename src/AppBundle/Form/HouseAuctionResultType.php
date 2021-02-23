<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Repository\WorkRepository;

class HouseAuctionResultType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('works', CollectionType::class, array(
            'entry_type' => HouseAuctionWorkType::class,
            'label' => false
        ))->add('submit', SubmitType::class, array(
            'label'    => 'auctions.dodaj_obiekt',
            'attr'     => array('class' => 'btn-success')
        ));
    }

    public function getBlockPrefix()
    {
        return 'app_houseauction_result';
    }
}