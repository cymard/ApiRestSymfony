<?php

namespace App\Controller;

use App\Entity\order;
use App\Entity\CartProduct;
use App\Entity\OrderProduct;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/orders", name="all_orders", methods={"GET"})
     * Display orders
     */
    public function displayOrders(OrderRepository $repo, SerializerInterface $serializerInterface,NormalizerInterface $normalizerInterface,  PaginatorInterface $paginator, Request $request ) 
    {
        if($request->query->get('page') && $request->query->get('search')){



            $page = (int)$request->query->get('page');
            $search = $request->query->get('search');

            $ordersPerPage = 9;

            if($search === "default"){
               $queryBuilder =  $repo->findAllOrders();

            }else{
                $queryBuilder = $repo->findOrderBySearchingEmail($search);
            }

            $allOrders = count($queryBuilder->getQuery()->getResult());
            $pageNumber = ceil ($allOrders/$ordersPerPage);

            $orders = $paginator->paginate(
                $queryBuilder->getQuery(), // Requête contenant les données à paginer (ici nos articles)
                $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                $ordersPerPage // Nombre de résultats par page
    
            );

            
            // convertion des objets en tableaux
            $ordersArray = $normalizerInterface->normalize($orders,null,["groups" => "order"]);
           
            // 3) les afficher
            // responses
            $response = new JsonResponse();
            $response->headers->set('Content-Type', 'application/json');

            // convertion des tableaux en json
            $allResponses = json_encode(["ordersPerPage" => $ordersPerPage ,"allOrdersNumber" => $allOrders, "totalPageNumber"=>$pageNumber, "pageContent"=>$ordersArray]);

            $response->setContent($allResponses);

            return $response;
    
        }else{
            $response = new JsonResponse(['message' => "pas de query param 'page' ou 'search'."]);
            return $response;
        }
        

       
        
    }


    /**
     * @Route("/api/order", name="create_an_order", methods={"POST"})
     * Create an order
     */
    public function createOrder(Request $request,SerializerInterface $serializerInterface,EntityManagerInterface $em, OrderRepository $repo)
    {
        // récuperer les données en json
        $dataJson = $request->getContent();

        // deserialiser les données
        $data = $serializerInterface->deserialize($dataJson, Order::class, "json");
        $user = $this->getUser();
        $data->setUser($user);
        $this->transferCartProductToOrderProduct($data, $user);

        // envoie dans la bdd
        $em->persist($data);
        $em->flush();

        // réponse

        if(!empty($dataJson)){
            //requête qui envoie les données vers app react
            $response = new Response();
            $response->setContent($dataJson);
            $response->headers->set('Content-Type', 'application/json');
            
            return $response;
        }
    }



     /**
     * Transfer cart_product to order_product
     */
    public function transferCartProductToOrderProduct($order, $user){

        // récup tous les cart_product correspondant au user
        $cartProductsCollection = $user->getCartProduct();
        $cartProductsArray = $cartProductsCollection->toArray();

        foreach( $cartProductsArray as $cartProduct){

            // récupère les infos du cartProduct qui nous interesse
            $product = $cartProduct->getProduct(); // le product n'a pas toutes les infos
            $quantity = $cartProduct->getQuantity();

            // instanciation d'un nouvel order
            $orderProduct = new OrderProduct();
            $orderProduct->setQuantity($quantity);
            $orderProduct->setProduct($product);

            // associer à un order deja existant
            $orderProduct->setUserOrder($order);

            // envoie des données en bdd
            $this->em->persist($orderProduct);
            $this->em->flush();

            // supprimer tous les cart_products correspondant
            $user->removeCartProduct($cartProduct);

            // envoie des données en bdd
            $this->em->remove($cartProduct);
            $this->em->flush();
        }
   
    }   


    /**
     * @Route("/admin/order/{orderId}/cart", name="display_order_products", methods={"GET"})
     * display order products
     */
    public function displayOrderProducts($orderId, OrderRepository $orderRepo,NormalizerInterface $normalizerInterface, Request $request,SerializerInterface $serializerInterface,EntityManagerInterface $em, OrderRepository $repo)
    {
        // récuperer la commande correspondante
        $orderArray = $orderRepo->findBy(["id" => $orderId]);
        $order = $orderArray[0];

        // récuperer les produits de la commande
        $orderProductsCollection = $order->getOrderProducts();
        $orderProducts = $orderProductsCollection->toArray();

        $allProducts = [];
        
       
        foreach($orderProducts as $orderProduct){
            $quantity = $orderProduct->getQuantity();

            $product = $orderProduct->getProduct();

            $productNormalized = $normalizerInterface->normalize($product, null , ["groups" => "productWithoutComments"]);

            $productInformations = ["product" => $productNormalized, "quantity" => $quantity];
            array_push($allProducts, $productInformations);
  
        }



        // response
        $response = new Response();
        $response->setContent(json_encode(["data" => $allProducts]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }

    /**
     * @Route("/admin/order/{orderId}", name="display_an_order", methods={"GET"})
     * display an order
     */
    public function displayAnOrder ($orderId,OrderRepository $repoOrder, NormalizerInterface $normalizerInterface)
    {
        $order = $repoOrder->findBy(["id" => $orderId]);
        $orderArray = (array) $order;
        $order = $orderArray[0];

        // transformation de l'objet en tableau
        $orderArray = $normalizerInterface->normalize($order, "json",["groups" => "order"]);

        // response

        $response = new Response();
        $response->setContent(json_encode(["orderInformations" => $orderArray]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }


}
