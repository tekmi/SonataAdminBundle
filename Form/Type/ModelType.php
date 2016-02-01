<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Form\Type;

use Sonata\AdminBundle\Form\ChoiceList\ModelChoiceList;
use Sonata\AdminBundle\Form\ChoiceList\ModelChoiceLoader;
use Sonata\AdminBundle\Form\DataTransformer\LegacyModelsToArrayTransformer;
use Sonata\AdminBundle\Form\DataTransformer\ModelsToArrayTransformer;
use Sonata\AdminBundle\Form\DataTransformer\ModelToIdTransformer;
use Sonata\AdminBundle\Form\EventListener\MergeCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ModelType
 * This type define a standard select input with a + sign to add new associated object.
 *
 * @author  Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class ModelType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['multiple']) {
            if (array_key_exists('choice_loader', $options) && $options['choice_loader'] !== null) { // SF2.7+
                $builder->addViewTransformer(new ModelsToArrayTransformer(new \Symfony\Component\Form\ChoiceList\LazyChoiceList($options['choice_loader']), $options['model_manager'], $options['class']), true);
            } else {
                $builder->addViewTransformer(new LegacyModelsToArrayTransformer($options['choice_list']), true);
            }

            $builder
                ->addEventSubscriber(new MergeCollectionListener($options['model_manager']))
            ;
        } else {
            $builder
                ->addViewTransformer(new ModelToIdTransformer($options['model_manager'], $options['class']), true)
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['btn_add'] = $options['btn_add'];
        $view->vars['btn_list'] = $options['btn_list'];
        $view->vars['btn_delete'] = $options['btn_delete'];
        $view->vars['btn_catalogue'] = $options['btn_catalogue'];
    }

    /**
     * {@inheritdoc}
     *
     * @todo Remove it when bumping requirements to SF 2.7+
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $options = array();
        if (interface_exists('Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface')) { // SF2.7+
            $options['choice_loader'] = function (Options $options, $previousValue) {
                if ($previousValue && count($choices = $previousValue->getChoices())) {
                    return $choices;
                }

                return new ModelChoiceLoader(
                    $options['model_manager'],
                    $options['class'],
                    $options['property'],
                    $options['query'],
                    $options['choices']
                );

            };
        } else {
            $options['choice_list'] = function (Options $options, $previousValue) {
                if ($previousValue && count($choices = $previousValue->getChoices())) {
                    return $choices;
                }

                return new ModelChoiceList(
                    $options['model_manager'],
                    $options['class'],
                    $options['property'],
                    $options['query'],
                    $options['choices']
                );
            };
        }

        $resolver->setDefaults(array_merge($options, array(
            'compound'          => function (Options $options) {
                if (isset($options['multiple']) && $options['multiple']) {
                    if (isset($options['expanded']) && $options['expanded']) {
                        //checkboxes
                        return true;
                    }

                    //select tag (with multiple attribute)
                    return false;
                }

                if (isset($options['expanded']) && $options['expanded']) {
                    //radio buttons
                    return true;
                }

                //select tag
                return false;
            },

            'template'          => 'choice',
            'multiple'          => false,
            'expanded'          => false,
            'model_manager'     => null,
            'class'             => null,
            'property'          => null,
            'query'             => null,
            'choices'           => array(),
            'preferred_choices' => array(),
            'btn_add'           => 'link_add',
            'btn_list'          => 'link_list',
            'btn_delete'        => 'link_delete',
            'btn_catalogue'     => 'SonataAdminBundle',
        )));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     *
     * @todo Remove when dropping Symfony <2.8 support
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_type_model';
    }
}
