<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ShoppingCartRepository;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\ProductRepository;

class ShoppingCartController extends AbstractController
{
    /**
     * @Route("/shopping/cart", name="shopping_cart")
     */
    public function index(): Response
    {
        return $this->render('shopping_cart/index.html.twig', [
            'controller_name' => 'ShoppingCartController',
        ]);
    }

    /**
     * @Route("/api/cart/products", name="diplay_cart_products", methods={"GET"})
     */
    public function getShoppingCartProducts(ShoppingCartRepository $repo, ProductRepository $productRepo)
    {
        // recup données
        $data = $repo->findAll();
        
        $totalPrice = 0;
        $allProducts = [];
        $totalArticles = 0;

        foreach($data as $product){

            $productInformations = $productRepo->findBy(["id" => $product->getId()]);
            

            // App\Entity\Product {#1037
            //     -id: 54
            //     -price: 44.0
            //     -image: null
            //     -description: "description"
            //     -name: "carte micro sd"
            //     -category: "informatique/high-tech"
            //     -stock: 0
            //     -comments: Doctrine\ORM\PersistentCollection {#1100}
            //   }

            $productData = [
                "title" => $productInformations[0]->getName(), 
                "price" => $productInformations[0]->getPrice(),
                "image" => $productInformations[0]->getImage(),
                "quantity" => $product->getQuantity(), 
                "totalPrice" => $product->getQuantity() * $productInformations[0]->getPrice()
            ];

            array_push($allProducts, $productData);

            $totalPrice += $productData["totalPrice"];
            $totalArticles += $productData["quantity"];

        }

        $json = json_encode(["allProducts" => $allProducts,"totalPrice" => $totalPrice, "totalArticles" => $totalArticles]);

        // La réponse en json
        $response = new Response();
        $response->setContent($json);
        $response->headers->set('Content-type','application/json');
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }

    /**
     * @Route("/api/cart/product/{}", name="add_product_cart", methods={"POST"})
     */
    public function addProductToCart(){

    }
    
}
