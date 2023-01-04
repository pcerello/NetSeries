<?php

namespace App\Entity;

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
     * @ORM\Column(name="plot", type="text", length=0, nullable=true)
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
     * @ORM\Column(name="awards", type="text", length=0, nullable=true)
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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Actor", mappedBy="series")
     */
    private $actor = array();

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Genre", mappedBy="series")
     */
    private $genre = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->country = new \Doctrine\Common\Collections\ArrayCollection();
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
        $this->actor = new \Doctrine\Common\Collections\ArrayCollection();
        $this->genre = new \Doctrine\Common\Collections\ArrayCollection();
    }

}
