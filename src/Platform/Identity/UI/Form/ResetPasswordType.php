<?php

declare(strict_types=1);

namespace App\Platform\Identity\UI\Form;

use App\Platform\Identity\Application\PasswordPolicy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @extends AbstractType<array{password: string}> */
final class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Las dos contraseñas no coinciden.',
            'first_options' => [
                'label' => 'Contraseña nueva',
                'help' => \sprintf('Al menos %d caracteres. Una frase que recuerdes sirve.', PasswordPolicy::MIN_LENGTH),
                'attr' => ['autocomplete' => 'new-password', 'autofocus' => true],
            ],
            'second_options' => ['label' => 'Repite la contraseña', 'attr' => ['autocomplete' => 'new-password']],
            'constraints' => [new NotBlank(message: 'Escribe tu contraseña nueva.')],
        ]);
    }
}
