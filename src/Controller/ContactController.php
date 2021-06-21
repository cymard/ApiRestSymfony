<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\UserAdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



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


    /**
     * @Route("/contact", name="contact")
     */
    public function index(Request $request)
    {
        $newContact = new Contact();
        $form = $this->createForm(ContactType::class, $newContact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->isEmpty() === false) {

            $emailAdmins = $this->getAllAdminsEmail();
            $email = $newContact->sendEmailToAdmins($emailAdmins);

            $this->mailer->send($email);

            $this->em->persist($newContact);
            $this->em->flush();

            return $this->redirect("https://relaxed-sammet-0deed4.netlify.app/products?category=all&page=1");
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView()
        ]);
    }


}

