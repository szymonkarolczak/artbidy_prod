<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Repository\WorkRepository;

class HouseAuctionWorkType extends AbstractType
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
                    ->setParameter(':author', $options['user'])
                    ->orderBy('w.title');
            },
        ))->add('submit', SubmitType::class, array(
            'label'    => 'auctions.dodaj_obiekt',
            'attr'     => array('class' => 'btn-success')
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\HouseAuctionWork',
            'user' => null
        ));
    }

    public function getBlockPrefix()
    {
        return 'app_houseauction_work';
    }
}