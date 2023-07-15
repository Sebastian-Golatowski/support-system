<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message:'Add title')]
    #[Assert\Length(
        min: 3,
        max: 50,
        maxMessage:"your description can`t be longer than {{ limit }}",
        minMessage:"pls. make title slightly longer (at least {{ limit }} characters)",
    )]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message:'Add description for your problem')]
    #[Assert\Length(
        min: 10,
        max: 255,
        maxMessage:"your description can`t be longer than {{ limit }}",
        minMessage:"tell us some more (at least {{ limit }} characters)",
    )]
    private ?string $text = null;

    #[ORM\Column(length: 255)]
    private ?string $priority = null;

    #[ORM\Column]
    private ?bool $status = false;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    #[ORM\ManyToOne]
    private ?User $belongsTo = null;

    public function getId(): ?string
    {
        return $this->id;
    }


    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }


    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getBelongsTo(): ?User
    {
        return $this->belongsTo;
    }

    public function setBelongsTo(?User $belongsTo): static
    {
        $this->belongsTo = $belongsTo;

        return $this;
    }
}
