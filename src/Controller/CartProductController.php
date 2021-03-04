<?php

namespace App\Controller;

use App\Entity\CartProduct;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\CartProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CartProductController extends AbstractController
{



    /**
     * @Route("/api/cart/product/{id}", name="add_product_cart", methods={"POST"})
     * add a product to cartProduct
     * quantity
     */
    public function addProductToCart($id,Request $request,ProductRepository $productRepo, EntityManagerInterface $em, SerializerInterface $serializerInterface)
    {
        // récuperer le user grace au token
        $dataJson = $request->getContent();

        // je recois l'id du user au lieu de l'id du shoppingCart
        $dataStdClass = json_decode($dataJson); // class standard

        // conversion sous forme de tableau pour avoir accès aux données
        $dataArray = (array) $dataStdClass; 

        $user = $this->getUser();

        // envoyer le produit dans le cartProduct
        $cartProduct = new CartProduct();
        $cartProduct->setUser($user);

        //  $product
        $productArray = $productRepo->findBy(["id" => $id]);
        $product = $productArray[0];
        $cartProduct->setProduct($product); // surement l'entité product au complet
        //  $quantity
        $cartProduct->setQuantity($dataArray["quantity"]);

        $em->persist($cartProduct);
        $em->flush();

       

        // réponse
        $cartProductJson = $serializerInterface->serialize($cartProduct, "json", ["groups" => "cartProductWithoutRelation"]);

        $response = new Response();
        $response->setContent($cartProductJson);
        $response->headers->set('Content-type','application/json');
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }




    /**
     * @Route("/api/cart/products", name="display_cart_products", methods={"GET"})
     * Display content of a shoppingCart
     */
    public function getShoppingCartProducts(ProductRepository $productRepo)
    {
        $user = $this->getUser();

        // trouver tous les products avec id du shoppingCart dans CartProduct
        $allProductsCollection = $user->getCartProduct();
        $allProductsArray = $allProductsCollection->toArray();

        // les afficher 
        $totalPrice = 0;
        $allProducts = [];
        $totalArticles = 0;

        foreach($allProductsArray as $product){

            $productInformationsId = $product->getProduct();
            $productInformationsArray = $productRepo->findBy(["id" => $productInformationsId]);
            $productInformations = $productInformationsArray[0];


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
                "id" => $productInformations->getId(),
                "title" => $productInformations->getName(), 
                "price" => $productInformations->getPrice(),
                "image" => $productInformations->getImage(),
                "quantity" => $product->getQuantity(), 
                "totalPrice" => $product->getQuantity() * $productInformations->getPrice()
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
     * @Route("/api/cart/product/{id}/quantity", name="change_cart_product_quantity", methods={"PUT"})
     * Change quantity of shoppingCart product
     * quanity
     */
    public function changeProductQuantity ($id, Request $request,ProductRepository $productRepo, CartProductRepository $cartProductRepository ,EntityManagerInterface $em)
    {
        $dataJson = $request->getContent();

        // recuperer le nom du user
        $dataStdClass = json_decode($dataJson);
        $dataArray = (array) $dataStdClass;
        $newQuantity = $dataArray["quantity"];
        $user = $this->getUser();

        // trouver le product correspondant à l'id
        $productArray = $productRepo->findBy(["id" => $id]);
        $product = $productArray[0];

        // trouver le cartProduct avec le compte user et le produit
        $cartProductArray = $cartProductRepository->findBy(["user" => $user, "product" => $product]);
        $cartProduct = $cartProductArray[0];

        // changement de la quantité 
        $cartProduct->setQuantity($newQuantity);
        
        // envoyer le nouveau data
        $em->persist($cartProduct);
        $em->flush();

        // retourner la réponse
        $response = $this->json(["message" => "quantity changed"], 200);
        return $response;
    }



    

    /**
     * @Route("/api/cart/product/{id}/delete", name="delete_cart_product_quantity", methods={"DELETE"})
     * Delete a product in CartProduct
     * email
     */
    public function deleteProduct ($id,ProductRepository $productRepo, CartProductRepository $cartProductRepository ,EntityManagerInterface $em)
    {

        $user = $this->getUser();

        // trouver le product correspondant à l'id
        $productArray = $productRepo->findBy(["id" => $id]);
        $product = $productArray[0];

        // trouver le cartProduct avec le compte user et le produit
        $cartProductArray = $cartProductRepository->findBy(["user" => $user, "product" => $product]);
        $cartProduct = $cartProductArray[0];

        // supprimer le cartProduct depuis le User
        $user->removeCartProduct($cartProduct);

        // supprimer le produit du panier
        $em->remove($cartProduct);
        $em->flush();

        // retourner la réponse
        $response = $this->json(["message" => "product delete from shopping cart"], 200);
        return $response;
    }

}
