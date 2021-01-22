<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;


class ProductController extends AbstractController
{

    /**
     * @Route("/products", name="products", methods={"GET"})
     * Display the products
     */
    public function getProducts (ProductRepository $productRepository, SerializerInterface $serializerInterface ,Request $request,  PaginatorInterface $paginator)
    {
        // 1) Récuperer les produits en bdd
        $data = $productRepository->findAll();
       
        // les envoyer en réponse
        return $this->json($data);
    }

    /**
     * @Route("product/{id}", name="product_get", methods={"GET"})
     * Display one product
     */
    public function getProduct (Product $product, SerializerInterface $serializerInterface)
    {
        // Met la donnée en format json et renvoie une réponse
        $serializeProduct = $serializerInterface->serialize($product, "json");

        $response = new Response();
        $response->setContent($serializeProduct);
        $response->headers->set('Content-type','application/json');
        $response->setStatusCode(Response::HTTP_OK);
        return $response;

    }

    /**
     * @Route("admin/product/{id}", name="product_delete", methods={"DELETE"})
     * Delete a product
     */
    public function deleteProduct (Product $product,EntityManagerInterface $em)
    {
        // gerer : erreur no product
        // 1) recuperer le produit
        // 2) supprimer le produit
        $em->remove($product);
        $em->flush();

        // 3) retourner la reponse (status 200)
        $response = new Response("Product deleted");
        return $response;
    }

    /**
     * @Route("/admin/product/{id}", name="product_put", methods={"PUT"})
     * Modify a product
     */
    public function setProduct (Product $product, Request $request, SerializerInterface $serializerInterface,EntityManagerInterface $em, ValidatorInterface $validator)
    {
        // essaie d'encoder la donnée
        try{
            // 1) recuperer le produit modifié
            $json = $request->getContent();
            $newProduct = $serializerInterface->deserialize($json,Product::class,"json");

            // 2) validation des données reçues
            $errors = $validator->validate($newProduct);

            if (count($errors) > 0) {
                /*
                * Uses a __toString method on the $errors variable which is a
                * ConstraintViolationList object. This gives us a nice string
                * for debugging.
                */
                $errorsString = (string) $errors;

                return new Response($errorsString);
            }

            // 3) recuperer le produit à modifier
            // 4) faire la modification (pour tous les champs)
            $product->setName($newProduct->getName());
            $product->setPrice($newProduct->getPrice());
            $product->setDescription($newProduct->getDescription());
            $product->setImage($newProduct->getImage());

            // 5) Envoyer vers la bdd
            $em->persist($product);
            $em->flush();

            // 6) retourner le produit modifié
            return $this->json($product, 201);
        }catch(NotEncodableValueException $e){
            return $this->json([
                "status" => 400,
                "erreur" => $e->getMessage()
            ]);
        }
        
    }

    /**
     * @Route("/admin/products", name="product_post", methods={"POST"})
     * Create a product
     */
    public function createProduct (Request $request, SerializerInterface $serializerInterface, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        try{
            // 1) recuperer les données du produit
            $json = $request->getContent();
            


            // 2) transformation du json en code
            $data = $serializerInterface->deserialize($json,Product::class,"json");

            // 3) validation des données reçues
            $errors = $validator->validate($data);

            if (count($errors) > 0) {
                /*
                * Uses a __toString method on the $errors variable which is a
                * ConstraintViolationList object. This gives us a nice string
                * for debugging.
                */
                $errorsString = (string) $errors;

                return new Response($errorsString);
            }

            // 4) envoyer les données en bdd
            $em->persist($data);
            $em->flush();

            // 5) retourner le produit créé
            $dataSerialized = $serializerInterface->serialize($data,"json");

            
            $response = new Response();
            $response->headers->set('Content-type','application/json');
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($dataSerialized);
            return $response;
            
        }catch(NotEncodableValueException $e){
            return $this->json([
                "status" => 400,
                "erreur" => $e->getMessage()
            ]);
        }
    }



    /**
     * @Route("/products/all/{page}", name="product_category_all", methods={"GET"})
     * Display the products per page
     */
    public function getAllProducts (ProductRepository $productRepository,Request $request,  PaginatorInterface $paginator, $page)
    {
        // 1) Récuperer les produits en bdd
        $data = $productRepository->findAll();

        $articles = $paginator->paginate(
            $data, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            9 // Nombre de résultats par page
        );

        // les envoyer en réponse
        return $this->json($articles);
    }


    /**
     * @Route("/products/maison/{page}", name="product_category_maison", methods={"GET"})
     * Get the maison category products per page
     */
    public function MaisonProducts (ProductRepository $ProductRepository, Request $request,  PaginatorInterface $paginator, $page) {

        $data = $ProductRepository->findBy(["category"=>"maison"]);

        $articles = $paginator->paginate(
            $data, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            9 // Nombre de résultats par page
        );

        // les envoyer en réponse
        return $this->json($articles);
    }

    /**
     * @Route("/products/informatique/{page}", name="product_category_informatique", methods={"GET"})
     * Get the informatique/high-tech category products per page
     */
    public function InformatiqueProducts (ProductRepository $ProductRepository,Request $request, PaginatorInterface $paginator , $page ) {

        $data = $ProductRepository->findBy(["category"=>"informatique/high-tech"]);

        $articles = $paginator->paginate(
            $data, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            9 // Nombre de résultats par page
        );

        // les envoyer en réponse
        return $this->json($articles);
    }

    /**
     * @Route("/products/sports/{page}", name="product_category_sports", methods={"GET"})
     * Get the sports/vetements category products per page
     */
    public function SportsProducts (ProductRepository $ProductRepository,Request $request, PaginatorInterface $paginator , $page) {

        $data = $ProductRepository->findBy(["category"=>"sports/vetements"]);

        $articles = $paginator->paginate(
            $data, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            9 // Nombre de résultats par page
        );

        // les envoyer en réponse
        return $this->json($articles);
    }

    /**
     * @Route("/products/livres/{page}", name="product_category_livres", methods={"GET"})
     * Get the livres category products per page
     */
    public function LivresProducts (ProductRepository $ProductRepository,Request $request, PaginatorInterface $paginator, $page ) {
        $data = $ProductRepository->findBy(["category"=>"livres"]);

        $articles = $paginator->paginate(
            $data, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', $page), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            9 // Nombre de résultats par page
        );

        // $pageNumber = $articles->getTotalItemCount()/9;

        // dd($articles);

        // les envoyer en réponse
        // { article: $articles, page: $page, count: $count}
        return $this->json($articles) ;

    }


    /**
     * @Route("/products/{category}", name="product_category_number", methods={"GET"})
     * Get the livres category products per page
     */
    public function getProductNumber (ProductRepository $ProductRepository,Request $request, PaginatorInterface $paginator, $category ) {
        if($category === "informatique"){
            $data = $ProductRepository->findBy(["category"=>"informatique/high-tech"]);
            $count = count($data);
            // $pageNumber = /
            return $this->json($count);
        }else if($category === "maison"){
            $data = $ProductRepository->findBy(["category"=>"maison"]);
            $count = count($data);
            // $pageNumber = /
            return $this->json($count);
        }else if($category === "sports"){
            $data = $ProductRepository->findBy(["category"=>"sports/vetements"]);
            $count = count($data);
            // $pageNumber = /
            return $this->json($count);
        }else if($category === "livres"){
            $data = $ProductRepository->findBy(["category"=>"livres"]);
            $count = count($data);
            // $pageNumber = /
            return $this->json($count);
        }else if($category === "all"){
            $data = $ProductRepository->findAll();
            $count = count($data);
            // $pageNumber = /
            return $this->json($count);
        }else {
            return $this->json([
                "status" => 400,
                "erreur" => "Catégorie introuvable"
            ]);
        }
        

    }

}
