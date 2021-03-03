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

class ShoppingCartController extends AbstractController
{

    /**
     * @Route("/api/cart/product/{id}", name="add_product_cart", methods={"POST"})
     * add a product to cartProduct
     */
    public function addProductToCart($id,Request $request, UserRepository $userRepository, EntityManagerInterface $em, SerializerInterface $serializerInterface)
    {
        $dataJson = $request->getContent();

        // je recois l'id du user au lieu de l'id du shoppingCart
        $dataStdClass = json_decode($dataJson); // class standard

        // conversion sous forme de tableau pour avoir accès aux données
        $dataArray = (array) $dataStdClass; 

        // trouver le user correspondant
        $email = $dataArray["email"];
        $emailOfUser = $userRepository->findBy(["email" => $email]);
        $user = $emailOfUser[0];

        // trouver le shoppingCart correspondant
        $shoppingCart = $user->getShoppingCart();
        $shoppingCartId = $shoppingCart->getId();
      

        // envoyer le produit dans le cartProduct
  
        $cartProduct = new CartProduct();
        //  $productId
        $cartProduct->setProductId($id);
        //  $quantity
        $cartProduct->setQuantity($dataArray["quantity"]);
        //  $shoppingCartId
        $cartProduct->setShoppingCartId($shoppingCartId);

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
     * @Route("/api/cart/products", name="display_cart_products", methods={"POST"})
     * Display content of a shoppingCart
     * email
     */
    public function getShoppingCartProducts(Request $request, UserRepository $userRepo, CartProductRepository $cartProductRepository ,ProductRepository $productRepo)
    {
        $dataJson = $request->getContent();

        // recuperer le nom du user
        $dataStdClass = json_decode($dataJson);
        $dataArray = (array) $dataStdClass;
        $userEmail = $dataArray["email"];

        // trouver le comtpe du user correspondant
        $userArray = $userRepo->findBy(["email" => $userEmail]);
        $user = $userArray[0];

        // avec le user trouver le shoppingCart correspondant
        $shoppingCart = $user->getShoppingCart();
        $shoppingCartId = $shoppingCart->getId();

        // trouver tous les products avec id du shoppingCart dans CartProduct
        $allProductsArray = $cartProductRepository->findBy(["shoppingCartId" => $shoppingCartId]);

        // les afficher 

        $totalPrice = 0;
        $allProducts = [];
        $totalArticles = 0;

        foreach($allProductsArray as $product){

            $productInformations = $productRepo->findBy(["id" => $product->getProductId()]);
            

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
                "id" => $productInformations[0]->getId(),
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
     * @Route("/api/cart/product/{id}/quantity", name="change_cart_product_quantity", methods={"PUT"})
     * Change quantity of shoppingCart product
     * email,quanity
     */
    public function changeProductQuantity ($id, Request $request, UserRepository $userRepo, CartProductRepository $cartProductRepository ,EntityManagerInterface $em)
    {
        $dataJson = $request->getContent();

        // recuperer le nom du user
        $dataStdClass = json_decode($dataJson);
        $dataArray = (array) $dataStdClass;
        $userEmail = $dataArray["email"];
        $newQuantity = $dataArray["quantity"];

        // trouver le comtpe du user correspondant
        $userArray = $userRepo->findBy(["email" => $userEmail]);
        $user = $userArray[0];

        // avec le user trouver le shoppingCart correspondant
        $shoppingCart = $user->getShoppingCart();
        $shoppingCartId = $shoppingCart->getId();

        // trouver le produit du cartProduct avec $id
        $productArray = $cartProductRepository->findBy(["shoppingCartId" => $shoppingCartId, "productId" => $id]);
        $product = $productArray[0];

        // changer la quantité du produit
        $product->setQuantity($newQuantity);

        // envoyer le nouveau data
        $em->persist($product);
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
    public function deleteProduct ($id, Request $request, UserRepository $userRepo, CartProductRepository $cartProductRepository ,EntityManagerInterface $em)
    {

        $dataJson = $request->getContent();

        // recuperer le nom du user
        $dataStdClass = json_decode($dataJson);
        $dataArray = (array) $dataStdClass;
        $userEmail = $dataArray["email"];

        // trouver le comtpe du user correspondant
        $userArray = $userRepo->findBy(["email" => $userEmail]);
        $user = $userArray[0];

        // avec le user trouver le shoppingCart correspondant
        $shoppingCart = $user->getShoppingCart();
        $shoppingCartId = $shoppingCart->getId();

        // trouver le produit du cartProduct avec $id
        $productArray = $cartProductRepository->findBy(["shoppingCartId" => $shoppingCartId, "productId" => $id]);
        $product = $productArray[0];

        // supprimer le produit du panier
        $em->remove($product);
        $em->flush();

        // retourner la réponse
        $response = $this->json(["message" => "product delete from shopping cart"], 200);
        return $response;
    }

}
