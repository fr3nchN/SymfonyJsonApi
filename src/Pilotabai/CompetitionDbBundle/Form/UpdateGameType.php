<?php

namespace Pilotabai\CompetitionDbBundle\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateGameType extends GameType
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'is_edit' => true,
        ));
    }
}