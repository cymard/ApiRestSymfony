<?php

namespace App\Controller;

use App\Entity\order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{

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
     * @Route("/admin/order", name="order", methods={"POST"})
     * Create an order
     */
    public function createOrder(Request $request,SerializerInterface $serializerInterface,EntityManagerInterface $em, OrderRepository $repo)
    {
        // récuperer les données en json
        $dataJson = $request->getContent();

        // deserialiser les données
        $data = $serializerInterface->deserialize($dataJson, Order::class, "json");

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
}
