<?php

namespace App\Controller;

use App\Entity\CartProduct;
use App\Entity\User;
use App\Entity\ShoppingCart;
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
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

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
     * @Route("/api/connectedAccount", name="user_information", methods={"GET"})
     * Display all informations of a user
     */
    public function getUserInformation(UserRepository $userRepository, SerializerInterface $serializerInterface)
    {
        $user = $this->getUser();
        $userJson = $serializerInterface->serialize($user, "json", ["groups" => "UserInformation"]);

        if(!empty($userJson)){
            //requête qui envoie les données vers app react
            $response = new Response();
            $response->setContent($userJson);
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
     * @Route("/api/user/paymentInformations", name="send_user_payment_informations", methods={"PUT"})
     * send User Payment Informations
     */
    public function sendUserPaymentInformations(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $em ,ValidatorInterface $validator,UserPasswordEncoderInterface $encoder)
    {
        // récuperer les infos 
        $dataJson = $request->getContent();
        $data = json_decode($dataJson);

        // récuperer le user
        $user = $this->getUser();

        // envoie des informations
        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName); 
        $user->setCity($data->city); 
        $user->setAddress($data->address); 
        $user->setPaymentMethod($data->paymentMethod); 
        $user->setCardName($data->cardName); 
        $user->setCardNumber($data->cardNumber); 
        $user->setCardExpirationDate($data->cardExpirationDate); 
        $user->setCryptogram($data->cryptogram); 

        // envoie bdd
        $em->persist($user);
        $em->flush();
        
        return $this->json([
            'informations' => $data
        ],200);
    }


    /**
     * @Route("/api/modify/password", name="user_modify_password", methods={"POST"})
     * Modify actual password
     */
    public function modifyActualPassword(Request $request)
    {
        // recuperer les données
        $jsonData = $request->getContent();
        $dataStdClass = json_decode($jsonData);
        $data = (array) $dataStdClass;

        if($data["newPasswordOne"] !== $data["newPasswordTwo"]){
            // réponse les nouveaux mot de passe ne sont pas identiques
            return $this->json([
                'message' => "Les nouveaux mots de passe entrées ne sont pas identiques."
            ],403);
        }

        $oldPassword = $data["oldPassword"];
        $newPassword = $data["newPasswordOne"];

        // verifier le mdp actuel
            // connexion user
        $user = $this->getUser();
        $cryptedOldPassword = $user->getPassword();
            // password_verify
        $response = password_verify ( $oldPassword , $cryptedOldPassword );

        if($response !== true){
            return $this->json([
                'message' => "Le mot de passe entré n'est pas le bon."
            ],403);
        }


        // changer le mdp actuel par le nouveau mdp
            // crypter le nouveau mdp
        $newPasswordHashed =  password_hash ( $newPassword, PASSWORD_BCRYPT  , ["cost" => 12]);
            
            // remplacement du mdp
        $user->setPassword($newPasswordHashed);
            
            // Mise à jour de la base de données 
        $this->em->persist($user);
        $this->em->flush();

        // réponse
        return $this->json([
            'message' => "Mot de passe modifié avec succès."
        ],200);

    }

    /**
     * @Route("/api/modify/email", name="user_modify_email", methods={"POST"})
     * Modify actual email
     */
    public function modifyActualEmail(Request $request){

        // récuperer les données de la requête
        $dataJson = $request->getContent();
        $dataStdClass = json_decode($dataJson);
        $data = (array) $dataStdClass;

        // vérification du mdp
        $user = $this->getUser();
            // password_verify()
        $password = $data["password"];
        $hashedPassword = $user->getPassword();

        $response = password_verify($password, $hashedPassword);

        if($response !== true){
            return $this->json([
                'message' => "Le mot de passe entré est incorect."
            ],403);
        }

        // changement de l'email
            // setEmail()
        $newEmail = $data["newEmail"];
        $user->setEmail($newEmail);
            
            // enregistrer les données en bdd
        $this->em->persist($user);
        $this->em->flush();

        // réponse
        return $this->json([
            'message' => "Email modifié avec succès."
        ],200);
    }

}
