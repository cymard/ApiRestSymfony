<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CommentRepository;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=CommentRepository::class)
 */
class Comment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"commentWithoutProduct"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"commentWithoutProduct"})
     */
    private $username;

    /**
     * @ORM\Column(type="text")
     * @Groups({"commentWithoutProduct"})
     */
    private $content;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"commentWithoutProduct"})
     */
    private $note;
    
    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"commentWithoutProduct"})
     */
    private $title;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"commentWithoutProduct"})
     */
    protected $date;
    
    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $product;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isReported;

    public function __construct()
    {
        $this->date = new DateTime(); 
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }


    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): self
    {
        $this->note = $note;

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


    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getIsReported(): ?bool
    {
        return $this->isReported;
    }

    public function setIsReported(bool $isReported): self
    {
        $this->isReported = $isReported;

        return $this;
    }


}


