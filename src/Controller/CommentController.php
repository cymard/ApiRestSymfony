<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\CommentRepository;


class CommentController extends AbstractController
{
    private $entityManager;
    private $validator;
    private $normalizerInterface;
    private $serializerInterface;
    private $commentRepository;

    public function __construct(CommentRepository $commentRepository,SerializerInterface $serializerInterface, NormalizerInterface $normalizerInterface, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->normalizerInterface = $normalizerInterface;
        $this->serializerInterface = $serializerInterface;
        $this->commentRepository = $commentRepository;
    }

    private function sendJsonResponse ($dataArray,$status = 200)
    {
        $response = new JsonResponse();
        $response->setContent(json_encode($dataArray));
        $response->setStatusCode($status);
        return $response;
    }

    private function validateDataWithConstraints ($data)
    {
        $errors = $this->validator->validate($data);
        if (count($errors) > 0) {
            return $this->json($errors,400);
        }
        return $this->sendJsonResponse([
            "message" => 'Les données sont valides.'
        ]);
    }

    private function setCommentReportingState (Comment $comment, $state)
    {

        $reportedComment = $comment->setIsReported($state);
        $this->entityManager->flush($reportedComment);

        if($state === true){
            return $this->sendJsonResponse([
                "message" => "Comment reported"
            ]);
        }else{
            return $this->sendJsonResponse( [
                "message" => "Comment ignored"
            ]);
        }
    }




    

    /**
     * @Route("/admin/product/{id}/comments", name="product_comments", methods={"GET"})
     */
    public function getProductComments(Product $product){

        $allComments = $product->getComments()->toArray();
        $allCommentsJson = [];

        foreach($allComments as $comment){
            $commentJson  = $this->normalizerInterface->normalize($comment, "json",["groups" => "commentWithoutProduct"]);
            array_unshift($allCommentsJson, $commentJson);
        }

        return $this->sendJsonResponse($allCommentsJson);

    }



    /**
     * @Route("/api/product/{id}/comment", name="product_create_comment", methods={"POST"})
     */
    public function createComment(Product $product,Request $request) {

        $commentObject = $this->serializerInterface->deserialize($request->getContent(), Comment::class, "json");

        // vérification des données
        $this->validateDataWithConstraints($commentObject);
        
        // ajout du commentaire au produit correspondant
        $product->addComment($commentObject);

        $this->entityManager->persist($commentObject);
        $this->entityManager->flush();

        $commentArray = $this->normalizerInterface->normalize($commentObject, null, ["groups" => "commentWithoutProduct"]);
        return $this->sendJsonResponse( $commentArray);
    }

    /**
     * @Route("/admin/comment/{id}", name="product_delete_comment", methods={"DELETE"})
     * Delete comment
     */
    public function deleteComment(Comment $comment)
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
        return $this->sendJsonResponse([ 
            "message" => "Comment deleted"
        ]);
    }

    /**
     * @Route("/api/comment/{id}", name="report_comment", methods={"PUT"})
     * Report comment
     */
    public function reportComment(Comment $comment)
    {
        return $this->setCommentReportingState($comment, true);
    }

    /**
     * @Route("/admin/comment/{id}", name="ignore_reported_comment", methods={"PUT"})
     * ignore reported comment
     */
    public function ignoreReportedComment(Comment $comment)
    {
        return $this->setCommentReportingState($comment, false);
    }

    /**
     * @Route("/admin/comments/reported", name="admin_display_reported_comments", methods={"GET"})
     * Display reported comments
     */
    public function displayReportedComments () 
    {
        $commentsObject = $this->commentRepository->findBy(["isReported"=> true]);
        $commentsArray = $this->normalizerInterface->normalize($commentsObject,null, ["groups" => "commentWithoutProduct"]);
        return $this->sendJsonResponse($commentsArray);
    }

}
