<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Mime\Email;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $form = $builder
            ->add('first_name', TextType::class, ['label' => 'Prénom : '])
            ->add('last_name', TextType::class, ['label' => 'Nom : '])
            ->add('email', EmailType::class, ['label' => 'Email : '])
            ->add('message', TextareaType::class, ['label' => 'Message : '])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => [
                    'class' => 'btn btn-primary w-100'
                ]
            ])
            ->getForm();

        return $form;
    }

    static function createEmail (array $emailAdmins, $data)
    {
        $email = (new Email())
        ->from('site22web22@gmail.com')
        ->to(...$emailAdmins)
        ->subject($data->getFirstName().' vous a contacté')
        ->html('
            <p>Prénom: '.$data->getFirstName().'</p>
            <p>Nom: '.$data->getLastName().'</p>
            <p>Email: '.$data->getEmail().'</p>
            <p>Message: '.$data->getMessage().'</p>
        ');

        return $email;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
