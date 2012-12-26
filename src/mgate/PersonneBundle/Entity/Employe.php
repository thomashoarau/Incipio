<?php

namespace mgate\PersonneBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * mgate\PersonneBundle\Entity\Employe
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="mgate\PersonneBundle\Entity\EmployeRepository")
 */
class Employe
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
     
    /**
     * @ORM\ManyToOne(targetEntity="Prospect", inversedBy="employes", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $prospect;
    
    
    /**
     * @ORM\OneToOne(targetEntity="Personne", inversedBy="employe", cascade={"persist", "merge", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $personne;



    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set prospect
     *
     * @param \mgate\PersonneBundle\Entity\Prospect $prospect
     * @return Employe
     */
    public function setProspect(\mgate\PersonneBundle\Entity\Prospect $prospect)
    {
        $this->prospect = $prospect;
    
        return $this;
    }

    /**
     * Get prospect
     *
     * @return \mgate\PersonneBundle\Entity\Prospect 
     */
    public function getProspect()
    {
        return $this->prospect;
    }

    /**
     * Set personne
     *
     * @param \mgate\PersonneBundle\Entity\Personne $personne
     * @return Employe
     */
    public function setPersonne(\mgate\PersonneBundle\Entity\Personne $personne)
    {
        $this->personne = $personne;
    
        return $this;
    }

    /**
     * Get personne
     *
     * @return \mgate\PersonneBundle\Entity\Personne 
     */
    public function getPersonne()
    {
        return $this->personne;
    }
}