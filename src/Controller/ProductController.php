<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CommentRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\CommentProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;


class ProductController extends AbstractController
{

    /**
     * @Route("/product/{id}", name="product_get", methods={"GET"})
     * Display one product
     */
    public function getProduct (Product $product,  NormalizerInterface $normalizerInterface, SerializerInterface $serializerInterface,$id)
    {
       
        // Le product
        $productArrayFormat = $normalizerInterface->normalize($product,null,["groups" => "productWithoutComments"]);
    
        // Les commentaires
        $collection = $product->getComments();
        $commentsObjectFormat = $collection->toArray(); // les commentaires sont sous forme d'objet
        $commentsNormalized = []; 

        foreach ($commentsObjectFormat as $comment) {

            $comment = $normalizerInterface->normalize($comment, null , ["groups" => "commentWithoutProduct"]); // fait passer les objets sous forme de tableaux

            array_push($commentsNormalized, $comment);
        }



        $allDataJson = json_encode(["product" => $productArrayFormat, "comments" => $commentsNormalized]); 


        // La réponse en json
        $response = new Response();
        $response->setContent($allDataJson);
        $response->headers->set('Content-type','application/json');
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }

    /**
     * @Route("admin/product/{id}", name="product_delete", methods={"DELETE"})
     * Delete a product
     */
    public function deleteProduct (Product $product,EntityManagerInterface $em)
    {
        // gerer : erreur no product
        // 1) recuperer le produit
        // 2) supprimer le produit
        $em->remove($product);
        $em->flush();

        // 3) retourner la reponse (status 200)
        $response = new Response("Product deleted");
        return $response;
    }

    /**
     * @Route("/admin/product/{id}/edit", name="product_put", methods={"PUT"})
     * Modify a product
     */
    public function setProduct (Product $product, Request $request, SerializerInterface $serializerInterface,EntityManagerInterface $em, ValidatorInterface $validator)
    {
        // essaie d'encoder la donnée
        try{
            // 1) recuperer le produit modifié
            $json = $request->getContent();
            $newProduct = $serializerInterface->deserialize($json,Product::class,"json");
 
            // 2) validation des données reçues
            $errors = $validator->validate($newProduct);

            if (count($errors) > 0) {
                /*
                * Uses a __toString method on the $errors variable which is a
                * ConstraintViolationList object. This gives us a nice string
                * for debugging.
                */
                $errorsString = (string) $errors;

                return new Response($errorsString);
            }

            // 3) recuperer le produit à modifier
            // 4) faire la modification (pour tous les champs)
            $product->setName($newProduct->getName());
            $product->setPrice($newProduct->getPrice());
            $product->setDescription($newProduct->getDescription());
            $product->setImage($newProduct->getImage());
            $product->setStock($newProduct->getStock());

            // 5) Envoyer vers la bdd
            $em->persist($product);
            $em->flush();

            // 6) retourner le produit modifié
            return $this->json($product, 201);

        }catch(NotEncodableValueException $e){
            return $this->json([
                "status" => 400,
                "erreur" => $e->getMessage()
            ]);
        }
        
    }

    /**
     * @Route("/admin/products", name="product_post", methods={"POST"})
     * Create a product
     */
    public function createProduct (Request $request, SerializerInterface $serializerInterface, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        try{
            // 1) recuperer les données du produit
            $json = $request->getContent();
            

            // 2) transformation du json en code
            $data = $serializerInterface->deserialize($json,Product::class,"json");

            // 3) validation des données reçues
            $errors = $validator->validate($data);

            if (count($errors) > 0) {
                /*
                * Uses a __toString method on the $errors variable which is a
                * ConstraintViolationList object. This gives us a nice string
                * for debugging.
                */
                $errorsString = (string) $errors;

                return new Response($errorsString);
            }

            // 4) envoyer les données en bdd
            $em->persist($data);
            $em->flush();

            // 5) retourner le produit créé
            $dataSerialized = $serializerInterface->serialize($data,"json");

            
            $response = new Response();
            $response->headers->set('Content-type','application/json');
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($dataSerialized);
            return $response;
            
        }catch(NotEncodableValueException $e){
            return $this->json([
                "status" => 400,
                "erreur" => $e->getMessage()
            ]);
        }
    }

    private function getCategory(string $category){
        if($category === "sports"){
            $category = "sports/vetements";
        }else if ($category === "informatique"){
            $category = "informatique/high-tech";
        }

        return $category;
    }


    /**
     * @Route("/products", name="product_category", methods={"GET"})
     * Display the products per page from a specific category
     */
    public function getCategoryProducts (EntityManagerInterface $em,ProductRepository $productRepository,Request $request,  PaginatorInterface $paginator, NormalizerInterface $normalizerInterface)
    {
        if( $request->query->get('category') &&$request->query->get('page') ){

            $category = $this->getCategory($request->query->get("category"));
            $page = (int)$request->query->get("page");


            // 1) Récuperer les produits en bdd
            if($category === "all"){
                // $data = $productRepository->findAll();
                $query = $em->createQuery(
                    'SELECT u FROM App\Entity\Product u'
                );
            }else{
                // $data = $productRepository->findBy(["category"=>$category]);
                $query = $em->createQuery(
                    'SELECT u FROM App\Entity\Product u WHERE u.category = :category'
                )->setParameter('category' , $category);
            }


            $productsPerPage = 9;
            $allProducts = count($query->getResult());
            $pageNumber = ceil ($allProducts/$productsPerPage);

            $articles = $paginator->paginate(
                $query->getResult(), // Requête contenant les données à paginer (ici nos articles)
                $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                $productsPerPage // Nombre de résultats par page
            );


            // convertion des objets en tableaux
            $array = $normalizerInterface->normalize($articles);

            // responses
            $response = new JsonResponse();
            $response->headers->set('Content-Type', 'application/json');

            // convertion des tableaux en json
            $allResponses = json_encode(["productsPerPageNumber" => $productsPerPage,"category"=> $category ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "pageContent"=>$array]);

            $response->setContent($allResponses);

            return $response;
        
        }
    }

}
