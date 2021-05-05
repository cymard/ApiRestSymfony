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
    private $em;
    private $validator;
    private $userPasswordEncoderInterface;
    private $serializerInterface;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, UserPasswordEncoderInterface $userPasswordEncoderInterface, SerializerInterface $serializerInterface)
    {
        $this->em = $em;
        $this->validator = $validator;
        $this->userPasswordEncoderInterface = $userPasswordEncoderInterface;
        $this->serializerInterface = $serializerInterface;
    }

    private function sendNewResponseJson($dataJsonFormat)
    {
        $response = new Response();
        $response->setContent($dataJsonFormat);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function validateDataWithConstraints ($data)
    {
        $errors = $this->validator->validate($data);
        if (count($errors) > 0) {
            return $this->json($errors,400);
        }
    }

    private function encryptDataPassword ($data)
    {
        $user = new User();
        $password = $data->getPassword(); // password qui doit être crypté
        $passwordHashed = $this->userPasswordEncoderInterface->encodePassword($user, $password);
        $data->setPassword($passwordHashed);

        return $data;
    }

    private function transformDataJsonIntoDataArray(Request $request)
    {
        $dataJson = $request->getContent();
        $dataStdClass = json_decode($dataJson);
        $data = (array) $dataStdClass;

        return $data;
    }

    private function saveInDatabase($dataObject)
    {
        $this->em->persist($dataObject);
        $this->em->flush();
    }



    /**
     * @Route("/api/connectedAccount", name="user_information", methods={"GET"})
     * Display all informations of a user
     */
    public function getUserInformations()
    {
        $userObject = $this->getUser();
        $userJson= $this->serializerInterface->serialize($userObject, "json", ["groups" => "UserInformation"]);

        if(!empty($userJson)){
            return $this->sendNewResponseJson($userJson);
        }
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     * Create an account
     */
    public function register(Request $request)
    {
        $dataJson = $request->getContent();

        try{

            $dataObject = $this->serializerInterface->deserialize($dataJson,User::class,"json");
            $this->validateDataWithConstraints($dataObject);

            $dataObject->setRoles(["ROLE_USER"]);
            $this->encryptDataPassword($dataObject);

            $this->saveInDatabase($dataObject);

            return $this->json([
                'email' => $dataObject->getUsername(),
                'role' => $dataObject->getRoles()
            ],201);

        }catch(NotEncodableValueException $e){
            return $this->json([
                "erreur" => $e->getMessage()
            ],400);
        }
    }


    /**
     * @Route("/api/user/paymentInformations", name="send_user_payment_informations", methods={"PUT"})
     * send User Payment Informations
     */
    public function sendUserPaymentInformations(Request $request)
    {
        $data = json_decode($request->getContent());
        $user = $this->getUser();

        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName); 
        $user->setCity($data->city); 
        $user->setAddress($data->address); 
        $user->setPaymentMethod($data->paymentMethod); 
        $user->setCardName($data->cardName); 
        $user->setCardNumber($data->cardNumber); 
        $user->setCardExpirationDate($data->cardExpirationDate); 
        $user->setCryptogram($data->cryptogram); 

        $this->em->flush($user);
        
        return $this->json([
            'informations' => $data
        ],200);
    }


    /**
     * @Route("/api/modify/password", name="user_modify_password", methods={"PUT"})
     * Modify actual password
     */
    public function modifyActualPassword(Request $request)
    {
        $data = $this->transformDataJsonIntoDataArray($request);

        $oldPassword = $data["oldPassword"];
        $newPassword = $data["newPasswordOne"];

        if($data["newPasswordOne"] !== $data["newPasswordTwo"]){
            return $this->json([
                'message' => "Les nouveaux mots de passe entrés ne sont pas identiques."
            ],403);
        }

        $user = $this->getUser();

        if($user->verifyEnteredPassword($oldPassword) !== true){
            return $this->json([
                'message' => "Le mot de passe entré n'est pas le bon."
            ],403);
        }

        $user->setPassword(password_hash ($newPassword, PASSWORD_BCRYPT, ["cost" => 12]));

        $this->em->flush($user);

        return $this->json([
            'message' => "Mot de passe modifié avec succès."
        ],200);
    }


    /**
     * @Route("/api/modify/email", name="user_modify_email", methods={"PUT"})
     * Modify actual email
     */
    public function modifyActualEmail(Request $request){

        $data = $this->transformDataJsonIntoDataArray($request);
        $user = $this->getUser();

        if($user->verifyEnteredPassword($data["password"]) !== true){
            return $this->json([
                'message' => "Le mot de passe entré est incorect."
            ],403);
        }

        $userOrders = $user->changeEmailOfAllMyOrders($data["newEmail"]);
        $this->em->flush($userOrders);

        // changement de l'email
        $user->setEmail($data["newEmail"]);

        $this->em->flush($user);

        return $this->json([
            'message' => "Email modifié avec succès."
        ],200);
    }

}

























// class UserController extends AbstractController
// {
//     private $em;
//     private $validator;
//     private $userPasswordEncoderInterface;

//     public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, UserPasswordEncoderInterface $userPasswordEncoderInterface)
//     {
//         $this->em = $em;
//         $this->validator = $validator;
//         $this->userPasswordEncoderInterface = $userPasswordEncoderInterface;
//     }

//     private function sendNewResponseJson($dataJsonFormat)
//     {
//         $response = new Response();
//         $response->setContent($dataJsonFormat);
//         $response->headers->set('Content-Type', 'application/json');
//         return $response;
//     }

//     private function validateDataWithConstraints ($data)
//     {
//         $errors = $this->validator->validate($data);
//         if (count($errors) > 0) {
//             return $this->json($errors,400);
//         }
//         return new Response('Les données sont valides.');
//     }

//     private function encryptDataPassword ($data)
//     {
//         $user = new User();
//         $password = $data->getPassword(); // password qui doit être crypté
//         $passwordHashed = $this->userPasswordEncoderInterface->encodePassword($user, $password);
//         $data->setPassword($passwordHashed);

//         return $data;
//     }

//     private function transformDataJsonIntoDataArray()
//     {
//         $request = new Request();
//         $dataJson = $request->getContent();
//         $dataStdClass = json_decode($dataJson);
//         $data = (array) $dataStdClass;

//         return $data;
//     }

    

//     // /**
//     //  * @Route("/admin/users", name="users", methods={"GET"})
//     //  * Display all accounts
//     //  */
//     // public function getUsers(UserRepository $userRepository, SerializerInterface $serializerInterface)
//     // {
//     //     $data = $userRepository->findAll();
//     //     $dataJsonFormat = $serializerInterface->serialize($data,"json");

//     //     if(!empty($dataJsonFormat)){
//     //         return $this->sendNewResponseJson($dataJsonFormat);
//     //     }
//     // }
    

//     /**
//      * @Route("/api/connectedAccount", name="user_information", methods={"GET"})
//      * Display all informations of a user
//      */
//     public function getUserInformation( SerializerInterface $serializerInterface)
//     {
//         $user = $this->getUser();
//         $userJsonFormat = $serializerInterface->serialize($user, "json", ["groups" => "UserInformation"]);

//         if(!empty($userJsonFormat)){
//             return $this->sendNewResponseJson($userJsonFormat);
//         }
//     }

//     /**
//      * @Route("/register", name="register", methods={"POST"})
//      * Create an account
//      */
//     public function register(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $em)
//     {
//         $json = $request->getContent();

//         try{

//             $data = $serializerInterface->deserialize($json,User::class,"json");
//             $this->validateDataWithConstraints($data);

//             $data->setRoles(["ROLE_USER"]);
//             $this->encryptDataPassword($data);

//             $em->persist($data);
//             $em->flush();

//             return $this->json([
//                 'email' => $data->getUsername(),
//                 'role' => $data->getRoles()
//             ],201);

//         }catch(NotEncodableValueException $e){
//             return $this->json([
//                 "status" => 400,
//                 "erreur" => $e->getMessage()
//             ]);
//         }
//     }


//     /**
//      * @Route("/api/user/paymentInformations", name="send_user_payment_informations", methods={"PUT"})
//      * send User Payment Informations
//      */
//     public function sendUserPaymentInformations(Request $request, EntityManagerInterface $em)
//     {
//         $dataJsonFormat = $request->getContent();
//         $data = json_decode($dataJsonFormat);

//         $user = $this->getUser();

//         $user->setFirstName($data->firstName);
//         $user->setLastName($data->lastName); 
//         $user->setCity($data->city); 
//         $user->setAddress($data->address); 
//         $user->setPaymentMethod($data->paymentMethod); 
//         $user->setCardName($data->cardName); 
//         $user->setCardNumber($data->cardNumber); 
//         $user->setCardExpirationDate($data->cardExpirationDate); 
//         $user->setCryptogram($data->cryptogram); 

//         $em->persist($user);
//         $em->flush();
        
//         return $this->json([
//             'informations' => $data
//         ],200);
//     }


//     /**
//      * @Route("/api/modify/password", name="user_modify_password", methods={"POST"})
//      * Modify actual password
//      */
//     public function modifyActualPassword()
//     {
//         $data = $this->transformDataJsonIntoDataArray();

//         if($data["newPasswordOne"] !== $data["newPasswordTwo"]){
//             return $this->json([
//                 'message' => "Les nouveaux mots de passe entrées ne sont pas identiques."
//             ],403);
//         }

//         $oldPassword = $data["oldPassword"];
//         $newPassword = $data["newPasswordOne"];

//         // verifier le mdp actuel
//         $user = $this->getUser();
//         $cryptedOldPassword = $user->getPassword();
//         $response = password_verify ( $oldPassword , $cryptedOldPassword );

//         if($response !== true){
//             return $this->json([
//                 'message' => "Le mot de passe entré n'est pas le bon."
//             ],403);
//         }

//         // changer le mdp actuel par le nouveau mdp
//             // crypter le nouveau mdp
//         $newPasswordHashed =  password_hash ( $newPassword, PASSWORD_BCRYPT  , ["cost" => 12]);
//             // remplacement du mdp
//         $user->setPassword($newPasswordHashed);
            
//         $this->em->persist($user);
//         $this->em->flush();

//         return $this->json([
//             'message' => "Mot de passe modifié avec succès."
//         ],200);
//     }

//     /**
//      * @Route("/api/modify/email", name="user_modify_email", methods={"POST"})
//      * Modify actual email
//      */
//     public function modifyActualEmail(){

//         $data = $this->transformDataJsonIntoDataArray();

//         // vérification du mdp
//         $user = $this->getUser();
//         $password = $data["password"];
//         $hashedPassword = $user->getPassword();

//         $response = password_verify($password, $hashedPassword);

//         if($response !== true){
//             return $this->json([
//                 'message' => "Le mot de passe entré est incorect."
//             ],403);
//         }

//         // changement de l'email
//         $newEmail = $data["newEmail"];
//         $user->setEmail($newEmail);
            
//         $this->em->persist($user);
//         $this->em->flush();

//         return $this->json([
//             'message' => "Email modifié avec succès."
//         ],200);
//     }

// }