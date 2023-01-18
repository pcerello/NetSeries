<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Rating
 *
 * @ORM\Table(name="rating", indexes={@ORM\Index(name="IDX_D88926225278319C", columns={"series_id"}), @ORM\Index(name="IDX_D8892622A76ED395", columns={"user_id"})})
 * @ORM\Entity
 */
class Rating
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="value", type="integer", nullable=false)
     */
    private $value;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", length=300, nullable=true)
     */
    private $comment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=false)
     */
    private $date;

    /**
     * @var \Series|null
     *
     * @ORM\ManyToOne(targetEntity="Series", inversedBy="ratings")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="series_id", referencedColumnName="id")
     * })
     */
    private $series;

    /**
     * @var \User|null
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="ratings")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var bool
     *
     * @ORM\Column(name="estModere", type="boolean", nullable=false)
     */
    private $estModere = '0';

    public function __construct()
    {
        $this->date = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getSeries(): ?Series
    {
        return $this->series;
    }

    public function setSeries(?Series $series): self
    {
        $this->series = $series;

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

    /**
     * Méthode permettant d'avoir une unité de temps dynamique par rapport à la date
     */
    public function getTime() {
        $now = new DateTime(); // date actuelle
        $interval = $this->date->diff($now); // calcul de la différence entre les deux dates

        if ($interval->y > 0) {
            echo "Commentaire posté il y a " . $interval->y . " an(s).";
        } elseif ($interval->m > 0) {
            echo "Commentaire posté il y a " . $interval->m . " mois.";
        } elseif ($interval->d > 0) {
            echo "Commentaire posté il y a " . $interval->d . " jour(s).";
        } elseif ($interval->h > 0) {
            echo "Commentaire posté il y a " . $interval->h . " heure(s).";
        } elseif ($interval->i > 0) {
            echo "Commentaire posté il y a " . $interval->i . " minute(s).";
        } elseif ($interval->s > 0) {
            echo "Commentaire posté il y a " . $interval->s . " seconde(s).";
        }
    }

    public function isEstModere(): ?bool
    {
        return $this->estModere;
    }

    public function setEstModere(bool $estModere): self
    {
        $this->estModere = $estModere;

        return $this;
    }
}
