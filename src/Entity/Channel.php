<?php

namespace App\Entity;

use App\Repository\ChannelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ChannelRepository::class)
 * @ORM\Table(name="channels")
 */
class Channel
{
    /**
     * Channels to be inserted to db
     */
    public const CHANNELS = [
        // channelId => category slug
        -1001253713550 => null,
        -1001356873598 => 'it',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", unique=true)
     */
    private $channelId;

    /**
     * @ORM\OneToMany(targetEntity=Vacancy::class, mappedBy="channel", orphanRemoval=true)
     */
    private $vacancies;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $categorySlug;

    public function __construct()
    {
        $this->vacancies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannelId(): ?int
    {
        return $this->channelId;
    }

    public function setChannelId(int $channelId): self
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * @return Collection|Vacancy[]
     */
    public function getVacancies(): Collection
    {
        return $this->vacancies;
    }

    public function addVacancy(Vacancy $vacancy): self
    {
        if (!$this->vacancies->contains($vacancy)) {
            $this->vacancies[] = $vacancy;
            $vacancy->setChannel($this);
        }

        return $this;
    }

    public function removeVacancy(Vacancy $vacancy): self
    {
        if ($this->vacancies->contains($vacancy)) {
            $this->vacancies->removeElement($vacancy);
            // set the owning side to null (unless already changed)
            if ($vacancy->getChannel() === $this) {
                $vacancy->setChannel(null);
            }
        }

        return $this;
    }

    public function getCategorySlug(): ?string
    {
        return $this->categorySlug;
    }

    public function setCategorySlug(?string $categorySlug): self
    {
        $this->categorySlug = $categorySlug;

        return $this;
    }
}
