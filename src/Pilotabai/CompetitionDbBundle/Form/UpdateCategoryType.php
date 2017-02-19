<?php

namespace Pilotabai\CompetitionDbBundle\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateCategoryType extends CategoryType
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