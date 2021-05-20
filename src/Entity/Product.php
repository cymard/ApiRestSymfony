<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpClient\HttpClient;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 */
class Product
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"productWithoutComments"})
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @Assert\Length(
     *      min = 1,
     *      max = 50,
     *      minMessage = "Your first name must be at least {{ limit }} characters long",
     *      maxMessage = "Your first name cannot be longer than {{ limit }} characters"
     * )
     * @Groups({"productWithoutComments"})
     */
    private $price;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"productWithoutComments"})
     */
    private $image;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank
     * @Groups({"productWithoutComments"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"productWithoutComments"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Choice(
     *     choices = { "sports/vetements", "livres", "maison", "informatique/high-tech" },
     *     message = "Choisissez une catégorie valide."
     * )
     * @Groups({"productWithoutComments"})
     */
    private $category;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     * @Groups({"productWithoutComments"})
     */
    private $stock;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="product", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"onlyComments"})
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity=CartProduct::class, mappedBy="product")
     */
    private $cartProducts;


    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->cartProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?float
    {
        return round($this->price, 2);
        
    }

    public function setPrice(float $price): self
    {
        $this->price = round($price, 2);

        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }


    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setProduct($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getProduct() === $this) {
                $comment->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CartProduct[]
     */
    public function getCartProducts(): Collection
    {
        return $this->cartProducts;
    }

    public function addCartProduct(CartProduct $cartProduct): self
    {
        if (!$this->cartProducts->contains($cartProduct)) {
            $this->cartProducts[] = $cartProduct;
            $cartProduct->setProduct($this);
        }

        return $this;
    }

    public function removeCartProduct(CartProduct $cartProduct): self
    {
        if ($this->cartProducts->removeElement($cartProduct)) {
            if ($cartProduct->getProduct() === $this) {
                $cartProduct->setProduct(null);
            }
        }

        return $this;
    }




    
    // Méthodes

    public function calculateAverageRates()
    {
        $comments = $this->getComments()->toArray();

        if(count($comments) > 0){
            $allRates = [];
            $sum = 0;
    
            foreach($comments as $comment){
                array_unshift($allRates, $comment->getNote());
                $sum += intval($comment->getNote());
            }

            if($sum === 0){
                return 0;
            }else{
                $average = $sum/count($allRates);
                return $average;
            }
        }else{
            return null;
        }
        
    }
    
    public function replaceValuesByAnotherProduct(Product $otherProduct){
        $this->setName($otherProduct->getName());
        $this->setPrice($otherProduct->getPrice());
        $this->setDescription($otherProduct->getDescription());
        $this->setStock($otherProduct->getStock());
    }

    public function sendImageToImgbbAndReturnUrl($imageBase64)
    {
        $client = HttpClient::create();
        $response = $client->request('POST', 'https://api.imgbb.com/1/upload?expiration=15552000&key=602552f9aeec55ba40e0e73f6ab60d8b', [
            'body' => [
                "image" => $imageBase64
            ]
        ]);

        
        if($response->getStatusCode() === 200){
            $imgbbData = json_decode($response->getContent(), true);
            $this->setImage($imgbbData["data"]["url"]);
            return $imgbbData["data"]["url"];
        }else{
            $this->setImage(null);
            return null;
        }

    }

    public function takeFromStock(int $quantity): self
    {
        $this->stock -= $quantity;

        return $this;
    }


}
