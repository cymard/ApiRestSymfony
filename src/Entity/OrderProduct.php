<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
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
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $quantity;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="orderProducts", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\Column(type="float")
     * @Assert\PositiveOrZero
     */
    private $price;

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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

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
        return $orderProduct;
    }
}
