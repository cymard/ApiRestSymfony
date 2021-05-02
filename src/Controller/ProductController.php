<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
// use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{

    private $client;
    private $paginator;
    private $em;
    private $normalizerInterface;
    private $serializerInterface;

    public function __construct(SerializerInterface $serializerInterface, NormalizerInterface $normalizerInterface, EntityManagerInterface $em,HttpClientInterface $client,PaginatorInterface $paginator)
    {
        $this->client = $client;
        $this->paginator = $paginator;
        $this->em = $em;
        $this->normalizerInterface = $normalizerInterface;
        $this->serializerInterface = $serializerInterface;
    }

    private function sendNewResponse ($dataSerialized)
    {
        $response = new Response();
        $response->headers->set('Content-type','application/json');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($dataSerialized);
        return $response;
    }

    private function sendJsonResponse ($dataJsonFormat)
    {
        $response = new JsonResponse();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($dataJsonFormat);

        return $response;
    }

    private function getCategory(string $category){
        if($category === "sports"){
            $category = "sports/vetements";
        }else if ($category === "informatique"){
            $category = "informatique/high-tech";
        }

        return $category;
    }

    private function queryPaginator ($query, $page, $productsPerPage)
    {
        $request = Request::createFromGlobals();
        $articles = $this->paginator->paginate(
            $query, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $productsPerPage // Nombre de résultats par page
        );

        return $articles;
    }







    /**
     * @Route("/product/{id}", name="product_get", methods={"GET"})
     * Display one product
     */
    public function getProduct (Product $product)
    {
        $productInArrayFormat = $this->normalizerInterface->normalize($product,null,["groups" => "productWithoutComments"]);
        $productCommentsObject = $product->getComments()->toArray();
        $productCommentsArray = [];

        foreach($productCommentsObject as &$comment){
            $commentArrayFormat = $this->normalizerInterface->normalize($comment,null,["groups" => "commentWithoutProduct"]);
            array_unshift($productCommentsArray, $commentArrayFormat);
        }
        unset($comment);

        $average = $product->calculateAverageRates();

        if($average !== null){
            $rateNumber = count($product->getComments()->toArray());
            $allDataJson = json_encode(["product" => $productInArrayFormat, "comments" => $productCommentsArray, "arrayAveraging" => $average, "rateNumber" => $rateNumber]); 
        }else{
            $allDataJson = json_encode(["product" => $productInArrayFormat, "comments" => $productCommentsArray, "averaging" => 0, "rateNumber" => 0]); 
        }

        return $this->sendNewResponse($allDataJson);
    }


    /**
     * @Route("admin/product/{id}", name="product_delete", methods={"DELETE"})
     * Delete a product
     */
    public function deleteProduct (Product $product)
    {
        $this->em->remove($product);
        $this->em->flush();

        $response = new Response("Product deleted");
        return $response;
    }


    /**
     * @Route("/admin/product/{id}/edit", name="product_put", methods={"PUT"})
     * Modify a product
     */
    public function modifyProduct (Product $actualProduct)
    {
        $request = Request::createFromGlobals();

        $newInformationsProductObject = $this->serializerInterface->deserialize($request->getContent(),Product::class,"json",["groups" => "productWithoutComments"]);
        $imageBase64 = $newInformationsProductObject->getImage();
 
        if($imageBase64 !== null ){
            $actualProduct->sendImageToImgbbAndReturnUrl($imageBase64);
            $actualProduct->replaceValuesByAnotherProduct($newInformationsProductObject, true);
        }else{
            $actualProduct->replaceValuesByAnotherProduct($newInformationsProductObject, false);
        }
        
        $this->em->flush($actualProduct);

        return $this->json([
            "status" => 201,
            "message" => "Produit modifié"
        ]);

    }


    /**
     * @Route("/admin/products", name="product_post", methods={"POST"})
     * Create a product
     */
    public function createProduct ()
    {
        try{
            $request = Request::createFromGlobals();
            $newProductInformations = $this->serializerInterface->deserialize($request->getContent(),Product::class,"json");
            $imageBase64 = $newProductInformations->getImage();

            if($imageBase64 !== null){
                // envoyer l'image dans le serveur d'image
                $newProductInformations->sendImageToImgbbAndReturnUrl($imageBase64);
            }

            $this->em->persist($newProductInformations);
            $this->em->flush();

            $newProductInformationsJson = $this->serializerInterface->serialize($newProductInformations,"json",["groups" => "productWithoutComments"]);
            return $this->sendNewResponse($newProductInformationsJson);
            
            
        }catch(NotEncodableValueException $e){
            return $this->json([
                "status" => 400,
                "erreur" => $e->getMessage()
            ]);
        }
    }


    /**
     * @Route("/products", name="product_category", methods={"GET"})
     * Display the products per page from a specific category
     */
    public function getProductsFromACategory (ProductRepository $productRepository)
    {
        $request = Request::createFromGlobals();
        // $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $categoryQuery = $request->query->get('category');
        $pageQuery = (int)$request->query->get('page');
        $searchQuery = $request->query->get('search');
        $productsPerPage = 9;

        if( $categoryQuery && $pageQuery ){
            $category = $this->getCategory($categoryQuery);

            $dataQuery = $productRepository->searchProductWithCategory($category)->getQuery();

            $allProducts = count($dataQuery->getResult());
            $pageNumber = ceil ($allProducts/$productsPerPage);

            $articles = $this->queryPaginator($dataQuery, $pageQuery, $productsPerPage);

            $array = $this->normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);
            $allResponses = json_encode(["productsPerPageNumber" => $productsPerPage,"category"=> $category ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "pageContent"=>$array]);
            
            return $this->sendJsonResponse($allResponses);
        
        }else if($searchQuery && $pageQuery){
            $search = $searchQuery;

            $dataQuery = $productRepository->searchProduct($search)->getQuery();

            $allProducts = count($dataQuery->getResult());
            $pageNumber = ceil ($allProducts/$productsPerPage);

            $articles = $this->queryPaginator($dataQuery, $pageQuery, $productsPerPage);

            $array = $this->normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);
            $allResponses = json_encode(["productsPerPageNumber" => $productsPerPage,"search"=> $search ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "data"=>$array]);
            
            return $this->sendJsonResponse($allResponses);
        }
    
    }

}








































// class ProductController extends AbstractController
// {

//     private $client;

//     public function __construct(HttpClientInterface $client)
//     {
//         $this->client = $client;
//     }

//     private function sendJsonResponse ($dataSerialized)
//     {
//         $response = new Response();
//         $response->headers->set('Content-type','application/json');
//         $response->setStatusCode(Response::HTTP_OK);
//         $response->setContent($dataSerialized);
//         return $response;
//     }

//     /**
//      * Averaging of an array
//      */
//     private function arrayAveraging(array $array) 
//     {
//         $rateNumber = count($array);
//         $sum = 0;
//         foreach($array as $number){
//             $sum += $number;
//         }
//         $averaging = $sum/$rateNumber;

//         return $averaging;
//     }

//     /**
//      * @Route("/product/{id}", name="product_get", methods={"GET"})
//      * Display one product
//      */
//     public function getProduct (Product $product,  NormalizerInterface $normalizerInterface)
//     {
       
//         // le produit
//         $productInArrayFormat = $normalizerInterface->normalize($product,null,["groups" => "productWithoutComments"]);
       
//         // les commentaires
//         $commentsInCollectionFormat = $product->getComments();
//         $commentsInObjectFormat = $commentsInCollectionFormat->toArray();
//         $commentsNormalized = []; 
        
//         // l'ensemble des notes de commentaires du produit sur 5
//         $allRates = [];

//         foreach ($commentsInObjectFormat as $comment) {
//             array_unshift($allRates, $comment->getNote());
//             $comment = $normalizerInterface->normalize($comment, null , ["groups" => "commentWithoutProduct"]);
//             array_unshift($commentsNormalized, $comment);
//         }

//         // calcul de la moyenne des notes des commentaires
//         if(count($allRates) > 0){
//             $averaging = $this->arrayAveraging($allRates);
//             $rateNumber = count($allRates);
//             $allDataJson = json_encode(["product" => $productInArrayFormat, "comments" => $commentsNormalized, "arrayAveraging" => $averaging, "rateNumber" => $rateNumber]); 
//         }else{
//             $allDataJson = json_encode(["product" => $productInArrayFormat, "comments" => $commentsNormalized, "averaging" => 0, "rateNumber" => 0]); 
//         }

//         return $this->sendJsonResponse($allDataJson);
//     }


//     /**
//      * @Route("admin/product/{id}", name="product_delete", methods={"DELETE"})
//      * Delete a product
//      */
//     public function deleteProduct (Product $product,EntityManagerInterface $em)
//     {
//         $em->remove($product);
//         $em->flush();

//         $response = new Response("Product deleted");
//         return $response;
//     }


//     private function sendImageToImgbb($base64)
//     {
//         $response = $this->client->request('POST', 'https://api.imgbb.com/1/upload?expiration=15552000&key=602552f9aeec55ba40e0e73f6ab60d8b', [
//             'body' => [
//                 "image" => $base64
//             ]
//         ]);

//         $status = $response->getStatusCode();
    
//         if($status === 200){
//             $content = $response->getContent();
//             return $content;
//         }else{
//             return false;
//         }
//     }


//     /**
//      * @Route("/admin/product/{id}/edit", name="product_put", methods={"PUT"})
//      * Modify a product
//      */
//     public function setProduct (Product $product, Request $request, SerializerInterface $serializerInterface,EntityManagerInterface $em)
//     {
//         $json = $request->getContent();
        
//         $data = json_decode($json, true);

//         $newProductData = $serializerInterface->deserialize($json,Product::class,"json",["groups" => "productWithoutComments"]);

//         $imageBase64 = $data["image"];

//         // appel API vers imgBB pour enregistrer l'image
//         if($imageBase64 !== null ){
//             $imgbbDataJson = $this->sendImageToImgbb($imageBase64);

//             //  Vérification de la réponse de la requête
//             if($imgbbDataJson === false){
//                 // response error
//                 return $this->json([
//                     "status" => 500,
//                     "message" => "Impossible d'envoyer de télécharger l'image sur imgbb."
//                 ]);
//             }

//             // récupération de l'url de l'image
//             $imgbbData = json_decode($imgbbDataJson, true);
//             $imageUrl = $imgbbData["data"]["url"];
            
//             // récuperer le produit et le modifier
//             $product->setName($newProductData->getName());
//             $product->setPrice($newProductData->getPrice());
//             $product->setDescription($newProductData->getDescription());
//             $product->setImage($imageUrl);
//             $product->setStock($newProductData->getStock());

//         }else{
//             // pas de nouvelle image
//             // récuperer le produit et le modifier
//             $product->setName($newProductData->getName());
//             $product->setPrice($newProductData->getPrice());
//             $product->setDescription($newProductData->getDescription());
//             $product->setStock($newProductData->getStock());
//         }
        
//         $em->persist($product);
//         $em->flush();

//         return $this->json([
//             "status" => 201,
//             "message" => "Produit modifié"
//         ]);

//     }



//     /**
//      * @Route("/admin/products", name="product_post", methods={"POST"})
//      * Create a product
//      */
//     public function createProduct (Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $em)
//     {
//         try{
//             $json = $request->getContent();

//             $data = $serializerInterface->deserialize($json,Product::class,"json");
//             $imageBase64 = $data->getImage();

//             if($imageBase64 !== null){
//                 // télécharger image vers imgbb
//                 // mettre l'url dans la bdd
//                 $imgbbDataJson = $this->sendImageToImgbb($imageBase64);

//                 //  Vérification de la réponse de la requête
//                 if($imgbbDataJson === false){
//                     // response error
//                     return $this->json([
//                         "status" => 500,
//                         "message" => "Impossible de télécharger l'image sur imgbb."
//                     ]);
//                 }

//                 // récupération de l'url de l'image
//                 $imgbbData = json_decode($imgbbDataJson, true);
//                 $imageUrl = $imgbbData["data"]["url"];

//                 $data->setImage($imageUrl);

//                 $em->persist($data);
//                 $em->flush();

//             }else{
//                 $em->persist($data);
//                 $em->flush();

//             }

//             $dataSerialized = $serializerInterface->serialize($data,"json",["groups" => "productWithoutComments"]);
//             $response = new Response();
//             $response->headers->set('Content-type','application/json');
//             $response->setStatusCode(Response::HTTP_OK);
//             $response->setContent($dataSerialized);
//             return $response;
            
            
//         }catch(NotEncodableValueException $e){
//             return $this->json([
//                 "status" => 400,
//                 "erreur" => $e->getMessage()
//             ]);
//         }
//     }

//     private function getCategory(string $category){
//         if($category === "sports"){
//             $category = "sports/vetements";
//         }else if ($category === "informatique"){
//             $category = "informatique/high-tech";
//         }

//         return $category;
//     }


//     /**
//      * @Route("/products", name="product_category", methods={"GET"})
//      * Display the products per page from a specific category
//      */
//     public function getCategoryProducts (ProductRepository $productRepository,Request $request,  PaginatorInterface $paginator, NormalizerInterface $normalizerInterface)
//     {
//         if( $request->query->get('category') &&$request->query->get('page') ){

//             $category = $this->getCategory($request->query->get("category"));
//             $page = (int)$request->query->get("page");

//             $data = $productRepository->searchProductWithCategory($category);
//             $query = $data->getQuery();

//             $productsPerPage = 9;
//             $allProducts = count($query->getResult());
//             $pageNumber = ceil ($allProducts/$productsPerPage);

//             $articles = $paginator->paginate(
//                 $query, // Requête contenant les données à paginer (ici nos articles)
//                 $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
//                 $productsPerPage // Nombre de résultats par page
//             );

//             $array = $normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);

//             $response = new JsonResponse();
//             $response->headers->set('Content-Type', 'application/json');

//             $allResponses = json_encode(["productsPerPageNumber" => $productsPerPage,"category"=> $category ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "pageContent"=>$array]);

//             $response->setContent($allResponses);

//             return $response;
        
//         }else if($request->query->get('search') && $request->query->get('page')){
//             $search = $request->query->get('search');
//             $page = $request->query->get('page');

//             $data = $productRepository->searchProduct($search);
//             $query = $data->getQuery();

//             $productsPerPage = 9;
//             $allProducts = count($query->getResult());
//             $pageNumber = ceil ($allProducts/$productsPerPage);

//             $articles = $paginator->paginate(
//                 $query, // Requête contenant les données à paginer (ici nos articles)
//                 $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
//                 $productsPerPage // Nombre de résultats par page
//             );

//             $array = $normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);

//             $response = new JsonResponse();
//             $response->headers->set('Content-Type', 'application/json');

//             $allResponses = json_encode(["productsPerPageNumber" => $productsPerPage,"search"=> $search ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "data"=>$array]);

//             $response->setContent($allResponses);

//             return $response;
//         }
    
//     }

// }

