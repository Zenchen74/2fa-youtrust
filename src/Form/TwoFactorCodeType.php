<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class TwoFactorCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code de vérification',
                'attr' => [
                    'placeholder' => '123456',
                    'maxlength' => 6,
                    'autocomplete' => 'one-time-code',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le code est requis']),
                    new Type(['type' => 'numeric', 'message' => 'Le code doit contenir uniquement des chiffres']),
                    new Length(['min' => 6, 'max' => 6, 'exactMessage' => 'Le code doit contenir exactement 6 chiffres']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'two_factor_code_item',
        ]);
    }
}
