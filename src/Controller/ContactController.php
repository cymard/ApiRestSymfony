<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\UserAdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Form\ContactType;



class ContactController extends AbstractController
{
    private $userAdminRepository;
    private $em;
    private $mailer;

    public function __construct(MailerInterface $mailer, EntityManagerInterface $em, UserAdminRepository $userAdminRepository)
    {
        $this->userAdminRepository = $userAdminRepository;
        $this->em = $em;
        $this->mailer = $mailer;
    }
    
    private function getAllAdminsEmail ()
    {
        // récuperer les emails de tous les admins
        $admins = $this->userAdminRepository->findAll();
        $adminEmails = [];
        foreach ($admins as $admin){
            $email = $admin->getEmail();
            array_push($adminEmails, $email);
        }

        return $adminEmails;
    }


    // private function createEmail (array $emailAdmins, $data)
    // {
    //     $email = (new Email())
    //     ->from('site22web22@gmail.com')
    //     ->to(...$emailAdmins)
    //     ->subject($data->getFirstName().' vous a contacté')
    //     ->html('
    //         <p>Prénom: '.$data->getFirstName().'</p>
    //         <p>Nom: '.$data->getLastName().'</p>
    //         <p>Email: '.$data->getEmail().'</p>
    //         <p>Message: '.$data->getMessage().'</p>
    //     ');

    //     return $email;
    // }


    /**
     * @Route("/contact", name="contact")
     */
    public function index(Request $request)
    {
        $newContact = new Contact();
        $form = $this->createForm(ContactType::class, $newContact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           
            $data = $form->getData();

            $emailAdmins = $this->getAllAdminsEmail();
            $contactForm = new ContactType();
            $email = $contactForm::createEmail($emailAdmins, $data);
            // $email = $this->createEmail($emailAdmins, $data);
            $this->mailer->send($email);

            $this->em->persist($newContact);
            $this->em->flush();

            // redirection home
            return $this->redirect("http://localhost:3000/products?category=all&page=1");
            // return $this->redirectToRoute('product_category', ["category" => "all", "page" => 1] );
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView()
        ]);
    }


}







































// class ContactController extends AbstractController
// {
//     private $userAdminRepository;

//     public function __construct(UserAdminRepository $userAdminRepository)
//     {
//         $this->userAdminRepository = $userAdminRepository;
//     }
    
//     private function getAllAdminsEmail ()
//     {
//         // récuperer les emails de tous les admins
//         $adminsArray = $this->userAdminRepository->findAll();
//         $admins = $adminsArray;
//         $emailAdmins = [];
//         foreach ($admins as $admin){
//             $email = $admin->getEmail();
//             array_push($emailAdmins, $email);
//         }

//         return $emailAdmins;
//     }

//     // private function createAForm ()
//     // {
//     //     $form = $this->createFormBuilder()
//     //         ->add('first_name', TextType::class, ['label' => 'Prénom : '])
//     //         ->add('last_name', TextType::class, ['label' => 'Nom : '])
//     //         ->add('email', EmailType::class, ['label' => 'Email : '])
//     //         ->add('message', TextareaType::class, ['label' => 'Message : '])
//     //         ->add('submit', SubmitType::class, [
//     //             'label' => 'Valider',
//     //             'attr' => [
//     //                 'class' => 'btn btn-primary w-100'
//     //             ]
//     //         ])
//     //         ->getForm();

//     //     return $form;
//     // }

//     private function fillContactEntity ($data)
//     {
//         $contact = New Contact();

//         $contact->setEmail($data["email"]);
//         $contact->setFirstName($data['first_name']);
//         $contact->setLastName($data['last_name']);
//         $contact->setMessage($data['message']);
//     }

//     // private function createEmail (array $emailAdmins, $data)
//     // {
//     //     $email = (new Email())
//     //     ->from('site22web22@gmail.com')
//     //     ->to(...$emailAdmins)
//     //     ->subject($data['first_name'].' vous a contacté')
//     //     ->html('
//     //         <p>Prénom: '.$data['first_name'].'</p>
//     //         <p>Nom: '.$data['last_name'].'</p>
//     //         <p>Email: '.$data['email'].'</p>
//     //         <p>Message: '.$data['message'].'</p>
//     //     ');

//     //     return $email;
//     // }


//     /**
//      * @Route("/contact", name="contact")
//      */
//     public function index(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
//     {
//         $form = $this->createAForm();
//         $form->handleRequest($request);

//         if ($form->isSubmitted() && $form->isValid()) {
           
//             $data = $form->getData();

//             $contact = $this->fillContactEntity($data);
            
//             $emailAdmins = $this->getAllAdminsEmail();
//             $email = $this->createEmail($emailAdmins, $data);
//             $mailer->send($email);


//             $entityManager->persist($contact);
//             $entityManager->flush();

//             // redirection home
//             return $this->redirect("http://localhost:3000/products?category=all&page=1");
//         }

//         return $this->render('contact/index.html.twig', [
//             'form' => $form->createView()
//         ]);
//     }


// }



// class ContactController extends AbstractController
// {
//     /**
//      * @Route("/contact", name="contact")
//      */
//     public function index(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, UserAdminRepository $userAdminRepository): Response
//     {
//         $form = $this->createFormBuilder()
//             ->add('first_name', TextType::class, ['label' => 'Prénom : '])
//             ->add('last_name', TextType::class, ['label' => 'Nom : '])
//             ->add('email', EmailType::class, ['label' => 'Email : '])
//             ->add('message', TextareaType::class, ['label' => 'Message : '])
//             ->add('submit', SubmitType::class, [
//                 'label' => 'Valider',
//                 'attr' => [
//                     'class' => 'btn btn-primary w-100'
//                 ]
//             ])
//             ->getForm();


//         $form->handleRequest($request);

//         if ($form->isSubmitted() && $form->isValid()) {
           
//             $data = $form->getData();

//             $contact = New Contact();

//             $contact->setEmail($data["email"]);
//             $contact->setFirstName($data['first_name']);
//             $contact->setLastName($data['last_name']);
//             $contact->setMessage($data['message']);
            
//             // récuperer les emails de tous les admins
//             $adminsArray = $userAdminRepository->findAll();
//             $admins = $adminsArray;
//             $emailsAdmins = [];
//             foreach ($admins as $admin){
//                 $email = $admin->getEmail();
//                 array_push($emailsAdmins, $email);
//             }

//             // envoie email
//             $email = (new Email())
//                 ->from('site22web22@gmail.com')
//                 ->to(...$emailsAdmins)
//                 ->subject($data['first_name'].' vous a contacté')
//                 ->html('
//                     <p>Prénom: '.$data['first_name'].'</p>
//                     <p>Nom: '.$data['last_name'].'</p>
//                     <p>Email: '.$data['email'].'</p>
//                     <p>Message: '.$data['message'].'</p>
//                 ');

//             $mailer->send($email);


//             $entityManager->persist($contact);
//             $entityManager->flush();

//             // redirection home
//             return $this->redirect("http://localhost:3000/products?category=all&page=1");
//         }

//         return $this->render('contact/index.html.twig', [
//             'form' => $form->createView()
//         ]);
//     }


// }

