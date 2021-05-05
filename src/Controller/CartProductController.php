<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\CartProduct;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CartProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CartProductController extends AbstractController
{

    private $em;
    private $cartProductRepository;
    private $productRepository;
    private $serializerInterface;

    public function __construct(SerializerInterface $serializerInterface, ProductRepository $productRepository, EntityManagerInterface $em, CartProductRepository $cartProductRepository)
    {
        $this->em = $em;
        $this->cartProductRepository = $cartProductRepository;
        $this->productRepository = $productRepository;
        $this->serializerInterface = $serializerInterface;
    }

    private function sendNewResponse ($dataFormatJson)
    {
        $response = new Response();
        $response->setContent($dataFormatJson);
        $response->headers->set('Content-type','application/json');
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }

    private function addQuantityToCartProduct(CartProduct $cartProduct)
    {
        $newQuantity = $cartProduct->getQuantity() + 1;
        $cartProduct->setQuantity($newQuantity);

        $this->em->flush($cartProduct);

        return $cartProduct;
    }


    /**
     * @Route("/api/cart/product/{id}", name="add_product_cart", methods={"POST"})
     * add a product to cartProduct
     * quantity
     */
    public function addProductToCart($id,Request $request)
    {

        $dataArray = (array)json_decode($request->getContent());
        $user = $this->getUser();
        $product = $this->productRepository->findOneBy(["id" => $id]);

        // vérification des stocks
        if($product->getStock() === 0 ){
            return $this->sendNewResponse(json_encode([
                "message" => "impossible d'ajouter le produit au panier car le produit n'est plus en stock."
            ]));
        }

        $cartProduct = new CartProduct();
        $cartProduct->setUser($user);
        $cartProduct->setProduct($product); 
        $cartProduct->setQuantity($dataArray["quantity"]);

        $isExistingCartProduct = $this->cartProductRepository->findOneBy(["user" => $cartProduct->getUser(), "product" =>$cartProduct->getProduct()]);

        if($isExistingCartProduct != []){
            // augmenter la quantité en +1 du produit
            $this->addQuantityToCartProduct($isExistingCartProduct);
        }else{
            $this->em->persist($cartProduct);
            $this->em->flush();
        }

        $cartProductJson = $this->serializerInterface->serialize($cartProduct, "json", ["groups" => "cartProductWithoutRelation"]);

        return $this->sendNewResponse($cartProductJson);
    }


    /**
     * @Route("/api/cart/products", name="display_cart_products", methods={"GET"})
     * Display content of a shoppingCart
     */
    public function getShoppingCartProducts()
    {
        $user = $this->getUser();

        // trouver tous les products avec id du shoppingCart dans CartProduct
        $allCartProducts = $user->getCartProduct()->toArray();

        $totalPrice = 0;
        $allProductsInformations = [];
        $totalArticles = 0;

        foreach($allCartProducts as $cartProduct){

            $productId = $cartProduct->getProduct();
            $productInformations = $this->productRepository->findOneBy(["id" => $productId]);

            $productData = [
                "id" => $productInformations->getId(),
                "title" => $productInformations->getName(), 
                "price" => $productInformations->getPrice(),
                "image" => $productInformations->getImage(),
                "quantity" => $cartProduct->getQuantity(), 
                "totalPrice" => $cartProduct->getQuantity() * $productInformations->getPrice()
            ];

            array_push($allProductsInformations, $productData);

            $totalPrice += $productData["totalPrice"];
            $totalArticles += $productData["quantity"];

        }

        return $this->sendNewResponse(json_encode([
            "allProducts" => $allProductsInformations,
            "totalPrice" => $totalPrice, 
            "totalArticles" => $totalArticles
        ]));
    }



    /**
     * @Route("/api/cart/product/{id}/quantity", name="change_cart_product_quantity", methods={"PUT"})
     * Change quantity of shoppingCart product
     * quantity
     */
    public function changeCardProductQuantity ($id,Request $request)
    {
        $data = (array)json_decode($request->getContent());
        $data["quantity"];

        $user = $this->getUser();

        $productOfCartProduct = $this->productRepository->findOneBy(["id" => $id]);
        $cartProduct = $this->cartProductRepository->findOneBy(["user" => $user, "product" => $productOfCartProduct]);
    
        if($data["quantity"] == 0){

            // supprimer le cartProduct depuis le User
            $user->removeCartProduct($cartProduct);

            // supprimer le produit du panier
            $this->em->remove($cartProduct);
            $this->em->flush();

            return $this->json([
                "message" => "Le produit a été supprimé."
            ], 200);

        }else if($productOfCartProduct->getStock() < $data["quantity"] ){

            // Ne pas changer la quantité
            return $this->json([
                "erreur" => "La quantité demandée est supérieure au stock du produit",
                "number" => $cartProduct->getQuantity()
            ], 200);

        }else{

            // changement de la quantité 
            $cartProduct->setQuantity($data["quantity"] );

            $this->em->flush($cartProduct);
            return $this->json([
                "message" => "La quantité a été modifiée."
            ], 200);
        }
    }


    /**
     * @Route("/api/cart/product/{id}/delete", name="delete_cart_product_quantity", methods={"DELETE"})
     * Delete a product in CartProduct
     * email
     */
    public function deleteCartProduct ($id)
    {
        $user = $this->getUser();
        $product = $this->productRepository->findOneBy(["id" => $id]);
        $cartProduct = $this->cartProductRepository->findOneBy(["user" => $user, "product" => $product]);
        $user->removeCartProduct($cartProduct);

        $this->em->remove($cartProduct);
        $this->em->flush();

        return $this->json([
            "message" => "Produit supprimé du panier."
        ], 200);
    }

}





















// class CartProductController extends AbstractController
// {

//     private $em;

//     public function __construct(EntityManagerInterface $em)
//     {
//         $this->em = $em;
//     }


//     /**
//      * @Route("/api/cart/product/{id}", name="add_product_cart", methods={"POST"})
//      * add a product to cartProduct
//      * quantity
//      */
//     public function addProductToCart($id, CartProductRepository $cartRepo,Request $request,ProductRepository $productRepo, EntityManagerInterface $em, SerializerInterface $serializerInterface)
//     {
//         $dataJson = $request->getContent();

//         $dataStdClass = json_decode($dataJson); // class standard

//         // conversion sous forme de tableau pour avoir accès aux données
//         $dataArray = (array) $dataStdClass; 

//         $user = $this->getUser();

//         $cartProduct = new CartProduct();
//         $cartProduct->setUser($user);

//         $productArray = $productRepo->findBy(["id" => $id]);
//         $product = $productArray[0];

//         // vérification des stocks
//         if($product->getStock() === 0 ){
//             $response = new Response(json_encode(["message" => "impossible d'ajouter le produit au panier car le produit n'est plus en stock."]));
//             $response->headers->set('Content-type','application/json');
//             $response->setStatusCode(Response::HTTP_OK);
//             return $response;
//         }

//         $cartProduct->setProduct($product); 

//         $cartProduct->setQuantity($dataArray["quantity"]);

//         $existingCartProduct = $cartRepo->findBy(["user" => $cartProduct->getUser(), "product" =>$cartProduct->getProduct()]);

//         // regarder si la ligne existe deja
//         if($existingCartProduct != []){
//             // augmenter la quantité en +1 du produit
//             $this->addQuantityToCartProduct($product->getId());
//         }else{
//             $em->persist($cartProduct);
//             $em->flush();
//         }

//         $cartProductJson = $serializerInterface->serialize($cartProduct, "json", ["groups" => "cartProductWithoutRelation"]);

//         $response = new Response();
//         $response->setContent($cartProductJson);
//         $response->headers->set('Content-type','application/json');
//         $response->setStatusCode(Response::HTTP_OK);
//         return $response;
//     }


//     public function addQuantityToCartProduct($id)
//     {
        
//         $productRepo = $this->getDoctrine()->getRepository(Product::class);
        
//         $cartProductRepository = $this->getDoctrine()->getRepository(CartProduct::class);

        
//         $user = $this->getUser();

//         // trouver le product correspondant à l'id
//         $productArray = $productRepo->findBy(["id" => $id]);
//         $product = $productArray[0];

//         // trouver le cartProduct avec le compte user et le produit
//         $cartProductArray = $cartProductRepository->findBy(["user" => $user, "product" => $product]);
//         $cartProduct = $cartProductArray[0];

//         // quantité actuelle +1
//         $newQuantity = $cartProduct->getQuantity() + 1;

//         // changement de la quantité 
//         $cartProduct->setQuantity($newQuantity);

//         $this->em->persist($cartProduct);
//         $this->em->flush();

//     }


//     /**
//      * @Route("/api/cart/products", name="display_cart_products", methods={"GET"})
//      * Display content of a shoppingCart
//      */
//     public function getShoppingCartProducts(ProductRepository $productRepo)
//     {
//         $user = $this->getUser();

//         // trouver tous les products avec id du shoppingCart dans CartProduct
//         $allProductsCollection = $user->getCartProduct();
//         $allProductsArray = $allProductsCollection->toArray();

//         $totalPrice = 0;
//         $allProducts = [];
//         $totalArticles = 0;

//         foreach($allProductsArray as $product){

//             $productInformationsId = $product->getProduct();
//             $productInformationsArray = $productRepo->findBy(["id" => $productInformationsId]);
//             $productInformations = $productInformationsArray[0];

//             // Un exemple en Bdd
//             // App\Entity\Product {#1037
//             //     -id: 54
//             //     -price: 44.0
//             //     -image: null
//             //     -description: "description"
//             //     -name: "carte micro sd"
//             //     -category: "informatique/high-tech"
//             //     -stock: 0
//             //     -comments: Doctrine\ORM\PersistentCollection {#1100}
//             //   }

//             $productData = [
//                 "id" => $productInformations->getId(),
//                 "title" => $productInformations->getName(), 
//                 "price" => $productInformations->getPrice(),
//                 "image" => $productInformations->getImage(),
//                 "quantity" => $product->getQuantity(), 
//                 "totalPrice" => $product->getQuantity() * $productInformations->getPrice()
//             ];

//             array_push($allProducts, $productData);

//             $totalPrice += $productData["totalPrice"];
//             $totalArticles += $productData["quantity"];

//         }

//         $json = json_encode(["allProducts" => $allProducts,"totalPrice" => $totalPrice, "totalArticles" => $totalArticles]);

//         $response = new Response();
//         $response->setContent($json);
//         $response->headers->set('Content-type','application/json');
//         $response->setStatusCode(Response::HTTP_OK);
//         return $response;
//     }


//      /**
//      * @Route("/api/cart/product/{id}/quantity", name="change_cart_product_quantity", methods={"PUT"})
//      * Change quantity of shoppingCart product
//      */
//     public function changeProductQuantity ($id, Request $request, ProductRepository $productRepo, CartProductRepository $cartProductRepository)
//     {

//         $dataJson = $request->getContent();

//         $dataStdClass = json_decode($dataJson);
//         $dataArray = (array) $dataStdClass;
//         $newQuantity = $dataArray["quantity"];
//         $user = $this->getUser();

//         // trouver le product correspondant à l'id
//         $productArray = $productRepo->findBy(["id" => $id]);
//         $product = $productArray[0];

//         // comparaison du stock avec la quantité demandée
//         $productStock = $product->getStock();
    
//         if($newQuantity == 0){

//             // trouver le cartProduct avec le compte user et le produit
//             $cartProductArray = $cartProductRepository->findBy(["user" => $user, "product" => $product]);
//             $cartProduct = $cartProductArray[0];

//             // supprimer le cartProduct depuis le User
//             $user->removeCartProduct($cartProduct);

//             // supprimer le produit du panier
//             $this->em->remove($cartProduct);
//             $this->em->flush();

//             $response = $this->json(["message" => "product delete from shopping cart"], 200);
//             return $response;

//         }else if($productStock < $newQuantity){

//             // remettre l'ancienne quantité demandée
//             $cartProductArray = $cartProductRepository->findBy(["user" => $user, "product" => $product]);
//             $cartProduct = $cartProductArray[0];

//             $response = $this->json(["erreur" => "La quantité demandée est supérieure au stock du produit", "number" => $cartProduct->getQuantity()], 200);
//             return $response;

//         }else{

//             $cartProductArray = $cartProductRepository->findBy(["user" => $user, "product" => $product]);
//             $cartProduct = $cartProductArray[0];

//             // changement de la quantité 
//             $cartProduct->setQuantity($newQuantity);

//             $this->em->persist($cartProduct);
//             $this->em->flush();

//             $response = $this->json(["message" => "quantity changed"], 200);
//             return $response;
//         }
//     }



    

//     /**
//      * @Route("/api/cart/product/{id}/delete", name="delete_cart_product_quantity", methods={"DELETE"})
//      * Delete a product in CartProduct
//      * email
//      */
//     public function deleteProduct ($id,ProductRepository $productRepo, CartProductRepository $cartProductRepository ,EntityManagerInterface $em)
//     {
//         $user = $this->getUser();

//         $productArray = $productRepo->findBy(["id" => $id]);
//         $product = $productArray[0];

//         $cartProductArray = $cartProductRepository->findBy(["user" => $user, "product" => $product]);
//         $cartProduct = $cartProductArray[0];

//         $user->removeCartProduct($cartProduct);

//         $em->remove($cartProduct);
//         $em->flush();

//         $response = $this->json(["message" => "product delete from shopping cart"], 200);
//         return $response;
//     }

// }
