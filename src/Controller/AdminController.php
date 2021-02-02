<?php

namespace App\Controller;


use App\Entity\UserAdmin;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;

class AdminController extends AbstractController
{


    /**
     * @Route("/admin/home", name="admin_product_category", methods={"GET"})
     * Display the products per page from a specific category
     */
    public function getCategoryProducts (ProductRepository $productRepository,Request $request,  PaginatorInterface $paginator, NormalizerInterface $normalizerInterface)
    {
        if($request->query->get('category') && $request->query->get('page') && $request->query->get('sort')){
            $sort = $request->query->get('sort');
            $category =  $request->query->get('category');
            $page = (int)$request->query->get('page');


            // 1) Récuperer les produits en bdd
            if($sort === "default"){
                if($category === "all"){
                    $data = $productRepository->findAll();
                }else if( $category === "sports"){
                    $data = $productRepository->findBy(["category"=>"sports/vetements"]);
                }else if($category === "informatique"){
                    $data = $productRepository->findBy(["category"=>"informatique/high-tech"]);
                }else{
                    $data = $productRepository->findBy(["category"=>$category]);
                }
            }else if($sort === "desc" || $sort === "asc"){
                if($category === "all"){
                    $data = $productRepository->findSort($sort);
                }else if( $category === "sports"){
                    $data = $productRepository->findBy(["category"=>"sports/vetements"],['price' => $sort]);
                }else if($category === "informatique"){
                    $data = $productRepository->findBy(["category"=>"informatique/high-tech"],['price' => $sort]);
                }else{
                    $data = $productRepository->findBy(["category"=>$category],['price' => $sort]);
                }
                
            }

            // ne contient aucun probleme de sort
            // dd(json_encode(["productsPerPageNumber" =>$normalizerInterface->normalize($data)])); 

            $productsPerPage = 9;
            $allProducts = count($data);
            $pageNumber = ceil ($allProducts/$productsPerPage);
            // $page = intVal($page);

            // le probleme du sort
            $articles = $paginator->paginate(
                $data, // Requête contenant les données à paginer (ici nos articles)
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

        }else{

            return $this->json(['status' => 400, 'message' => "Les paramètres de l'url ne sont pas valides."]);

        }


        
    }

    
  


}