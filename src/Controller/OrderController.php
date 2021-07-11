<?php

namespace App\Controller;

use App\Entity\order;
use App\Entity\OrderProduct;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class OrderController extends AbstractController
{
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


    private function sendJsonResponse ($dataArray,$status = 200)
    {
        $response = new JsonResponse();
        $response->setContent(json_encode($dataArray));
        $response->setStatusCode($status);
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
        return $this->sendJsonResponse([
            "data" => $allProducts
        ]);
    }






    /**
     * @Route("/admin/orders", name="all_orders", methods={"GET"})
     * Display orders
     */
    public function displayOrders(Request $request) 
    {
        $searchQuery = $request->query->get('search');
        $pageQuery = (int)$request->query->get('page');

        if($pageQuery
        ){
            $ordersPerPage = 9;

            if($searchQuery === ""){
               $queryBuilder =  $this->orderRepo->findAllOrders();
            }else{
                $queryBuilder = $this->orderRepo->findOrderBySearchingEmail($searchQuery);
            }

            $allOrders = count($queryBuilder->getQuery()->getResult());
            $numberOfPages = ceil ($allOrders/$ordersPerPage);
            $paginatedOrders = $this->queryPaginator($request, $queryBuilder->getQuery(), $pageQuery, $ordersPerPage);
            $paginatedOrders = $this->normalizerInterface->normalize($paginatedOrders,null,["groups" => "order"]);

            return $this->sendJsonResponse([
                "ordersPerPage" => $ordersPerPage,
                "allOrdersNumber" => $allOrders, 
                "totalPageNumber"=>$numberOfPages, 
                "pageContent"=>$paginatedOrders]
            );

        }else{
            return $this->sendJsonResponse([
                'message' => "pas de query param 'page' ou 'search'."
            ]);
        }
    }


    /**
     * @Route("/api/order", name="create_an_order", methods={"POST"})
     * Create an order
     */
    public function createOrder(Request $request)
    {

        if(empty($request->getContent())){
            return $this->sendJsonResponse([
                "message" => "Aucun data envoyé"
            ]);
        }

        $newOrder = $this->serializerInterface->deserialize($request->getContent(), Order::class, "json");

        $user = $this->getUser();
        $newOrder->setUser($user);

        // Ne pas faire de commande avec 0 produit dans le panier.
        $cartProducts = $user->getCartProduct()->toArray();
        if(count($cartProducts) <= 0 ){
            return $this->sendJsonResponse([
                'message' => "Le montant total ne peut pas être égal à 0."
            ]);
        }

        foreach($cartProducts as $cartProduct){

            $orderProduct = OrderProduct::createOrderProductfromCartProduct($cartProduct);
            $orderProduct->setOrder($newOrder);

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

        return $this->sendJsonResponse([
            "message" => "nouvelle commande crée"
        ]);
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
            return $this->sendJsonResponse([
                "message" => "L'accès à ces informations n'est pas autorisée."
            ], 401);
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

        return $this->sendJsonResponse([
            "orderInformations" => $order
        ]);
    }

    /**
     * @Route("/admin/order", name="order_delete", methods={"DELETE"})
     * Delete orders
     */
    public function deleteOrder (Request $request)
    {
        $ordersToDelete = (array)json_decode($request->getContent());
        $ordersToDelete = $ordersToDelete["selectedOrders"];

        foreach($ordersToDelete as $id){
            $order = $this->orderRepo->find($id);

            $this->em->remove($order);
            $this->em->flush();
        }

        return $this->sendJsonResponse([
            "message" => "Commande(s) supprimée(s)"    
        ]);
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

            return $this->sendJsonResponse([
                "orderInformations" => $order
            ]);
        }else{
            return $this->sendJsonResponse([
                "message" => "L'accès à ses informations n'est pas autorisé"
            ], 401);
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

            return $this->sendJsonResponse([
                "ordersPerPage" => $ordersPerPage,
                "allOrdersNumber" => $allOrders, 
                "totalPageNumber"=>$pageNumber, 
                "pageContent"=>$ordersArray
            ]);
    
        }else{
            return $this->sendJsonResponse([
                'message' => "error"
            ]);
        }
        
    }

    /**
     * @Route("/api/user/order", name="user_order_number", methods={"GET"})
     * Display User orders number
     */
    public function getOrdersQuantityOfOneUser()
    {
        $ordersNumber = $this->getUser()->getOrdersQuantity();

        return $this->sendJsonResponse([
            'orderNumber' => $ordersNumber
        ]);
    }



}















