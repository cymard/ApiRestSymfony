<?php

namespace App\Controller;

use App\Entity\order;
use App\Entity\CartProduct;
use App\Entity\OrderProduct;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/orders", name="order", methods={"GET"})
     * Display orders
     */
    public function displayOrders(OrderRepository $repo, SerializerInterface $serializerInterface)
    {
        $dataArray = $repo->findAll();
        $dataJson = $serializerInterface->serialize($dataArray, "json");

        // 2) récuperer tous les comptes
        // 3) les afficher
        if(!empty($dataJson)){
            //requête qui envoie les données vers app react
            $response = new Response();
            $response->setContent($dataJson);
            $response->headers->set('Content-Type', 'application/json');
            
            return $response;
        }
        
    }

    /**
     * @Route("/api/order", name="order", methods={"POST"})
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



}
