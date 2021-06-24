<?php

namespace App\Controller;

use App\Entity\CartProduct;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CartProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CartProductController extends AbstractController
{

    private $em;
    private $cartProductRepository;
    private $productRepository;
    private $normalizerInterface;

    public function __construct(NormalizerInterface $normalizerInterface, ProductRepository $productRepository, EntityManagerInterface $em, CartProductRepository $cartProductRepository)
    {
        $this->em = $em;
        $this->cartProductRepository = $cartProductRepository;
        $this->productRepository = $productRepository;
        $this->normalizerInterface = $normalizerInterface;
    }

    private function sendJsonResponse ($dataArray,$status = 200)
    {
        $response = new JsonResponse();

        $response->setContent(json_encode($dataArray));
        
        $response->setStatusCode($status);
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
            return $this->sendJsonResponse([
                "message" => "impossible d'ajouter le produit au panier car le produit n'est plus en stock."
            ]);
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

        $cartProductArray = $this->normalizerInterface->normalize($cartProduct, null, ["groups" => "cartProductWithoutRelation"]);

        return $this->sendJsonResponse($cartProductArray);
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

        return $this->sendJsonResponse([
            "allProducts" => $allProductsInformations,
            "totalPrice" => $totalPrice, 
            "totalArticles" => $totalArticles
        ]);
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

            return $this->sendJsonResponse([
                "message" => "Le produit a été supprimé."
            ]);

        }else if($productOfCartProduct->getStock() < $data["quantity"] ){

            // Ne pas changer la quantité
            return $this->sendJsonResponse([
                "erreur" => "La quantité demandée est supérieure au stock du produit",
                "number" => $cartProduct->getQuantity()
            ]);

        }else{

            // changement de la quantité 
            $cartProduct->setQuantity($data["quantity"] );

            $this->em->flush($cartProduct);
            return $this->sendJsonResponse([
                "message" => "La quantité a été modifiée."
            ]);
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

        return $this->sendJsonResponse([
            "message" => "Produit supprimé du panier."
        ]);
    }

}


