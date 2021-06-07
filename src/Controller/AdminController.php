<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    private $paginator;
    private $productRepository;
    private $normalizerInterface;

    public function __construct (NormalizerInterface $normalizerInterface, ProductRepository $productRepository, PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
        $this->productRepository = $productRepository;
        $this->normalizerInterface = $normalizerInterface;
    }

    private function getCategory (string $category) 
    {
        if( $category === "sports"){
            return "sports/vetements";
        }else if($category === "informatique"){
            return "informatique/high-tech";
        }else{
            return $category;
        }
    }

 
    private function sendJsonResponse ($dataArray,$status = 200)
    {
        $response = new JsonResponse();

        $response->setContent(json_encode($dataArray));
        
        $response->setStatusCode($status);
        return $response;
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
     * @Route("/admin/home", name="admin_product_category", methods={"GET"})
     * Display the products per page from a specific category
     */
    public function getProductsByCategory (Request $request)
    {
        $searchQuery = $request->query->get('search');
        $categoryQuery = $this->getCategory($request->query->get('category'));
        $pageQuery = (int)$request->query->get('page');
        $sortingQuery = $request->query->get('sorting');

        $productsPerPage = 9;

        if($searchQuery && $categoryQuery && $pageQuery && $sortingQuery){

            // faire la recherche
            $queryBuilder = $this->productRepository->searchProductAdmin($searchQuery, $categoryQuery, $sortingQuery);
            $query = $queryBuilder->getQuery();
         
            $allProducts = count($query->getResult());
            $pageNumber = ceil ($allProducts/$productsPerPage);
            
            $articles = $this->queryPaginator($request, $query, $pageQuery, $productsPerPage);
            $articlesArray = $this->normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);


            return $this->sendJsonResponse([
                "productsPerPageNumber" => $productsPerPage,
                "search"=> $searchQuery,
                "allProductsNumber" => $allProducts,
                "totalPageNumber"=>$pageNumber,
                "pageContent"=>$articlesArray
            ]);


        }else if($categoryQuery && $pageQuery && $sortingQuery ){

            $queryBuilder = $this->productRepository->searchProductAdminWithCategory($categoryQuery,$sortingQuery);
            $query = $queryBuilder->getQuery();

            $allProducts = count($query->getResult());
            $pageNumber = ceil ($allProducts/$productsPerPage);
        
            $articles = $this->queryPaginator($request, $query, $pageQuery, $productsPerPage);
            $articlesArray = $this->normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);

            return $this->sendJsonResponse([
                "productsPerPageNumber" => $productsPerPage,
                "category"=> $categoryQuery,
                "allProductsNumber" => $allProducts,
                "totalPageNumber"=>$pageNumber,
                "pageContent"=>$articlesArray
            ]);

        }else{
            return $this->sendJsonResponse([
                'status' => 400,
                'message' => "Les paramètres de l'url ne sont pas valides."
            ]);
        }

    }

}
