<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\CommentRepository;

class CommentController extends AbstractController
{

    /**
     * @Route("/admin/product/{id}/comments", name="product_comments", methods={"GET"})
     */
    public function getCommentsOfAProduct(Product $product, NormalizerInterface $normalizerInterface){

        $allCommentsCollection = $product->getComments();
        $allCommentsArray = $allCommentsCollection->toArray();
        $allCommentsJson = [];

        foreach($allCommentsArray as $comment){
            $commentJson  = $normalizerInterface->normalize($comment, "json",["groups" => "commentWithoutProduct"]);
            array_unshift($allCommentsJson, $commentJson);
        }

        $response = new Response();
        $response->setContent(json_encode($allCommentsJson));
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }



     /**
     * @Route("/api/product/{id}/comment", name="product_create_comment", methods={"POST"})
     */
    public function createComment(Product $product, Request $request, SerializerInterface $serializerInterface, ValidatorInterface $validator,  NormalizerInterface $normalizerInterface,EntityManagerInterface  $entityManager) {
    

        // récuperer les données 
        $data = $request->getContent();

        // transformer les données en une entité comment
        $dataObject = $serializerInterface->deserialize($data, Comment::class, "json");
        // $dataObject->setDate();


        // vérification des données

        $errors = $validator->validate($dataObject);

        if (count($errors) > 0) {
            /*
            * Uses a __toString method on the $errors variable which is a
            * ConstraintViolationList object. This gives us a nice string
            * for debugging.
            */
            $errorsString = (string) $errors;

            return new Response($errorsString);
        }
        
        // ajout du commentaire au produit correspondant
        $product->addComment($dataObject);

        // envoyer les données à la db
        $entityManager->persist($dataObject);
        $entityManager->flush();


        $newCommentJson = $serializerInterface->serialize($dataObject, "json", ["groups" => "commentWithoutProduct"]);

        $response = new Response();
        $response->setContent($newCommentJson);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }



     /**
     * @Route("/admin/comment/{id}", name="product_delete_comment", methods={"DELETE"})
     * Delete comment
     */
    public function deleteComment(Comment $comment,EntityManagerInterface  $entityManager)
    {
        // suppression de $comment de la bdd
        $entityManager->remove($comment);
        $entityManager->flush();

        // response
        $response = new JsonResponse(['status' => 200, "message" => "Comment deleted"]);
        return $response;

    }



     /**
     * @Route("/api/comment/{id}", name="report_comment", methods={"PUT"})
     * Report comment
     */
    public function reportComment(Comment $comment,EntityManagerInterface  $entityManager)
    {
        // recup data
       
        $reportedComment = $comment->setIsReported(true);
        $entityManager->persist($reportedComment);
        $entityManager->flush();


        // response
        $response = new JsonResponse(['status' => 200, "message" => "Comment reported"]);
        return $response;

    }


    /**
     * @Route("/admin/comment/{id}", name="ignore_reported_comment", methods={"PUT"})
     * ignore reported comment
     */
    public function ignoreReportedComment(Comment $comment,EntityManagerInterface  $entityManager)
    {
        // recup data
       
        $reportedComment = $comment->setIsReported(false);
        $entityManager->persist($reportedComment);
        $entityManager->flush();


        // response
        $response = new JsonResponse(['status' => 200, "message" => "Comment ignored"]);
        return $response;

    }

    /**
     * @Route("/admin/comments/reported", name="admin_display_reported_comments", methods={"GET"})
     * Display reported comments
     */
    public function displayReportedComments (CommentRepository $repo, NormalizerInterface $normalizerInterface) 
    {
        $commentsObject = $repo->findBy(["isReported"=> true]);

        $commentsArray = [];

        foreach ($commentsObject as $comment) {
            // normalize le product et push
            $commentArray = $normalizerInterface->normalize($comment,"json", ["groups" => "commentWithoutProduct"]);
            array_unshift($commentsArray, $commentArray);
            
        }

        $json = json_encode($commentsArray);
        $response = new Response($json);
        return $response;


    }

}
