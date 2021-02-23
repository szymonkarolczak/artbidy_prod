<?php

namespace AdminBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkConfigType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('lang', EntityType::class, array(
            'label' => 'Język',
            'class' => 'AppBundle:Language',
            'choice_label' => 'shortcut'
        ))->add('value', TextType::class, array(
            'label'    => 'Wartość'
        ))->add('submit', SubmitType::class, array(
            'label' => 'Dodaj',
            'attr' => array('class' => 'btn-success')
        ));

        if(!$options['langs'])
        {
            $builder->add('type', ChoiceType::class, array(
                'label'    => 'Typ',
                'choices' => array(
                    'Styl' => 'styl',
                    'Technika' => 'technika',
                    'Typ obiektu' => 'typ',
                )
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\WorkConfig',
            'langs' => false
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_work_config';
    }
}