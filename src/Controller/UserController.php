<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class UserController extends AbstractController
{

    /**
     * @Route("/admin/users", name="users", methods={"GET"})
     * Display all accounts
     */
    public function getUsers(UserRepository $userRepository, SerializerInterface $serializerInterface)
    {
        // 1) connexion repository
        $data = $userRepository->findAll();
        $json = $serializerInterface->serialize($data,"json");

        // 2) récuperer tous les comptes
        // 3) les afficher
        if(!empty($json)){
            //requête qui envoie les données vers app react
            $response = new Response();
            $response->setContent($json);
            $response->headers->set('Content-Type', 'application/json');
            
            return $response;
        }
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     * Create an account
     */
    public function register(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $em ,ValidatorInterface $validator,UserPasswordEncoderInterface $encoder)
    {
        // 1) reçoit les données en POST
        $json = $request->getContent();

        try{

            $data = $serializerInterface->deserialize($json,User::class,"json");

            // Vérification de la validité des données avant de les envoyer en bdd
            $errors = $validator->validate($data);

            if (count($errors) > 0) {
                return $this->json($errors,400);
            }

            // role user
            $data->setRoles(["ROLE_USER"]);

            //  encryptage du password
            $user = new User();
            $password = $data->getPassword(); // password qui doit etre crypté
            $passwordHashed = $encoder->encodePassword($user, $password);
            // $passwordHashed = password_hash($password, PASSWORD_DEFAULT);
            $data->setPassword($passwordHashed);



            // 3) envoie des données en bdd
            $em->persist($data);
            $em->flush();

            return $this->json([
                'email' => $data->getUsername(),
                'role' => $data->getRoles()
            ],201);

        }catch(NotEncodableValueException $e){
            return $this->json([
                "status" => 400,
                "erreur" => $e->getMessage()
            ]);
        }
    }


     /**
     * @Route("verification", name="verification", methods={"POST"})
     * return le role de l'utilisateur
     */
    public function getAdminConnected (UserRepository $repo,Request $request, SerializerInterface $serializerInterface) {
        $json = $request->getContent();
        $data = $serializerInterface->deserialize($json,User::class,"json");
        $account = $repo->findBy(["email" => $data->getEmail()]);

        $accountRole = $account[0]->getRoles()[0];
        if($accountRole === "ROLE_USER"){
            return $this->json([
                "status" => 200,
                "role" => "ROLE_USER"
            ]);
        }else if($accountRole === "ROLE_ADMIN"){
            return $this->json([
                "status" => 200,
                "role" => "ROLE_ADMIN"
            ]);
        }else{
            return $this->json([
                "status" => 200,
                "role" => "IS_NOT_AUTHENTICATED"
            ]);
        }

       
    }

}
