<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\OrderProductRepository;

/**
 * @ORM\Entity(repositoryClass=OrderProductRepository::class)
 */
class OrderProduct
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"OrderProductWithoutRelation"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     * @Groups({"OrderProductWithoutRelation"})
     */
    private $quantity;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="orderProducts", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $order;

    /**
     * @ORM\Column(type="float")
     * @Assert\PositiveOrZero
     * @Groups({"OrderProductWithoutRelation"})
     */
    private $price;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $product;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"OrderProductWithoutRelation"})
     */
    private $image;

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }


    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    static function createOrderProductfromCartProduct(CartProduct $cartProduct) {
        $orderProduct = new OrderProduct();

        $orderProduct->setQuantity($cartProduct->getQuantity());
        $product = $cartProduct->getProduct();

        $orderProduct->setProduct($product);
        $orderProduct->setPrice($product->getPrice());
        $orderProduct->setImage($product->getImage());

        // baisse du stock du produit
        $product->takeFromStock($cartProduct->getQuantity());

        return $orderProduct;
    }
}


