<?php

namespace App\Entity;

use App\Repository\Channel\VacancyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VacancyRepository::class)
 * @ORM\Table(name="vacancies")
 * @ORM\HasLifecycleCallbacks()
 */
class Vacancy
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $vacancyId;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isSent;

    /**
     * @ORM\Column(type="datetime")
     */
    private $gotAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $sentAt;

    /**
     * @ORM\ManyToOne(targetEntity=Channel::class, inversedBy="vacancies")
     * @ORM\JoinColumn(nullable=false)
     */
    private $channel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVacancyId(): ?int
    {
        return $this->vacancyId;
    }

    public function setVacancyId(int $vacancyId): self
    {
        $this->vacancyId = $vacancyId;

        return $this;
    }

    public function getIsSent(): ?bool
    {
        return $this->isSent;
    }

    public function setIsSent(bool $isSent): self
    {
        $this->isSent = $isSent;

        return $this;
    }

    public function getGotAt(): ?\DateTimeInterface
    {
        return $this->gotAt;
    }

    public function setGotAt(\DateTimeInterface $gotAt): self
    {
        $this->gotAt = $gotAt;

        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function setChannel(?Channel $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->gotAt = new \DateTime();
        $this->isSent = false;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate()
    {
        $this->sentAt = new \DateTime();
    }
}
