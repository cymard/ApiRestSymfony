<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{

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

    
    /**
     * @Route("/admin/home", name="admin_product_category", methods={"GET"})
     * Display the products per page from a specific category
     */
    public function getCategoryProducts (Request $request,ProductRepository $productRepository,  PaginatorInterface $paginator, NormalizerInterface $normalizerInterface, EntityManagerInterface $em)
    {
        if($request->query->get('search') && $request->query->get('category') && $request->query->get('page') && $request->query->get('sorting')){
            // récuperer la valeur de la recherche
            $search =  $request->query->get('search');
            $page = (int)$request->query->get('page');
            $sort = $request->query->get('sorting');
            $category =  $this->getCategory($request->query->get('category'));

            // faire la recherche
            $data = $productRepository->searchProductAdmin($search,$category, $sort);
           
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
            $allResponses = json_encode(["productsPerPageNumber" => $productsPerPage,"search"=> $search ,"allProductsNumber" => $allProducts, "totalPageNumber"=>$pageNumber, "pageContent"=>$array]);

            $response->setContent($allResponses);

            return $response;


        }else if($request->query->get('category') && $request->query->get('page') && $request->query->get('sorting') ){
            $sort = $request->query->get('sorting');
            $category =  $this->getCategory($request->query->get('category'));
            $page = (int)$request->query->get('page');

            $data = $productRepository->searchProductAdminWithCategory($category,$sort);

            $query = $data->getQuery();

            $allProducts = count($query->getResult());
            $productsPerPage = 9;
            $pageNumber = ceil ($allProducts/$productsPerPage);
            

            $articles = $paginator->paginate(
                $query, // query
                $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                $productsPerPage // Nombre de résultats par page
            );

            // dd($articles);
            // convertion des objets en tableaux
            $array = $normalizerInterface->normalize($articles,null,["groups" => "productWithoutComments"]);
 
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

    /**
     * @Route("/admin/product", name="admin_delete_product", methods={"DELETE"})
     * Delete products
     */
    public function deleteProduct(Request $request, ProductRepository $repo, EntityManagerInterface $entityManager){
        $json = $request->getContent();
        $idToDelete = json_decode ( $json );

        foreach($idToDelete->data as $id ){
            // identifier le produit
            $product = $repo->find($id);

            // le delete
            $entityManager->remove($product);
            $entityManager->flush();
        }

        $response = new JsonResponse(['status' => 202,'message' => 'All products deleted']);
        return $response;
    }




}
