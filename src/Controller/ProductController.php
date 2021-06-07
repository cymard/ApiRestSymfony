<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

class ProductController extends AbstractController
{


    private $paginator;
    private $em;
    private $normalizerInterface;
    private $serializerInterface;
    private $productRepository;

    public function __construct(ProductRepository $productRepository, SerializerInterface $serializerInterface, NormalizerInterface $normalizerInterface, EntityManagerInterface $em,PaginatorInterface $paginator)
    {

        $this->paginator = $paginator;
        $this->em = $em;
        $this->normalizerInterface = $normalizerInterface;
        $this->serializerInterface = $serializerInterface;
        $this->productRepository = $productRepository;
    }


    private function sendJsonResponse ($dataArray,$status = 200)
    {
        $response = new JsonResponse();
        $response->setContent(json_encode($dataArray));
        $response->setStatusCode($status);
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

    private function queryPaginator (Request $request, $query, $page, $productsPerPage)
    {
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

        foreach($productCommentsObject as $comment){
            $commentArrayFormat = $this->normalizerInterface->normalize($comment,null,["groups" => "commentWithoutProduct"]);
            array_unshift($productCommentsArray, $commentArrayFormat);
        }

        $average = $product->calculateAverageRates();

        if($average !== null){
            $rateNumber = count($product->getComments()->toArray());
            $allDataArray = [
                "product" => $productInArrayFormat,
                "comments" => $productCommentsArray,
                "arrayAveraging" => $average,
                "rateNumber" => $rateNumber
            ]; 
        }else{
            $allDataArray = [
                "product" => $productInArrayFormat,
                "comments" => $productCommentsArray,
                "averaging" => 0,
                "rateNumber" => 0
            ]; 
        }

        return $this->sendJsonResponse($allDataArray);
    }


    /**
     * @Route("/admin/product/{id}", name="product_delete", methods={"DELETE"})
     * Delete a product
     */
    public function deleteProduct (Product $product)
    {
        $this->em->remove($product);
        $this->em->flush();

        return $this->sendJsonResponse([
            "message" => "Produit supprimé"    
        ]);
    }

    /**
     * @Route("/admin/product", name="admin_delete_product", methods={"DELETE"})
     * Delete products
     */
    public function deleteProducts (ProductRepository $productRepository, Request $request){
        $idsToDelete = (array)json_decode($request->getContent());
        $idsToDelete = $idsToDelete["selectedProducts"];
        
        foreach($idsToDelete as $id){
            $product = $productRepository->find($id);

            $this->em->remove($product);
            $this->em->flush();
        }

        return $this->sendJsonResponse([
            'message' => "La suppression a été effectuée."
        ]);

    }



    /**
     * @Route("/admin/product/{id}/edit", name="product_put", methods={"PUT"})
     * Modify a product
     */
    public function modifyProduct (Product $actualProduct ,Request $request)
    {

        $newInformationsProductObject = $this->serializerInterface->deserialize($request->getContent(),Product::class,"json",["groups" => "productWithoutComments"]);
        $imageBase64 = $newInformationsProductObject->getImage();
 
        if($imageBase64 !== null ){
            $actualProduct->sendImageToImgbbAndReturnUrl($imageBase64);
            $actualProduct->replaceValuesByAnotherProduct($newInformationsProductObject, true);
        }else{
            $actualProduct->replaceValuesByAnotherProduct($newInformationsProductObject, false);
        }
        
        $this->em->flush($actualProduct);

        return $this->sendJsonResponse([
            "message" => "Produit modifié"
        ]);

    }


    /**
     * @Route("/admin/products", name="product_post", methods={"POST"})
     * Create a product
     */
    public function createProduct (Request $request)
    {
        try{
            $newProductInformations = $this->serializerInterface->deserialize($request->getContent(),Product::class,"json");
            $imageBase64 = $newProductInformations->getImage();

            if($imageBase64 !== null){
                // envoyer l'image dans le serveur d'image
                $newProductInformations->sendImageToImgbbAndReturnUrl($imageBase64);
            }

            $this->em->persist($newProductInformations);
            $this->em->flush();

            $newInformationsProductArray = $this->normalizerInterface->normalize($newProductInformations,null,["groups" => "productWithoutComments"]);
            return $this->sendJsonResponse($newInformationsProductArray);
            
            
        }catch(NotEncodableValueException $e){
            return $this->sendJsonResponse([
                "erreur" => $e->getMessage()
            ], 400);
        }
    }


    /**
     * @Route("/products", name="product_category", methods={"GET"})
     * Display the products per page from a specific category
     */
    public function getProductsFromACategory (ProductRepository $productRepository, Request $request)
    {
        $categoryQuery = $request->query->get('category');
        $pageQuery = (int)$request->query->get('page');
        $searchQuery = $request->query->get('search');
        $productsPerPage = 9;

        if( $categoryQuery && $pageQuery ){
            $category = $this->getCategory($categoryQuery);

            $dataQuery = $productRepository->searchProductWithCategory($category)->getQuery();

            $allProducts = count($dataQuery->getResult());
            $pageNumber = ceil ($allProducts/$productsPerPage);

            $articles = $this->queryPaginator($request, $dataQuery, $pageQuery, $productsPerPage);

            $array = $this->normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);
            // $allResponses = ["productsPerPageNumber" => $productsPerPage,"category"=> $category ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "pageContent"=>$array];
            $allResponses = [
                "productsPerPageNumber" => $productsPerPage,
                "category"=> $category ,
                "allProductsNumber" => $allProducts, 
                "totalPageNumber"=>$pageNumber, 
                "content"=>$array
            ];

            return $this->sendJsonResponse($allResponses);
        
        }else if($searchQuery && $pageQuery){
            $search = $searchQuery;

            $dataQuery = $productRepository->searchProduct($search)->getQuery();

            $allProducts = count($dataQuery->getResult());
            $pageNumber = ceil ($allProducts/$productsPerPage);

            $articles = $this->queryPaginator($request, $dataQuery, $pageQuery, $productsPerPage);

            $array = $this->normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);
            // $allResponses = ["productsPerPageNumber" => $productsPerPage,"search"=> $search ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "data"=>$array];
            $allResponses = [
                "productsPerPageNumber" => $productsPerPage,
                "search"=> $search,
                "allProductsNumber" => $allProducts,
                "totalPageNumber"=>$pageNumber,
                "content"=>$array
            ];
            
            return $this->sendJsonResponse($allResponses);
        }
    
    }

}

