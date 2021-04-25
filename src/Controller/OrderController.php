<?php

namespace App\Controller;

use App\Entity\order;
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
    public function displayOrders(OrderRepository $repo, NormalizerInterface $normalizerInterface,  PaginatorInterface $paginator, Request $request ) 
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

            // conversion des objets en tableaux
            $ordersArray = $normalizerInterface->normalize($orders,null,["groups" => "order"]);
           
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
    public function createOrder(Request $request,SerializerInterface $serializerInterface,EntityManagerInterface $em)
    {
        $dataJson = $request->getContent();
        $data = $serializerInterface->deserialize($dataJson, Order::class, "json");

        // vérification du amount
        if($data->getAmount() === 0 ){
            $response = new JsonResponse(['message' => "Le montant total ne peut être égal à 0"]);
            return $response;
        }

        $user = $this->getUser();
        $data->setUser($user);
        $this->transferCartProductToOrderProduct($data, $user);

        $em->persist($data);
        $em->flush();

        if(!empty($dataJson)){
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

        // récupère tous les cart_product correspondant au user
        $cartProductsCollection = $user->getCartProduct();
        $cartProductsArray = $cartProductsCollection->toArray();

        foreach($cartProductsArray as $cartProduct){

            // récupère les infos du cartProduct qui nous interèsse
            $product = $cartProduct->getProduct(); // le product n'a pas toutes les infos
            $quantity = $cartProduct->getQuantity();
            $price = $product->getPrice();

            // baisse du stock
            $productStock = $product->getStock();
            $product->setStock($productStock - $quantity);

            // instanciation d'un nouvel order
            $orderProduct = new OrderProduct();
            $orderProduct->setQuantity($quantity);
            $orderProduct->setProduct($product);
            $orderProduct->setPrice($price);

            // associer à un order déjà éxistant
            $orderProduct->setUserOrder($order);

            $this->em->persist($orderProduct);
            $this->em->flush();

            // supprimer tous les cart_products correspondant
            $user->removeCartProduct($cartProduct);

            $this->em->remove($cartProduct);
            $this->em->flush();
        }
   
    }   


    /**
     * @Route("/admin/order/{orderId}/cart", name="display_order_products", methods={"GET"})
     * display order products for admin
     */
    public function displayOrderProducts($orderId, OrderRepository $orderRepo,NormalizerInterface $normalizerInterface)
    {
        $orderArray = $orderRepo->findBy(["id" => $orderId]);
        $order = $orderArray[0];

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

        $response = new Response();
        $response->setContent(json_encode(["data" => $allProducts]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }


    /**
     * @Route("/api/order/{orderId}/cart", name="user_display_order_products", methods={"GET"})
     * display order products
     */
    public function userDisplayOrderProducts($orderId, OrderRepository $orderRepo,NormalizerInterface $normalizerInterface)
    {
        $orderArray = $orderRepo->findBy(["id" => $orderId]);
        $order = $orderArray[0];

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

        $response = new Response();
        $response->setContent(json_encode(["data" => $allProducts]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }


    /**
     * @Route("/admin/order/{orderId}", name="admin_display_an_order", methods={"GET"})
     * display an order for admin
     */
    public function adminDisplayAnOrder ($orderId,OrderRepository $repoOrder, NormalizerInterface $normalizerInterface)
    {
        $order = $repoOrder->findBy(["id" => $orderId]);
        $orderArray = (array) $order;
        $order = $orderArray[0];

        $orderArray = $normalizerInterface->normalize($order, "json",["groups" => "order"]);

        $response = new Response();
        $response->setContent(json_encode(["orderInformations" => $orderArray]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }


    /**
     * @Route("/api/order/{orderId}", name="user_display_an_order", methods={"GET"})
     * display an order
     */
    public function userDisplayAnOrder ($orderId,OrderRepository $repoOrder, NormalizerInterface $normalizerInterface)
    {
        $order = $repoOrder->findBy(["id" => $orderId]);
        $orderArray = (array) $order;
        $order = $orderArray[0];

        $orderArray = $normalizerInterface->normalize($order, "json",["groups" => "order"]);

        $response = new Response();
        $response->setContent(json_encode(["orderInformations" => $orderArray]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }



    /**
     * @Route("/api/orders", name="display_all_user_orders", methods={"GET"})
     * display all orders of a user
     */
    public function displayUserOrders( NormalizerInterface $normalizerInterface, Request $request, PaginatorInterface $paginator, OrderRepository $orderRepo)
    {
        if($request->query->get('page') && $request->query->get('date') ){

            $page = $request->query->get('page');
            $date = $request->query->get('date');

            $user = $this->getUser();
            $userEmail = $user->getEmail();
            $ordersCollection = $user->getOrders();
            $ordersArray = $ordersCollection->toArray();
            $ordersPerPage = 9;


            $data = $orderRepo->findAllOrdersByDate($userEmail, $date);

            $allOrders = count($data->getQuery()->getResult());
            $pageNumber = ceil($allOrders/9);
    
            $orders = $paginator->paginate(
                $data->getQuery(), // Requête contenant les données à paginer (ici nos articles)
                $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                $ordersPerPage // Nombre de résultats par page
            );

            $ordersArray = $normalizerInterface->normalize($orders,null,["groups" => "order"]);
           
            $response = new JsonResponse();
            $response->headers->set('Content-Type', 'application/json');

            $allResponses = json_encode(["ordersPerPage" => $ordersPerPage ,"allOrdersNumber" => $allOrders, "totalPageNumber"=>$pageNumber, "pageContent"=>$ordersArray]);

            $response->setContent($allResponses);

            return $response;
    
        }else{
            return "error";
        }
        
    }

    /**
     * @Route("/api/user/order", name="user_order_number", methods={"GET"})
     * Display User orders number
     */
    public function userOrderNumber()
    {
        $user = $this->getUser();
        $orderCollection = $user->getOrders();
        $orderArray = $orderCollection->toArray();
        $orderNumber = count($orderArray);

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'orderNumber' => $orderNumber,
        ]));

        return $response;
    }

}
