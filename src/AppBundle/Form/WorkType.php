<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use AppBundle\Repository\CurrencyRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class WorkType extends AbstractType
{
    /**
     * @var array
     */
    private $options;

    /**
     * @param FormBuilderInterface $builder
     * @param TranslatorInterface $translator
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;
        
        $builder->add('technique_type', ChoiceType::class, array(
            'label' => 'add_work.technika',
            'attr' => array('class' => 'selectpicker'),
            'choices' => array(
                'add_work.technika' => 'technika',
                'add_work.material' => 'material'
            )
        ))->add('creator', ChoiceType::class, array(
            'label' => 'add_work.tworca',
            'attr' => array('class' => 'selectpicker', 'data-none-selected-text'=> $this->getTranslator()->trans('nothing_selected')),
            'choices' => array(
                'add_work.artysta' => 'artysta',
                'add_work.producent' => 'producent'
            ),
            'required' => true,
        ))->add('artist', TextType::class, array(
            'label' => 'add_work.artysta',
            'required' => true,
        ))->add('title', TextType::class, array(
            'label' => 'add_work.tytul',
            'required' => true,
        ))->add('technique', TextType::class, array(
            'label' => 'add_work.technika',
            'required' => false,
        ))->add('dimensions_x', TextType::class, array(
            'mapped' => false,
            'label' => false,
            'required' => false,
        ))->add('dimensions_y', TextType::class, array(
            'mapped' => false,
            'label' => false,
            'required' => false,
        ))->add('dimensions_z', TextType::class, array(
            'mapped' => false,
            'label' => false,
            'required' => false,
        ))->add('dimension_type', ChoiceType::class, array(
            'mapped' => false,
            'label' => false,
            'choices' => array(
                'cm' => 'cm',
                'in' => 'in'
            ),
            'required' => false,
        ))->add('year', IntegerType::class, array(
            'label' => 'add_work.rok',
            'required' => true
        ))->add('type', ChoiceType::class, array(
            'label' => 'add_work.typ',
            'choices' => array_combine($options['types'], $options['types']),
            'required' => false,
        ))->add('style', TextType::class, array(
            'label' => 'add_work.styl',
            'required' => false,
        ))->add('description', TextareaType::class, array(
            'label' => 'add_work.opis',
            'required' => false,
        ))->add('submit', SubmitType::class, array(
            'label' => 'main.zapisz',
            'attr' => array('class' => 'btn-success')
        ));


        $builder->add('priceOnRequest', CheckboxType::class, array(
            'label' => 'add_work.price_on_request',
            'required' => false,
        ))->add('price', TextType::class, array(
            'required' => false,
        ))->add('currency', EntityType::class, array(
            'label' => 'main.waluta',
            'class' => 'AppBundle:Currency',
            'query_builder' => function (CurrencyRepository $er) {
                return $er->createQueryBuilder('c')
                    ->where('c.enabled = :enabled')
                    ->setParameter(':enabled', true);
            },
            'required' => false,
            'choice_label' => 'code'
        ));

        if (!$options['edit']) {
        $builder->add('image', FileType::class, array(
            'label' => 'add_work.grafika',
            'attr' => array(
                'accept' => 'image/*'
            ),
            'data_class' => null,
            'required' => true,
            //'constraints' => array(new NotBlank())
        ))->add('gallery', CollectionType::class, array(
            'entry_type' => FileType::class,
            'entry_options' => array(
                'attr' => array('accept' => 'image/*')
            ),
            'allow_add' => true,
            'allow_delete' => true,
            'label' => false,
            'required' => false
        ));
    }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'edit' => false,
            'types' => array(),
            'roles' => array(),
            'locale' => 'pl'
        ));
    }

    public function getBlockPrefix()
    {
        return 'app_work';
    }

    private function getTranslator() {

        if(!isset($this->translator)) {
            $this->translator = new Translator($this->options['locale']);
            $this->translator->addLoader('yaml', new YamlFileLoader());
            $this->translator->addResource('yaml',dirname(__DIR__).'/../../app/Resources/translations/messages.pl.yml','pl');
            $this->translator->addResource('yaml',dirname(__DIR__).'/../../app/Resources/translations/messages.en.yml','en');
            $this->translator->setFallbackLocales(['en']);
        }

        return $this->translator;
    }
}