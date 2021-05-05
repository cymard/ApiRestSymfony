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

    // /**
    //  * Transfer cart_product to order_product
    //  */
    // private function transferCartProductToOrderProduct($order, $user){

    //     // récupère tous les cart_product correspondant au user
    //     $cartProductsArray = $user->getCartProduct()->toArray();

    //     foreach($cartProductsArray as $cartProduct){

    //         // récupère les infos du cartProduct qui nous interèsse
    //         $product = $cartProduct->getProduct(); // le product n'a pas toutes les infos
    //         $quantity = $cartProduct->getQuantity();
    //         $price = $product->getPrice();

    //         // baisse du stock
    //         $productStock = $product->getStock();
    //         $product->setStock($productStock - $quantity);

    //         // instanciation d'un nouvel order
    //         $orderProduct = new OrderProduct();
    //         $orderProduct->setQuantity($quantity);
    //         $orderProduct->setProduct($product);
    //         $orderProduct->setPrice($price);

    //         // associer à un order déjà éxistant
    //         $orderProduct->setUserOrder($order);

    //         $this->em->persist($orderProduct);
    //         $this->em->flush();

    //         // supprimer tous les cart_products correspondant
    //         $user->removeCartProduct($cartProduct);

    //         $this->em->remove($cartProduct);
    //         $this->em->flush();
    //     }
   
    // }   

    private $em;
    private $paginator;
    private $normalizerInterface;
    private $serializerInterface;
    private $orderRepo;

    public function __construct(SerializerInterface $serializerInterface, EntityManagerInterface $em, PaginatorInterface $paginator ,NormalizerInterface $normalizerInterface, OrderRepository $orderRepo)
    {
        $this->em = $em;
        $this->paginator = $paginator;
        $this->normalizerInterface = $normalizerInterface;
        $this->orderRepo = $orderRepo;
        $this->serializerInterface = $serializerInterface;
    }

    private function queryPaginator (Request $request, $query, $page, $orderPerPage)
    {
        $articles = $this->paginator->paginate(
            $query, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            $orderPerPage // Nombre de résultats par page
        );

        return $articles;
    }

    private function sendNewResponse ($dataJsonFormat)
    {
        $response = new Response();
        $response->setContent($dataJsonFormat);
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }


    private function getOrderProductsAndQuantity ($orderId)
    {
        $order = $this->orderRepo->findOneBy(["id" => $orderId]);
        $orderProducts = $order->getOrderProducts()->toArray();

        $allProducts = [];
        
        foreach($orderProducts as $orderProduct){
            $quantity = $orderProduct->getQuantity();
            $product = $this->normalizerInterface->normalize($orderProduct->getProduct(), null , ["groups" => "productWithoutComments"]);
            $productInformations = ["product" => $product, "quantity" => $quantity];
            array_push($allProducts, $productInformations);
        }
        return $this->sendNewResponse(json_encode(["data" => $allProducts]));
    }






    /**
     * @Route("/admin/orders", name="all_orders", methods={"GET"})
     * Display orders
     */
    public function displayOrders(Request $request) 
    {
        $pageQuery = (int)$request->query->get('page');
        $searchQuery = $request->query->get('search');

        if($pageQuery && $searchQuery){
            $ordersPerPage = 9;

            if($searchQuery === "default"){
               $queryBuilder =  $this->orderRepo->findAllOrders();
            }else{
                $queryBuilder = $this->orderRepo->findOrderBySearchingEmail($searchQuery);
            }

            $allOrders = count($queryBuilder->getQuery()->getResult());
            $numberOfPages = ceil ($allOrders/$ordersPerPage);
            $paginatedOrders = $this->queryPaginator($request, $queryBuilder->getQuery(), $pageQuery, $ordersPerPage);
            $paginatedOrders = $this->normalizerInterface->normalize($paginatedOrders,null,["groups" => "order"]);

            return $this->sendNewResponse(json_encode(["ordersPerPage" => $ordersPerPage ,"allOrdersNumber" => $allOrders, "totalPageNumber"=>$numberOfPages, "pageContent"=>$paginatedOrders]));
        }else{
            return new JsonResponse(['message' => "pas de query param 'page' ou 'search'."]);
        }
    }


    /**
     * @Route("/api/order", name="create_an_order", methods={"POST"})
     * Create an order
     */
    public function createOrder(Request $request)
    {

        if(empty($request->getContent())){
            return $this->sendNewResponse(json_encode(["message" => "Aucun data envoyé"]));
        }

        $newOrder = $this->serializerInterface->deserialize($request->getContent(), Order::class, "json");

        // vérification du amount
        if($newOrder->getAmount() === 0 ){
            return new JsonResponse(['message' => "Le montant total ne peut être égal à 0"]);
        }

        $user = $this->getUser();
        $newOrder->setUser($user);

        $cartProducts = $user->getCartProduct()->toArray();

        foreach($cartProducts as $cartProduct){

            $orderProduct = OrderProduct::createOrderProductfromCartProduct($cartProduct);
            $orderProduct->setUserOrder($newOrder);

            // baisse du stock
            $product = $orderProduct->getProduct();
            $product->takeFromStock($cartProduct->getQuantity());

            $this->em->persist($orderProduct);
            $this->em->flush();

            // supprimer le cart_product correspondant
            $user->removeCartProduct($cartProduct);

            $this->em->remove($cartProduct);
            $this->em->flush();
        }

        $this->em->persist($newOrder);
        $this->em->flush();

        return $this->sendNewResponse(json_encode(["message" => "nouvelle commande crée"]));
    }


    /**
     * @Route("/admin/order/{orderId}/cart", name="display_order_products", methods={"GET"})
     * display order products for admin
     */
    public function displayOneDetailedOrderForAdmin($orderId)
    {
        return $this->getOrderProductsAndQuantity($orderId);
    }

    /**
     * @Route("/api/order/{orderId}/cart", name="user_display_order_products", methods={"GET"})
     * display order products
     */
    public function displayOneDetailedOrderForUser($orderId)
    {
        $order = $this->orderRepo->findOneBy(["id" => $orderId]);
        $user = $this->getUser();

        if($user === $order->getUser()){
            return $this->getOrderProductsAndQuantity($orderId);
        }else{
            return new JsonResponse(["message" => "L'accès à ces informations n'est pas autorisée."], 401);
        }
    }



    /**
     * @Route("/admin/order/{orderId}", name="admin_display_an_order", methods={"GET"})
     * display an order for admin
     */
    public function displayInformationOrderForAdmin ($orderId)
    {
        $order = $this->orderRepo->findOneBy(["id" => $orderId]);

        $order = $this->normalizerInterface->normalize($order, "json",["groups" => "order"]);

        return $this->sendNewResponse(json_encode(["orderInformations" => $order]));
    }


    /**
     * @Route("/api/order/{orderId}", name="user_display_an_order", methods={"GET"})
     * display an order
     */
    public function displayInformationOrderForUser ($orderId)
    {
        $order = $this->orderRepo->findOneBy(["id" => $orderId]);
        $user = $this->getUser();

        if($user === $order->getUser()){
            $order = $this->normalizerInterface->normalize($order, "json",["groups" => "order"]);

            return $this->sendNewResponse(json_encode(["orderInformations" => $order]));
        }else{
            return new JsonResponse(["message" => "L'accès à ses informations n'est pas autorisé"], 401);
        }
    }



    /**
     * @Route("/api/orders", name="display_all_user_orders", methods={"GET"})
     * display all orders of a user
     */
    public function displayUserOrders(Request $request)
    { 
        $pageQuery = $request->query->get('page');
        $dateQuery = $request->query->get('date');

        if($pageQuery && $dateQuery ){

            $user = $this->getUser();
            $userEmail = $user->getEmail();
            // $ordersArray = $user->getOrders()->toArray();
            $ordersPerPage = 9;


            $dataQueryBuilder = $this->orderRepo->findAllOrdersByDate($userEmail, $dateQuery);

            $allOrders = count($dataQueryBuilder->getQuery()->getResult());
            $pageNumber = ceil($allOrders/9);
            $paginatedOrders = $this->queryPaginator($request, $dataQueryBuilder->getQuery(), $pageQuery, $ordersPerPage);

            $ordersArray = $this->normalizerInterface->normalize($paginatedOrders,null,["groups" => "order"]);

            return $this->sendNewResponse(json_encode(["ordersPerPage" => $ordersPerPage ,"allOrdersNumber" => $allOrders, "totalPageNumber"=>$pageNumber, "pageContent"=>$ordersArray]));
    
        }else{
            return new JsonResponse(['message' => "error"]);
        }
        
    }

    /**
     * @Route("/api/user/order", name="user_order_number", methods={"GET"})
     * Display User orders number
     */
    public function getOrdersQuantityOfOneUser()
    {
        $ordersNumber = $this->getUser()->getOrdersQuantity();

        return $this->sendNewResponse(json_encode(['orderNumber' => $ordersNumber]));
    }

}















// class OrderController extends AbstractController
// {

//     private $em;
//     private $paginator;

//     public function __construct(EntityManagerInterface $em, PaginatorInterface $paginator)
//     {
//         $this->em = $em;
//         $this->paginator = $paginator;
//     }



//     /**
//      * @Route("/admin/orders", name="all_orders", methods={"GET"})
//      * Display orders
//      */
//     public function displayOrders(OrderRepository $repo, NormalizerInterface $normalizerInterface,  PaginatorInterface $paginator, Request $request ) 
//     {
//         if($request->query->get('page') && $request->query->get('search')){
//             $page = (int)$request->query->get('page');
//             $search = $request->query->get('search');

//             $ordersPerPage = 9;

//             if($search === "default"){
//                $queryBuilder =  $repo->findAllOrders();

//             }else{
//                 $queryBuilder = $repo->findOrderBySearchingEmail($search);
//             }

//             $allOrders = count($queryBuilder->getQuery()->getResult());
//             $pageNumber = ceil ($allOrders/$ordersPerPage);

//             $orders = $paginator->paginate(
//                 $queryBuilder->getQuery(), // Requête contenant les données à paginer (ici nos articles)
//                 $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
//                 $ordersPerPage // Nombre de résultats par page
//             );

//             // conversion des objets en tableaux
//             $ordersArray = $normalizerInterface->normalize($orders,null,["groups" => "order"]);
           
//             $response = new JsonResponse();
//             $response->headers->set('Content-Type', 'application/json');

//             // convertion des tableaux en json
//             $allResponses = json_encode(["ordersPerPage" => $ordersPerPage ,"allOrdersNumber" => $allOrders, "totalPageNumber"=>$pageNumber, "pageContent"=>$ordersArray]);

//             $response->setContent($allResponses);

//             return $response;
    
//         }else{
//             $response = new JsonResponse(['message' => "pas de query param 'page' ou 'search'."]);
//             return $response;
//         }
//     }


//     /**
//      * @Route("/api/order", name="create_an_order", methods={"POST"})
//      * Create an order
//      */
//     public function createOrder(Request $request,SerializerInterface $serializerInterface,EntityManagerInterface $em)
//     {
//         $dataJson = $request->getContent();
//         $data = $serializerInterface->deserialize($dataJson, Order::class, "json");

//         // vérification du amount
//         if($data->getAmount() === 0 ){
//             $response = new JsonResponse(['message' => "Le montant total ne peut être égal à 0"]);
//             return $response;
//         }

//         $user = $this->getUser();
//         $data->setUser($user);
//         $this->transferCartProductToOrderProduct($data, $user);

//         $em->persist($data);
//         $em->flush();

//         if(!empty($dataJson)){
//             $response = new Response();
//             $response->setContent($dataJson);
//             $response->headers->set('Content-Type', 'application/json');
            
//             return $response;
//         }
//     }



//     /**
//      * Transfer cart_product to order_product
//      */
//     public function transferCartProductToOrderProduct($order, $user){

//         // récupère tous les cart_product correspondant au user
//         $cartProductsCollection = $user->getCartProduct();
//         $cartProductsArray = $cartProductsCollection->toArray();

//         foreach($cartProductsArray as $cartProduct){

//             // récupère les infos du cartProduct qui nous interèsse
//             $product = $cartProduct->getProduct(); // le product n'a pas toutes les infos
//             $quantity = $cartProduct->getQuantity();
//             $price = $product->getPrice();

//             // baisse du stock
//             $productStock = $product->getStock();
//             $product->setStock($productStock - $quantity);

//             // instanciation d'un nouvel order
//             $orderProduct = new OrderProduct();
//             $orderProduct->setQuantity($quantity);
//             $orderProduct->setProduct($product);
//             $orderProduct->setPrice($price);

//             // associer à un order déjà éxistant
//             $orderProduct->setUserOrder($order);

//             $this->em->persist($orderProduct);
//             $this->em->flush();

//             // supprimer tous les cart_products correspondant
//             $user->removeCartProduct($cartProduct);

//             $this->em->remove($cartProduct);
//             $this->em->flush();
//         }
   
//     }   


//     /**
//      * @Route("/admin/order/{orderId}/cart", name="display_order_products", methods={"GET"})
//      * display order products for admin
//      */
//     public function displayOrderProducts($orderId, OrderRepository $orderRepo,NormalizerInterface $normalizerInterface)
//     {
//         $orderArray = $orderRepo->findBy(["id" => $orderId]);
//         $order = $orderArray[0];

//         $orderProductsCollection = $order->getOrderProducts();
//         $orderProducts = $orderProductsCollection->toArray();

//         $allProducts = [];
        
//         foreach($orderProducts as $orderProduct){
//             $quantity = $orderProduct->getQuantity();
//             $product = $orderProduct->getProduct();
//             $productNormalized = $normalizerInterface->normalize($product, null , ["groups" => "productWithoutComments"]);
//             $productInformations = ["product" => $productNormalized, "quantity" => $quantity];
//             array_push($allProducts, $productInformations);
//         }

//         $response = new Response();
//         $response->setContent(json_encode(["data" => $allProducts]));
//         $response->headers->set('Content-Type', 'application/json');

//         return $response;
//     }


//     /**
//      * @Route("/api/order/{orderId}/cart", name="user_display_order_products", methods={"GET"})
//      * display order products
//      */
//     public function userDisplayOrderProducts($orderId, OrderRepository $orderRepo,NormalizerInterface $normalizerInterface)
//     {
//         $orderArray = $orderRepo->findBy(["id" => $orderId]);
//         $order = $orderArray[0];

//         $orderProductsCollection = $order->getOrderProducts();
//         $orderProducts = $orderProductsCollection->toArray();

//         $allProducts = [];
       
//         foreach($orderProducts as $orderProduct){
//             $quantity = $orderProduct->getQuantity();
//             $product = $orderProduct->getProduct();
//             $productNormalized = $normalizerInterface->normalize($product, null , ["groups" => "productWithoutComments"]);
//             $productInformations = ["product" => $productNormalized, "quantity" => $quantity];
//             array_push($allProducts, $productInformations);
//         }

//         $response = new Response();
//         $response->setContent(json_encode(["data" => $allProducts]));
//         $response->headers->set('Content-Type', 'application/json');

//         return $response;
//     }


//     /**
//      * @Route("/admin/order/{orderId}", name="admin_display_an_order", methods={"GET"})
//      * display an order for admin
//      */
//     public function adminDisplayAnOrder ($orderId,OrderRepository $repoOrder, NormalizerInterface $normalizerInterface)
//     {
//         $order = $repoOrder->findBy(["id" => $orderId]);
//         $orderArray = (array) $order;
//         $order = $orderArray[0];

//         $orderArray = $normalizerInterface->normalize($order, "json",["groups" => "order"]);

//         $response = new Response();
//         $response->setContent(json_encode(["orderInformations" => $orderArray]));
//         $response->headers->set('Content-Type', 'application/json');

//         return $response;
//     }


//     /**
//      * @Route("/api/order/{orderId}", name="user_display_an_order", methods={"GET"})
//      * display an order
//      */
//     public function userDisplayAnOrder ($orderId,OrderRepository $repoOrder, NormalizerInterface $normalizerInterface)
//     {
//         $order = $repoOrder->findBy(["id" => $orderId]);
//         $orderArray = (array) $order;
//         $order = $orderArray[0];

//         $orderArray = $normalizerInterface->normalize($order, "json",["groups" => "order"]);

//         $response = new Response();
//         $response->setContent(json_encode(["orderInformations" => $orderArray]));
//         $response->headers->set('Content-Type', 'application/json');

//         return $response;
//     }



//     /**
//      * @Route("/api/orders", name="display_all_user_orders", methods={"GET"})
//      * display all orders of a user
//      */
//     public function displayUserOrders( NormalizerInterface $normalizerInterface, Request $request, PaginatorInterface $paginator, OrderRepository $orderRepo)
//     {
//         if($request->query->get('page') && $request->query->get('date') ){

//             $page = $request->query->get('page');
//             $date = $request->query->get('date');

//             $user = $this->getUser();
//             $userEmail = $user->getEmail();
//             $ordersCollection = $user->getOrders();
//             $ordersArray = $ordersCollection->toArray();
//             $ordersPerPage = 9;


//             $data = $orderRepo->findAllOrdersByDate($userEmail, $date);

//             $allOrders = count($data->getQuery()->getResult());
//             $pageNumber = ceil($allOrders/9);
    
//             $orders = $paginator->paginate(
//                 $data->getQuery(), // Requête contenant les données à paginer (ici nos articles)
//                 $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
//                 $ordersPerPage // Nombre de résultats par page
//             );

//             $ordersArray = $normalizerInterface->normalize($orders,null,["groups" => "order"]);
           
//             $response = new JsonResponse();
//             $response->headers->set('Content-Type', 'application/json');

//             $allResponses = json_encode(["ordersPerPage" => $ordersPerPage ,"allOrdersNumber" => $allOrders, "totalPageNumber"=>$pageNumber, "pageContent"=>$ordersArray]);

//             $response->setContent($allResponses);

//             return $response;
    
//         }else{
//             return "error";
//         }
        
//     }

//     /**
//      * @Route("/api/user/order", name="user_order_number", methods={"GET"})
//      * Display User orders number
//      */
//     public function userOrderNumber()
//     {
//         $user = $this->getUser();
//         $orderCollection = $user->getOrders();
//         $orderArray = $orderCollection->toArray();
//         $orderNumber = count($orderArray);

//         $response = new Response();
//         $response->headers->set('Content-Type', 'application/json');
//         $response->setContent(json_encode([
//             'orderNumber' => $orderNumber,
//         ]));

//         return $response;
//     }

// }
