<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\UserAdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="contact")
     */
    public function index(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, UserAdminRepository $userAdminRepository): Response
    {
        $form = $this->createFormBuilder()
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


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           
            $data = $form->getData();

            $contact = New Contact();

            $contact->setEmail($data["email"]);
            $contact->setFirstName($data['first_name']);
            $contact->setLastName($data['last_name']);
            $contact->setMessage($data['message']);
            
            // récuperer les emails de tous les admins
            $adminsArray = $userAdminRepository->findAll();
            $admins = $adminsArray;
            $emailsAdmins = [];
            foreach ($admins as $admin){
                $email = $admin->getEmail();
                array_push($emailsAdmins, $email);
            }

            // envoie email
            $email = (new Email())
                ->from('site22web22@gmail.com')
                ->to(...$emailsAdmins)
                ->subject($data['first_name'].' vous a contacté')
                ->html('
                    <p>Prénom: '.$data['first_name'].'</p>
                    <p>Nom: '.$data['last_name'].'</p>
                    <p>Email: '.$data['email'].'</p>
                    <p>Message: '.$data['message'].'</p>
                ');

            $mailer->send($email);


            $entityManager->persist($contact);
            $entityManager->flush();

            // redirection home
            return $this->redirect("http://localhost:3000/products?category=all&page=1");
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView()
        ]);
    }


}
