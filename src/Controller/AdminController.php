<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{

    /**
     * @Route("/admin/home/{category}/{page}/{sort}", name="admin_product_category_{category}", methods={"GET"})
     * Display the products per page from a specific category
     */
    public function getCategoryProducts (ProductRepository $productRepository,Request $request,  PaginatorInterface $paginator, NormalizerInterface $normalizerInterface, $category,$page, $sort)
    {

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
        

        $productsPerPage = 9;
        $allProducts = count($data);
        $pageNumber = ceil ($allProducts/$productsPerPage);

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
        

        
    }

    
    // /**
    //  * @Route("admin/products/all/{page}", name="admin_product_category_all", methods={"GET"})
    //  * Display the products per page
    //  */
    // public function getAllProducts (ProductRepository $productRepository,Request $request,  PaginatorInterface $paginator, $page)
    // {
    //     // 1) Récuperer les produits en bdd
    //     $data = $productRepository->findAll();

    //     $articles = $paginator->paginate(
    //         $data, // Requête contenant les données à paginer (ici nos articles)
    //         $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
    //         9 // Nombre de résultats par page
    //     );

    //     // les envoyer en réponse
    //     return $this->json($articles);
    // }


    // /**
    //  * @Route("admin/products/maison/{page}", name="admin_product_category_maison", methods={"GET"})
    //  * Get the maison category products per page
    //  */
    // public function MaisonProducts (ProductRepository $ProductRepository, Request $request,  PaginatorInterface $paginator, $page) {

    //     $data = $ProductRepository->findBy(["category"=>"maison"]);

    //     $articles = $paginator->paginate(
    //         $data, // Requête contenant les données à paginer (ici nos articles)
    //         $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
    //         9 // Nombre de résultats par page
    //     );

    //     // les envoyer en réponse
    //     return $this->json($articles);
    // }

    // /**
    //  * @Route("admin/products/informatique/{page}", name="admin_product_category_informatique", methods={"GET"})
    //  * Get the informatique/high-tech category products per page
    //  */
    // public function InformatiqueProducts (ProductRepository $ProductRepository,Request $request, PaginatorInterface $paginator , $page ) {

    //     $data = $ProductRepository->findBy(["category"=>"informatique/high-tech"]);

    //     $articles = $paginator->paginate(
    //         $data, // Requête contenant les données à paginer (ici nos articles)
    //         $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
    //         9 // Nombre de résultats par page
    //     );

    //     // les envoyer en réponse
    //     return $this->json($articles);
    // }

    // /**
    //  * @Route("admin/products/sports/{page}", name="admin_product_category_sports", methods={"GET"})
    //  * Get the sports/vetements category products per page
    //  */
    // public function SportsProducts (ProductRepository $ProductRepository,Request $request, PaginatorInterface $paginator , $page) {

    //     $data = $ProductRepository->findBy(["category"=>"sports/vetements"]);

    //     $articles = $paginator->paginate(
    //         $data, // Requête contenant les données à paginer (ici nos articles)
    //         $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
    //         9 // Nombre de résultats par page
    //     );

    //     // les envoyer en réponse
    //     return $this->json($articles);
    // }

    // /**
    //  * @Route("admin/products/livres/{page}", name="admin_product_category_livres", methods={"GET"})
    //  * Get the livres category products per page
    //  */
    // public function LivresProducts (ProductRepository $ProductRepository,Request $request, PaginatorInterface $paginator, $page ) {
    //     $data = $ProductRepository->findBy(["category"=>"livres"]);

    //     $articles = $paginator->paginate(
    //         $data, // Requête contenant les données à paginer (ici nos articles)
    //         $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
    //         9 // Nombre de résultats par page
    //     );

    //     // $pageNumber = $articles->getTotalItemCount()/9;

    //     // dd($articles);

    //     // les envoyer en réponse
    //     return $this->json($articles) ;

    // }


}
