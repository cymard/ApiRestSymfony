<?php

namespace App\Controller;

use App\Entity\CommentProduct;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

class CommentController extends AbstractController
{
    /**
     * @Route("/comment", name="comment")
     */
    public function index(): Response
    {
        return $this->render('comment/index.html.twig', [
            'controller_name' => 'CommentController',
        ]);
    }

    // /**
    //  * @Route("/api/comment", name="create_comment" , methods={"POST"})
    //  * create a comment
    //  */
    // public function createComment(CommentProductRepository $repo,SerializerInterface $serializerInterface, Request $request, ValidatorInterface $validator, EntityManagerInterface $em){

    //     try{
    //         // prendre le data des champs du comment
    //         $json = $request->getContent();

    //         // transformation du json en code
    //         $data = $serializerInterface->deserialize($json,CommentProduct::class,"json");


    //         // 3) validation des données reçues
    //         $errors = $validator->validate($data);

    //         if (count($errors) > 0) {
    //             /*
    //             * Uses a __toString method on the $errors variable which is a
    //             * ConstraintViolationList object. This gives us a nice string
    //             * for debugging.
    //             */
    //             $errorsString = (string) $errors;

    //             return new Response($errorsString);
    //         }

            

    //         // 4) envoyer les données en bdd
    //         $em->persist($data);
    //         $em->flush();

    //         // 5) retourner le produit créé
    //         $dataSerialized = $serializerInterface->serialize($data,"json");
    //         dd($dataSerialized);
            
    //         $response = new Response();
    //         $response->headers->set('Content-type','application/json');
    //         $response->setStatusCode(Response::HTTP_OK);
    //         $response->setContent($dataSerialized);
    //         return $response;
            
    //     }catch(NotEncodableValueException $e){
    //         return $this->json([
    //             "status" => 400,
    //             "erreur" => $e->getMessage()
    //         ]);
    //     }

    // }

}
