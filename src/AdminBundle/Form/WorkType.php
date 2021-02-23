<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use AppBundle\Repository\CurrencyRepository;

class WorkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('creator', ChoiceType::class, array(
            'label'    => 'add_work.tworca',
            'attr' => array('class' => 'selectpicker'),
            'choices' => array(
                'add_work.artysta' => 'artysta',
                'add_work.producent' => 'producent'
            )
        ))->add('slug', TextType::class, array(
            'label'     =>  'slug',
            'translation_domain' => 'admin',
            'required' => true,
        ))->add('artist', TextType::class, array(
            'label'    => 'add_work.artysta',
        ))->add('title', TextType::class, array(
            'label'    => 'add_work.tytul',
        ))->add('metatitle', TextType::class, array(
            'label'    => 'add_work.metatitle',
        ))->add('metatitleEn', TextType::class, array(
            'label'    => 'add_work.metatitle_en',
        ))->add('technique', TextType::class, array(
            'label'    => 'add_work.technika',
            'required' => false,
        ))->add('type', ChoiceType::class, array(
            'label'    => 'add_work.typ',
            'choices' => array_combine($options['types'], $options['types'])
        ))->add('style', TextType::class, array(
            'label'    => 'add_work.styl',
            'required' => false,
        ))->add('priceOnRequest', CheckboxType::class, array(
            'label'    => 'add_work.price_on_request',
            'required' => false,
        ))->add('price', TextType::class, array(
            'required' => false,
        ))->add('currency', EntityType::class, array(
            'label'    => 'main.waluta',
            'class'  => 'AppBundle:Currency',
            'query_builder' => function (CurrencyRepository $er) {
                return $er->createQueryBuilder('c')
                    ->where('c.enabled = :enabled')
                    ->setParameter(':enabled', true);
            },
            'choice_label' => 'code'
        ))->add('description', TextareaType::class, array(
            'label'    => 'add_work.opis',
        ))->add('image', FileType::class, array(
            'label'    => 'add_work.grafika',
            'attr' => array(
                'accept' => 'image/*'
            ),
            'required' => false
        ))->add('pinned', CheckboxType::class, array(
            'required' => false,
            'label' => 'aktualnosci.wyroznione',
            'translation_domain' => 'admin'
        ))->add('submit', SubmitType::class, array(
            'label'    => 'main.zapisz',
            'attr'     => array('class' => 'btn-success')
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'types'      => array()
        ));
    }

    public function getBlockPrefix()
    {
        return 'app_admin_work';
    }
}