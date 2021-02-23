<?php

namespace AdminBundle\Form;

use AdminBundle\Form\DataTransformer\UserToUsernameTransformer;
use AppBundle\Repository\EventCategoryLangRepository;
use AppBundle\Repository\EventCategoryRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use AdminBundle\Form\EventLangType;

class EventType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('langs', CollectionType::class, array(
            'entry_type' => EventLangType::class,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'translation_domain' => 'admin'
        ))->add('address', TextType::class, array(
            'label'    => 'user.adres',
        ))->add('city', TextType::class, array(
            'label'    => 'user.miasto',
        ))->add('startDate', DateTimeType::class, array(
            'label'    => 'main.data_start'
        ))->add('endDate', DateTimeType::class, array(
            'label'    => 'main.data_koniec'
        ))->add('image', FileType::class, array(
            'label'    => false,
            'required' => false
        ))->add('pinned', CheckboxType::class, array(
            'label'    => 'aktualnosci.wyroznione',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('submit', SubmitType::class, array(
            'label'    => 'main.zapisz',
            'attr'     => array('class' => 'btn-success')
        ))->add('slug', TextType::class, array(
            'label'     =>  'slug',
            'translation_domain' => 'admin',
            'required' => true,
        ))->add('category', EntityType::class, array(
            'label'    => false,
            'class' => 'AppBundle\Entity\EventCategory',
            'choice_label' => 'langs.first.title',
            'query_builder' => function (EventCategoryRepository $er) {
                return $er->createQueryBuilder('nc')
                    ->join('nc.langs', 'l')->addSelect('l')
                    ->join('l.lang', 'lg')
                    ->where('lg.shortcut = :lang')
                    ->setParameter(':lang', 'pl')
                    ->groupBy('nc.id');
            },
        ))->add('users', CollectionType::class, array(
            'entry_type'   => TextType::class,
            'entry_options' => array(
                'attr'      => array('class' => 'user-box')
            ),
            'allow_add' => true,
            'allow_delete' => true,
            'label'    => false,
            'required' => false,
        ));

        $builder->get('users')
            ->addModelTransformer(new UserToUsernameTransformer($this->manager));
    }

    public function getBlockPrefix()
    {
        return 'admin_event';
    }
}