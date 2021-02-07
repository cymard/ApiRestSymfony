<?php

namespace App\Controller;


use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;

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
    public function getCategoryProducts (ProductRepository $productRepository,Request $request,  PaginatorInterface $paginator, NormalizerInterface $normalizerInterface, EntityManagerInterface $em)
    {
        if($request->query->get('category') && $request->query->get('page') && $request->query->get('sort')){
            $sort = $request->query->get('sort');
            $category =  $this->getCategory($request->query->get('category'));
            $page = (int)$request->query->get('page');


            // 1) return dql query
            if($sort === "default"){
                if($category === "all"){
                    // $dql = $productRepository->findAll();
                    // $dql = "SELECT u FROM App\Entity\Product u";

                    $query = $em->createQuery(
                        'SELECT u FROM App\Entity\Product u'
                    );
                }else{
                    // $dql = $productRepository->findBy(["category"=>$category]);
                    // $dql = "SELECT u FROM App\Entity\Product u WHERE u.category = $category";
                    $query = $em->createQuery(
                        'SELECT u FROM App\Entity\Product u WHERE u.category = :category'
                    )->setParameter('category' , $category);
                }
                
            } else if($sort === "desc" || $sort === "asc"){
                // mettre un parametre pour le ordre ne marche pas
                if($category === "all"){
                    // $dql = $productRepository->findSort($sort);
                    // $dql = "SELECT u FROM App\Entity\Product u ORDER BY u.price $sort";
                    if($sort === "asc"){
                        $query = $em->createQuery(
                            'SELECT u FROM App\Entity\Product u ORDER BY u.price ASC'
                        );
                    }else{
                        $query = $em->createQuery(
                            'SELECT u FROM App\Entity\Product u ORDER BY u.price DESC'
                        );
                    }
                }else{
                    // $dql = $productRepository->findBy(["category"=>$category],['price' => $sort]);
                    // $dql = "SELECT u FROM App\Entity\Product u WHERE u.category = $category ORDER BY u.price $sort";

                    // // ne détecte pas la variable sort
                    // $query = $em->createQuery(
                    //     'SELECT u FROM App\Entity\Product u WHERE u.category = :category ORDER BY u.price  :sort'
                    // )->setParameters(['category' => $category, 'sort' => $sort ]);
                    
                    if($sort === "asc"){
                        $query = $em->createQuery(
                            'SELECT u FROM App\Entity\Product u WHERE u.category = :category ORDER BY u.price ASC'
                        )->setParameter('category' , $category);
                    }else{
                        $query = $em->createQuery(
                            'SELECT u FROM App\Entity\Product u WHERE u.category = :category ORDER BY u.price DESC'
                        )->setParameter('category' , $category);
                    }
                   

                }
                
            }

            $allProducts = count($query->getResult());
            $productsPerPage = 9;
            $pageNumber = ceil ($allProducts/$productsPerPage);

            $articles = $paginator->paginate(
                $query->getResult(), // query
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
