<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Series
 *
 * @ORM\Table(name="series", uniqueConstraints={@ORM\UniqueConstraint(name="UNIQ_3A10012D85489131", columns={"imdb"})})
 * @ORM\Entity
 */
class Series
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=128, nullable=false)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="plot", type="text", length=500, nullable=true)
     */
    private $plot;

    /**
     * @var string
     *
     * @ORM\Column(name="imdb", type="string", length=128, nullable=false)
     */
    private $imdb;

    /**
     * @var string|null
     *
     * @ORM\Column(name="poster", type="blob", length=0, nullable=true)
     */
    private $poster;

    /**
     * @var string|null
     *
     * @ORM\Column(name="director", type="string", length=128, nullable=true)
     */
    private $director;

    /**
     * @var string|null
     *
     * @ORM\Column(name="youtube_trailer", type="string", length=128, nullable=true)
     */
    private $youtubeTrailer;

    /**
     * @var string|null
     *
     * @ORM\Column(name="awards", type="text", length=200, nullable=true)
     */
    private $awards;

    /**
     * @var int|null
     *
     * @ORM\Column(name="year_start", type="integer", nullable=true)
     */
    private $yearStart;

    /**
     * @var int|null
     *
     * @ORM\Column(name="year_end", type="integer", nullable=true)
     */
    private $yearEnd;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Country", mappedBy="series")
     */
    private $country = array();

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="User", mappedBy="series")
     */
    private $user = array();

    /**
     * @var \Seasons
     *
     * @ORM\OneToMany(targetEntity="Season", mappedBy="series")
     * @ORM\OrderBy({"number" = "ASC"})
     */
    private $seasons;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Actor", mappedBy="series", cascade={"persist"})
     */
    private $actor = array();

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Genre", mappedBy="series")
     */
    private $genre = array();

    /**
     * @var \ExternalRating|null
     *
     * @ORM\OneToOne(targetEntity="ExternalRating", mappedBy="series")
     */
    private $externalRating;

    /**
     * @var \Rating|null
     *
     * @ORM\OneToMany(targetEntity="Rating", mappedBy="series")
     * @ORM\OrderBy({"date" = "DESC"})
     */
    private $ratings = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->country = new \Doctrine\Common\Collections\ArrayCollection();
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
        $this->actor = new \Doctrine\Common\Collections\ArrayCollection();
        $this->genre = new \Doctrine\Common\Collections\ArrayCollection();
        $this->seasons = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPlot(): ?string
    {
        return $this->plot;
    }

    public function setPlot(?string $plot): self
    {
        $this->plot = $plot;

        return $this;
    }

    public function getImdb(): ?string
    {
        return $this->imdb;
    }

    public function setImdb(string $imdb): self
    {
        $this->imdb = $imdb;

        return $this;
    }

    public function getPoster()
    {
        return $this->poster;
    }

    public function setPoster($poster): self
    {
        $this->poster = $poster;

        return $this;
    }

    public function getDirector(): ?string
    {
        return $this->director;
    }

    public function setDirector(?string $director): self
    {
        $this->director = $director;

        return $this;
    }

    public function getYoutubeTrailer(): ?string
    {
        return $this->youtubeTrailer;
    }

    public function setYoutubeTrailer(?string $youtubeTrailer): self
    {
        $this->youtubeTrailer = $youtubeTrailer;

        return $this;
    }

    public function getAwards(): ?string
    {
        return $this->awards;
    }

    public function setAwards(?string $awards): self
    {
        $this->awards = $awards;

        return $this;
    }

    public function getYearStart(): ?int
    {
        return $this->yearStart;
    }

    public function setYearStart(?int $yearStart): self
    {
        $this->yearStart = $yearStart;

        return $this;
    }

    public function getYearEnd(): ?int
    {
        return $this->yearEnd;
    }

    public function setYearEnd(?int $yearEnd): self
    {
        $this->yearEnd = $yearEnd;

        return $this;
    }

    /**
     * @return Collection<int, Country>
     */
    public function getCountry(): Collection
    {
        return $this->country;
    }

    public function addCountry(Country $country): self
    {
        if (!$this->country->contains($country)) {
            $this->country->add($country);
            $country->addSeries($this);
        }

        return $this;
    }

    public function removeCountry(Country $country): self
    {
        if ($this->country->removeElement($country)) {
            $country->removeSeries($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(User $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
            $user->addSeries($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->user->removeElement($user)) {
            $user->removeSeries($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Actor>
     */
    public function getActor(): Collection
    {
        return $this->actor;
    }

    public function addActor(Actor $actor): self
    {
        if (!$this->actor->contains($actor)) {
            $this->actor->add($actor);
            $actor->addSeries($this);
        }

        return $this;
    }

    public function removeActor(Actor $actor): self
    {
        if ($this->actor->removeElement($actor)) {
            $actor->removeSeries($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenre(): Collection
    {
        return $this->genre;
    }

    public function getStringGenre(): string
    {
        $res = "";

        foreach ($this->genre as $g) {
            $res = $res . $g->__toString() . " | ";
        }
        $res = substr($res, 0, -2);
        return $res;
    }

    public function addGenre(Genre $genre): self
    {
        if (!$this->genre->contains($genre)) {
            $this->genre->add($genre);
            $genre->addSeries($this);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): self
    {
        if ($this->genre->removeElement($genre)) {
            $genre->removeSeries($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Season>
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    public function addSeason(Season $season): self
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
            $season->setSeries($this);
        }

        return $this;
    }

    public function removeSeason(Season $season): self
    {
        if ($this->seasons->removeElement($season)) {
            // set the owning side to null (unless already changed)
            if ($season->getSeries() === $this) {
                $season->setSeries(null);
            }
        }

        return $this;
    }

    public function getExternalRating(): ?ExternalRating
    {
        return $this->externalRating;
    }

    public function setExternalRating(?ExternalRating $externalRating): self
    {
        $this->externalRating = $externalRating;

        return $this;
    }

    public function __toString()
    {
        return $this->title;
    }

    /**
     * Cette fonction permet de calculer la note moyenne d'une série en utilisant les notes associées à cette série
     *
     * @return float La note moyenne arrondie à 0.5 près, ou null si aucune note n'a été donnée
     */
    public function getAverageRating()
    {
        // Initialisation des variables pour stocker la somme des notes et le nombre de notes
        $averageRating = 0;
        $count = 0;
        $total = 0;

        // Parcours des notes associées à la série
        foreach ($this->getRatings() as $rating) {
            if ($rating->isEstModere() && !$rating->getUser()->isEstSuspendu()) {
                // Incrémente la somme total par la nouvelle note
                $total += $rating->getValue();
                // Incrémentation du nombre de notes total
                $count++;
            }
        }

        // Si au moins une note a été donnée
        if ($count > 0) {
            // Calcul de la moyenne
            $averageRating = $total / $count;
        } else {
            // Si aucune note n'a été donnée, la note moyenne est null
            $averageRating = null;
        }

        //Mais la moyenne sur 5
        $averageRating = $averageRating / 2;
        // Arrondi à 0.5 près
        $averageRating = round($averageRating * 2) / 2;

        return $averageRating;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatingsModerate()
    {
        /** @var \Rating */
        $valueModerate = new ArrayCollection();
        foreach ($this->getRatings() as $rating) {
            if ($rating->isEstModere() && !$rating->getUser()->isEstSuspendu()) {
                $valueModerate->add($rating);
            }
        }
        return $valueModerate;
    }

    public function addRating(Rating $rating): self
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setSeries($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): self
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getSeries() === $this) {
                $rating->setSeries(null);
            }
        }

        return $this;
    }

    /**
     * Méthode premettant d'avoir de dire si un utilisateur qui suit une séries il a terminé, pas fini ou pas commencé
     * @param User $user un utilisateur
     * @return string Terminé, En cours ou Non commencé
     */
    public function followedSeries(User $user)
    {
        if ($this->getFirstEpisode() == null) {
            return "Non commencée";
        }
        $firstEpisode = $this->getFirstEpisode();
        $lastEpisode = $this->getLastEpisode();
        if ($lastEpisode->getUser()->contains($user)) {
            return "Terminée";
        } elseif ($firstEpisode->getUser()->contains($user)) {
            return "En cours";
        }
        return "Non commencée";
    }

    public function getFirstEpisode(): ?Episode
    {
        if ($this->seasons->isEmpty()) {
            return null;
        }
        $firstSeason = $this->seasons->first();
        $firstEpisode = $firstSeason->getEpisodes()->first();
        return $firstEpisode;
    }

    public function getLastEpisode(): ?Episode
    {
        $lastSeason = $this->seasons->last();
        $lastEpisode = $lastSeason->getEpisodes()->last();
        return $lastEpisode;
    }
}
