<?php

namespace Pilotabai\CompetitionDbBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('website', TextType::class, [
                'required' => true,
                'disabled' => $options['is_edit'] // readonly edit mode
            ])
            ->add('competitionValue', IntegerType::class, [
                'required' => true,
                'disabled' => $options['is_edit'] // readonly edit mode
            ])
            ->add('specialityValue', IntegerType::class, [
                'required' => true,
                'disabled' => $options['is_edit'] // readonly edit mode
            ])
            ->add('levelValue', IntegerType::class, [
                'required' => true,
                'disabled' => $options['is_edit'] // readonly edit mode
            ])
            ->add('competition')
            ->add('speciality')
            ->add('level')
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pilotabai\CompetitionDbBundle\Entity\Category',
            'is_edit' => false,
            'csrf_protection' => false, // Use to protect online form but not api requests. Symfony forms always expect a token. But because we're building a stateless, or session-less API, we don't need CSRF tokens. You would need it if you have a JavaScript frontend that's relying on cookies to authenticate, but you don't need it if your API doesn't store the user in the session.

        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'pilotabai_competitiondbbundle_category';
    }
}
