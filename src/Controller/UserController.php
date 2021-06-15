<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;



class UserController extends AbstractController
{
    private $em;
    private $validator;
    private $userPasswordEncoderInterface;
    private $normalizerInterface;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, UserPasswordEncoderInterface $userPasswordEncoderInterface, NormalizerInterface $normalizerInterface)
    {
        $this->em = $em;
        $this->validator = $validator;
        $this->userPasswordEncoderInterface = $userPasswordEncoderInterface;
        $this->normalizerInterface = $normalizerInterface;
    }

    private function sendJsonResponse ($dataArray,$status = 200)
    {
        $response = new JsonResponse();
        $response->setContent(json_encode($dataArray));
        $response->setStatusCode($status);
        return $response;
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
        $userArray= $this->normalizerInterface->normalize($userObject, "json", ["groups" => "UserInformation"]);

        if(!empty($userArray)){
            return $this->sendJsonResponse($userArray);
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
            $dataObject->setRoles(["ROLE_USER"]);
            // $errors = $this->validator->validate($dataObject);
            // if (count($errors) > 0) {
            //     return $this->json((string)$errors,400);
            // }

            $this->encryptDataPassword($dataObject);
            $this->saveInDatabase($dataObject);

            return $this->sendJsonResponse([
                'email' => $dataObject->getUsername(),
                'role' => $dataObject->getRoles()
            ],201);

        }catch(NotEncodableValueException $e){
            return $this->sendJsonResponse([
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
        
        return $this->sendJsonResponse([
            'informations' => $data
        ]);
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
            return $this->sendJsonResponse([
                'message' => "Les nouveaux mots de passe entrés ne sont pas identiques."
            ],403);
        }

        $user = $this->getUser();

        if($user->verifyEnteredPassword($oldPassword) !== true){
            return $this->sendJsonResponse([
                'message' => "Le mot de passe entré est incorrect."
            ],403);
        }

        $user->setPassword(password_hash ($newPassword, PASSWORD_BCRYPT, ["cost" => 12]));

        $this->em->flush($user);

        return $this->sendJsonResponse([
            'message' => "Mot de passe modifié avec succès."
        ]);
    }


    /**
     * @Route("/api/modify/email", name="user_modify_email", methods={"PUT"})
     * Modify actual email
     */
    public function modifyActualEmail(Request $request){

        $data = $this->transformDataJsonIntoDataArray($request);
        $user = $this->getUser();

        if($user->verifyEnteredPassword($data["password"]) !== true){
            return $this->sendJsonResponse([
                'message' => "Le mot de passe entré est incorrect."
            ],403);
        }

        $userOrders = $user->changeEmailOfAllMyOrders($data["newEmail"]);
        $this->em->flush($userOrders);

        // changement de l'email
        $user->setEmail($data["newEmail"]);

        $this->em->flush($user);

        return $this->sendJsonResponse([
            'message' => "Email modifié avec succès."
        ]);
    }

}



