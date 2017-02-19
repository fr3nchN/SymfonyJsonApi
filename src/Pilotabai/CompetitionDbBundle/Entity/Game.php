<?php

namespace Pilotabai\CompetitionDbBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Pilotabai\CompetitionDbBundle\Annotation\Link;

/**
 * Game
 *
 * @ORM\Table(name="game")
 * @ORM\Entity(repositoryClass="Pilotabai\CompetitionDbBundle\Repository\GameRepository")
 * @UniqueEntity(
 *     fields = {"category", "rencontre"},
 *     message = "This game is already saved",
 *     errorPath = "NA"
 * )
 * @Link(
 *  "self",
 *  route = "api_games_show",
 *  params = { "id": "object.getId()" }
 * )
 * @Link(
 *     "category",
 *     route="api_categories_show",
 *     params={"id": "object.getCategoryId()"}
 * )
 * @Serializer\ExclusionPolicy("all")
 */
class Game
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
     * @var int
     *
     * @ORM\Column(name="rencontre", type="integer")
     * @Assert\NotBlank(message="Please enter a rencontre")
     * @Serializer\Expose()
     */
    private $rencontre;

    /**
     * @var string
     *
     * @ORM\Column(name="phase", type="string", length=255)
     * @Serializer\Expose()
     */
    private $phase;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="games")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank(message="Please enter a category")
     * @Serializer\Expose()
     */
    private $category;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getRencontre()
    {
        return $this->rencontre;
    }

    /**
     * @param int $rencontre
     */
    public function setRencontre($rencontre)
    {
        $this->rencontre = $rencontre;
    }

    /**
     * @return string
     */
    public function getPhase()
    {
        return $this->phase;
    }

    /**
     * @param string $phase
     */
    public function setPhase($phase)
    {
        $this->phase = $phase;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function __toString()
    {
        return '{ Game: 
        [id: '.$this->id.'],
        [rencontre: '.$this->rencontre.'],
        [phase: '.$this->phase.'] }';
    }

    public function getCategoryId() {
        return $this->category->getId();
    }

//    /**
//     * @Serializer\VirtualProperty()
//     * @Serializer\SerializedName("category")
//     */
//    public function getCategoryVal() {
//        return array(
//            "id" => $this->category->getId(),
//            "website" => $this->category->getWebsite(),
//            "competitionValue" => $this->category->getCompetitionValue(),
//            "specialityValue" => $this->category->getSpecialityValue(),
//            "levelValue" => $this->category->getLevelValue(),
//        );
//    }
}
