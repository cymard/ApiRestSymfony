<?php

namespace App\Entity;

use App\Repository\CartProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=CartProductRepository::class)
 */
class CartProduct
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"cartProductWithoutRelation"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"cartProductWithoutRelation"})
     */
    private $productId;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"cartProductWithoutRelation"})
     */
    private $quantity;

    /**
     * @ORM\Column(type="integer")
     */
    private $shoppingCartId;



    public function getShoppingCartId(): ?int
    {
        return $this->shoppingCartId;
    }

    public function setShoppingCartId(int $shoppingCartId): self
    {
        $this->shoppingCartId = $shoppingCartId;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): self
    {
        $this->productId = $productId;

        return $this;
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



}
