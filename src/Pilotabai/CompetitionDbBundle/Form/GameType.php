<?php

namespace Pilotabai\CompetitionDbBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rencontre', IntegerType::class, [
                'required' => true,
                'disabled' => $options['is_edit'] // readonly edit mode
            ])
            ->add('phase')
            ->add('category', EntityType::class, [
                'class' => 'Pilotabai\CompetitionDbBundle\Entity\Category',
                'required' => true,
                'disabled' => $options['is_edit'] // readonly edit mode
            ])
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pilotabai\CompetitionDbBundle\Entity\Game',
            'is_edit' => false,
            'csrf_protection' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'pilotabai_competitiondbbundle_game';
    }


}
