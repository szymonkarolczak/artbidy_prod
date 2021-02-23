<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use AdminBundle\Form\DataTransformer\UserToUsernameTransformer;
use Doctrine\Common\Persistence\ObjectManager;

class ExhibitionType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('langs', CollectionType::class, array(
            'entry_type' => ExhibitionLangType::class,
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
            'label'    => 'add_work.grafika',
            'required' => false
        ))->add('users', CollectionType::class, array(
            'entry_type'   => TextType::class,
            'entry_options' => array(
                'attr'      => array('class' => 'user-box')
            ),
            'allow_add' => true,
            'allow_delete' => true,
            'label'    => false,
            'required' => false,
        ))->add('approved', CheckboxType::class, array(
            'label' => 'dziela.zaakceptowane',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('pinned', CheckboxType::class, array(
            'label' => 'aktualnosci.wyroznione',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('enabled', CheckboxType::class, array(
            'label' => 'admin.aktywnosc',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('finearts', CheckboxType::class, array(
            'label' => 'admin.finearts',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('submit', SubmitType::class, array(
            'label'    => 'main.zapisz',
            'attr'     => array('class' => 'btn-success')
        ));
        
        $builder->get('users')
            ->addModelTransformer(new UserToUsernameTransformer($this->manager));
    }

    public function getBlockPrefix()
    {
        return 'app_admin_exhibition';
    }
}