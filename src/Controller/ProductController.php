<?php

namespace App\Controller;

use GuzzleHttp\Client;
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
use Symfony\Contracts\HttpClient\HttpClientInterface;


class ProductController extends AbstractController
{

    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Averaging of an array
     */
    private function averaging(array $array) 
    {
        $rateNumber = count($array);

        // $averaging = 
        $sum = 0;

        foreach($array as $number){
            $sum += $number;
        }

        $averaging = $sum/$rateNumber;

        return $averaging;
    }

    /**
     * @Route("/product/{id}", name="product_get", methods={"GET"})
     * Display one product
     */
    public function getProduct (Product $product,  NormalizerInterface $normalizerInterface, SerializerInterface $serializerInterface,$id)
    {
       
        // Le product
        $productInArrayFormat = $normalizerInterface->normalize($product,null,["groups" => "productWithoutComments"]);
       

        // Les commentaires
        $commentsInCollectionFormat = $product->getComments();
        $commentsInObjectFormat = $commentsInCollectionFormat->toArray();
        $commentsNormalized = []; 
        
        // l'ensemble des notes de commentaires du produit sur 5
        $allRates = [];

        foreach ($commentsInObjectFormat as $comment) {
            // dd($comment);
            array_unshift($allRates, $comment->getNote());
            $comment = $normalizerInterface->normalize($comment, null , ["groups" => "commentWithoutProduct"]); // fait passer les objets sous forme de tableaux
            // dd($comment["date"]);
            array_unshift($commentsNormalized, $comment);
        }

        // calcul de la moyenne des notes des commentaires
        if(count($allRates) > 0){
            $averaging = $this->averaging($allRates);
            $rateNumber = count($allRates);
    
            $allDataJson = json_encode(["product" => $productInArrayFormat, "comments" => $commentsNormalized, "averaging" => $averaging, "rateNumber" => $rateNumber]); 
        }else{
    
            $allDataJson = json_encode(["product" => $productInArrayFormat, "comments" => $commentsNormalized, "averaging" => 0, "rateNumber" => 0]); 
    
        }
        
       
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

    private function sendImageToImgbb($base64)
    {
        $response = $this->client->request('POST', 'https://api.imgbb.com/1/upload?expiration=15552000&key=602552f9aeec55ba40e0e73f6ab60d8b', [
            'body' => [
                "image" => $base64
            ]
        ]);
        $status = $response->getStatusCode();
        
        if($status === 200){

            $content = $response->getContent();

            return $content;
            // return $data;
        }else{
            return false;
        }
       
       
    }


    /**
     * @Route("/admin/product/{id}/edit", name="product_put", methods={"PUT"})
     * Modify a product
     */
    public function setProduct (Product $product, Request $request, SerializerInterface $serializerInterface,EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $json = $request->getContent();
        
        $data = json_decode($json, true);

        $newProductData = $serializerInterface->deserialize($json,Product::class,"json",["groups" => "productWithoutComments"]);

        $imageBase64 = $data["image"];

        // appel API vers imgBB pour enregistrer l'image
        if($imageBase64 !== null ){
            $imgbbDataJson = $this->sendImageToImgbb($imageBase64);

                // Vérification de la réponse de la requête
            if($imgbbDataJson === false){
                // response error
                return $this->json([
                    "status" => 500,
                    "message" => "Impossible d'envoyer de télécharger l'image sur imgbb."
                ]);
            }

            // récupération de l'url de l'image
            $imgbbData = json_decode($imgbbDataJson, true);
            $imageUrl = $imgbbData["data"]["url"];
            
            // 3) recuperer le produit à modifier
            // 4) faire la modification (pour tous les champs)
            $product->setName($newProductData->getName());
            $product->setPrice($newProductData->getPrice());
            $product->setDescription($newProductData->getDescription());
            $product->setImage($imageUrl);
            $product->setStock($newProductData->getStock());

        }else{
            // pas de nouvelle image

            // 3) recuperer le produit à modifier
            // 4) faire la modification (pour tous les champs)
            $product->setName($newProductData->getName());
            $product->setPrice($newProductData->getPrice());
            $product->setDescription($newProductData->getDescription());
            $product->setStock($newProductData->getStock());

        }
        

        


        // 5) Envoyer vers la bdd
        $em->persist($product);
        $em->flush();

        // 6) retourner le produit modifié
        // return $this->json($product, 201);
        return $this->json([
            "status" => 201,
            "message" => "Produit modifié"
        ]);

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
            $imageBase64 = $data->getImage();

            if($imageBase64 !== null){
                // télécharger image vers imgbb
                // mettre l'url dans la bdd

                $imgbbDataJson = $this->sendImageToImgbb($imageBase64);
                //  Vérification de la réponse de la requête
                if($imgbbDataJson === false){
                    // response error
                    return $this->json([
                        "status" => 500,
                        "message" => "Impossible de télécharger l'image sur imgbb."
                    ]);
                }

                // récupération de l'url de l'image
                $imgbbData = json_decode($imgbbDataJson, true);
                $imageUrl = $imgbbData["data"]["url"];

                $data->setImage($imageUrl);

                // 4) envoyer les données en bdd
                $em->persist($data);
                $em->flush();

            }else{
                // 4) envoyer les données en bdd
                $em->persist($data);
                $em->flush();

            }

            // 5) retourner le produit créé
            $dataSerialized = $serializerInterface->serialize($data,"json",["groups" => "productWithoutComments"]);


            $response = new Response();
            $response->headers->set('Content-type','application/json');
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($dataSerialized);
            return $response;

            // // 3) validation des données reçues
            // $errors = $validator->validate($data);

            // if (count($errors) > 0) {
            //     /*
            //     * Uses a __toString method on the $errors variable which is a
            //     * ConstraintViolationList object. This gives us a nice string
            //     * for debugging.
            //     */
            //     $errorsString = (string) $errors;

            //     return new Response($errorsString);
            // }

            
            
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
            // if($category === "all"){
            //     // $data = $productRepository->findAll();
            //     $query = $em->createQuery(
            //         'SELECT product FROM App\Entity\Product product'
            //     );
            // }else{
            //     // $data = $productRepository->findBy(["category"=>$category]);
            //     $query = $em->createQuery(
            //         'SELECT product FROM App\Entity\Product product WHERE product.category = :category'
            //     )->setParameter('category' , $category);
            // }
            
            $data = $productRepository->searchProductWithCategory($category);
            $query = $data->getQuery();

            $productsPerPage = 9;
            $allProducts = count($query->getResult());
            $pageNumber = ceil ($allProducts/$productsPerPage);


            $articles = $paginator->paginate(
                $query, // Requête contenant les données à paginer (ici nos articles)
                $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                $productsPerPage // Nombre de résultats par page

            );

  
            // convertion des objets en tableaux
            $array = $normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);

            // responses
            $response = new JsonResponse();
            $response->headers->set('Content-Type', 'application/json');

            // convertion des tableaux en json
            $allResponses = json_encode(["productsPerPageNumber" => $productsPerPage,"category"=> $category ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "pageContent"=>$array]);

            $response->setContent($allResponses);

            return $response;
        
        }else if($request->query->get('search') && $request->query->get('page')){
            $search = $request->query->get('search');
            $page = $request->query->get('page');

            $data = $productRepository->searchProduct($search);
            $query = $data->getQuery();

            
            $productsPerPage = 9;
            $allProducts = count($query->getResult());
            $pageNumber = ceil ($allProducts/$productsPerPage);

            $articles = $paginator->paginate(
                $query, // Requête contenant les données à paginer (ici nos articles)
                $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                $productsPerPage // Nombre de résultats par page

            );

            // convertion des objets en tableaux
            $array = $normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);

            // responses
            $response = new JsonResponse();
            $response->headers->set('Content-Type', 'application/json');

            // convertion des tableaux en json
            $allResponses = json_encode(["productsPerPageNumber" => $productsPerPage,"search"=> $search ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "data"=>$array]);

            $response->setContent($allResponses);

            return $response;
        }
    
    }

    
    // /**
    //  * @Route("/admin/download/image", name="admin/download/image", methods={"POST"})
    //  * Download image
    //  */
    // public function downloadImage()
    // {
    //     // récuperer la base64 de l'image
    //     dd("ca marche");
    // }
}
