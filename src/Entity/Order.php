<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $paymentMethod;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $cardName;

    /**
     * @ORM\Column(type="integer")
     */
    private $cardNumber;

    /**
     * @ORM\Column(type="date")
     */
    private $cardExpirationDate;

    /**
     * @ORM\Column(type="integer")
     */
    private $cryptogram;


    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * @ORM\OneToMany(targetEntity=OrderProduct::class, mappedBy="userOrder")
     */
    private $orderProducts;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdDate;

    public function __construct()
    {
        $this->createdDate = new DateTime();
        // DateTime::createFromFormat('j-M-Y', date("D M d, Y G:i"));
        $this->orderProducts = new ArrayCollection();
    }


    // /**
    //  * @ORM\Column(type="date")
    //  */
    // private $testest;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getCardName(): ?string
    {
        return $this->cardName;
    }

    public function setCardName(string $cardName): self
    {
        $this->cardName = $cardName;

        return $this;
    }

    public function getCardNumber(): ?int
    {
        return $this->cardNumber;
    }

    public function setCardNumber(int $cardNumber): self
    {
        $this->cardNumber = $cardNumber;

        return $this;
    }

    public function getCardExpirationDate(): ?\DateTimeInterface
    {
        return $this->cardExpirationDate;
    }

    public function setCardExpirationDate(\DateTimeInterface $cardExpirationDate): self
    {
        $this->cardExpirationDate = $cardExpirationDate;

        return $this;
    }

    public function getCryptogram(): ?int
    {
        return $this->cryptogram;
    }

    public function setCryptogram(int $cryptogram): self
    {
        $this->cryptogram = $cryptogram;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    // public function getTestest(): ?\DateTimeInterface
    // {
    //     return $this->testest;
    // }

    // public function setTestest(\DateTimeInterface $testest): self
    // {
    //     $this->testest = $testest;

    //     return $this;
    // }

    /**
     * @return Collection|OrderProduct[]
     */
    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function addOrderProduct(OrderProduct $orderProduct): self
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts[] = $orderProduct;
            $orderProduct->setUserOrder($this);
        }

        return $this;
    }

    public function removeOrderProduct(OrderProduct $orderProduct): self
    {
        if ($this->orderProducts->removeElement($orderProduct)) {
            // set the owning side to null (unless already changed)
            if ($orderProduct->getUserOrder() === $this) {
                $orderProduct->setUserOrder(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTimeInterface $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

   
}
