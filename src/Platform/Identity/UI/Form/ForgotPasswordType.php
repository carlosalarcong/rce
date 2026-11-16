<?php

declare(strict_types=1);

namespace App\Platform\Identity\UI\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @extends AbstractType<array{email: string}> */
final class ForgotPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Correo',
            'attr' => ['autocomplete' => 'email', 'autofocus' => true],
            'constraints' => [
                new NotBlank(message: 'Escribe tu correo.'),
                new Email(message: 'Ese correo no es válido.'),
                new Length(max: 180),
            ],
        ]);
    }
}
