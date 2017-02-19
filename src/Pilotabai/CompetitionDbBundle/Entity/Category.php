<?php

namespace Pilotabai\CompetitionDbBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Pilotabai\CompetitionDbBundle\Annotation\Link;

/**
 * Category
 *
 * @ORM\Table(name="category")
 * @ORM\Entity(repositoryClass="Pilotabai\CompetitionDbBundle\Repository\CategoryRepository")
 * @UniqueEntity(
 *     fields = {"website", "competitionValue", "specialityValue", "levelValue"},
 *     message = "This category is already saved",
 *     errorPath = "NA"
 * )
 * @Link(
 *  "self",
 *  route = "api_categories_show",
 *  params = { "id": "object.getId()" }
 * )
 * @Link(
 *     "games",
 *     route="api_games_list",
 *     params={"categoryFilter": "object.getId()"}
 * )
 * @Serializer\ExclusionPolicy("all")
 */
class Category
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=255)
     * @Assert\NotBlank(message="Please enter a website")
     * @Serializer\Expose()
     */
    private $website;

    /**
     * @var int
     *
     * @ORM\Column(name="competitionValue", type="integer")
     * @Assert\NotBlank(message="Please enter a competitionValue")
     * @Serializer\Expose()
     */
    private $competitionValue;

    /**
     * @var int
     *
     * @ORM\Column(name="specialityValue", type="integer")
     * @Assert\NotBlank(message="Please enter a specialityValue")
     * @Serializer\Expose()
     */
    private $specialityValue;

    /**
     * @var int
     *
     * @ORM\Column(name="levelValue", type="integer")
     * @Assert\NotBlank(message="Please enter a levelValue")
     * @Serializer\Expose()
     */
    private $levelValue;

    /**
     * @var string
     *
     * @ORM\Column(name="competition", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     */
    private $competition;

    /**
     * @var string
     *
     * @ORM\Column(name="speciality", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     */
    private $speciality;

    /**
     * @var string
     *
     * @ORM\Column(name="level", type="string", length=255, nullable=true)
     * @Serializer\Expose()
     */
    private $level;

    /**
     * @var ArrayCollection|Game[]
     *
     * @ORM\OneToMany(targetEntity="Game", mappedBy="category")
     */
    private $games;

    /**
     * Category constructor.
     * @param ArrayCollection|Game[] $games
     */
    public function __construct()
    {
        $this->games = array();
    }


    /**
     * Set Id
     *
     * @param integer $id
     *
     * @return Category
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set website
     *
     * @param string $website
     *
     * @return Category
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set competitionValue
     *
     * @param integer $competitionValue
     *
     * @return Category
     */
    public function setCompetitionValue($competitionValue)
    {
        $this->competitionValue = $competitionValue;

        return $this;
    }

    /**
     * Get competitionValue
     *
     * @return int
     */
    public function getCompetitionValue()
    {
        return $this->competitionValue;
    }

    /**
     * Set specialityValue
     *
     * @param integer $specialityValue
     *
     * @return Category
     */
    public function setSpecialityValue($specialityValue)
    {
        $this->specialityValue = $specialityValue;

        return $this;
    }

    /**
     * Get specialityValue
     *
     * @return int
     */
    public function getSpecialityValue()
    {
        return $this->specialityValue;
    }

    /**
     * Set levelValue
     *
     * @param integer $levelValue
     *
     * @return Category
     */
    public function setLevelValue($levelValue)
    {
        $this->levelValue = $levelValue;

        return $this;
    }

    /**
     * Get levelValue
     *
     * @return int
     */
    public function getLevelValue()
    {
        return $this->levelValue;
    }

    /**
     * Set competition
     *
     * @param string $competition
     *
     * @return Category
     */
    public function setCompetition($competition)
    {
        $this->competition = $competition;

        return $this;
    }

    /**
     * Get competition
     *
     * @return string
     */
    public function getCompetition()
    {
        return $this->competition;
    }

    /**
     * Set speciality
     *
     * @param string $speciality
     *
     * @return Category
     */
    public function setSpeciality($speciality)
    {
        $this->speciality = $speciality;

        return $this;
    }

    /**
     * Get speciality
     *
     * @return string
     */
    public function getSpeciality()
    {
        return $this->speciality;
    }

    /**
     * Set level
     *
     * @param string $level
     *
     * @return Category
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '{ category: 
        [id: '.$this->id.'], 
        [website: '.$this->website.'], 
        [competition: '.$this->competitionValue.'],
        [speciality: '.$this->specialityValue.'],
        [level: '.$this->levelValue.'] }';
    }

//    /**
//     * @Serializer\VirtualProperty()
//     * @Serializer\SerializedName("games")
//     */
//    public function getCategoryId() {
//        $gameArray = array();
//        foreach ($this->games as $game) {
//            $gameArray[] = array(
//                "id" => $game->getId(),
//                "rencontre" => $game->getRencontre()
//            );
//        }
//        return $gameArray;
//    }

    public function getGamesId() {
        $gamesIdArray = array();
        foreach ($this->games as $game) {
            $gamesIdArray[] = $game->getId();
        }
        return $gamesIdArray;
    }
}

