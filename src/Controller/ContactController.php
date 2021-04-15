<?php

namespace App\Controller;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="contact")
     */
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        
        
        $form = $this->createFormBuilder()
            ->add('first_name', TextType::class, ['label' => 'Prénom : '])
            ->add('last_name', TextType::class, ['label' => 'Nom : '])
            ->add('email', EmailType::class, ['label' => 'Email : '])
            ->add('message', TextareaType::class, ['label' => 'Message : '])
            ->add('submit', SubmitType::class, ['label' => 'Valider'])
            ->getForm();


        $form->handleRequest($request);
        // dump($form->isSubmitted() );
        // dump($form->isValid() );


        if ($form->isSubmitted() && $form->isValid()) {
           
            // die();
            $data = $form->getData();

            $contact = New Contact();

            $contact->setEmail($data["email"]);
            $contact->setFirstName($data['first_name']);
            $contact->setLastName($data['last_name']);
            $contact->setMessage($data['message']);
            
            // email

            $entityManager->persist($contact);
            $entityManager->flush();
        }

        
        
        return $this->render('contact/index.html.twig', [
            'form' => $form->createView()
        ]);
    }


}
