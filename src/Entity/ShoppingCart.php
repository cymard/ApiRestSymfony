<?php

namespace App\Entity;

use App\Repository\ShoppingCartRepository;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass=ShoppingCartRepository::class)
 */
class ShoppingCart
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="shoppingCart", cascade={"persist", "remove"} , orphanRemoval=true)
     */
    private $user;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        // set the owning side of the relation if necessary
        if ($user->getShoppingCart() !== $this) {
            $user->setShoppingCart($this);
        }

        $this->user = $user;

        return $this;
    }




}
