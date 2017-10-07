<?php

/*
 * This file is part of the Incipio package.
 *
 * (c) Florian Lefevre
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mgate\PubliBundle\Manager;

use Doctrine\ORM\EntityManager;
use PDO;

/**
 * DAO to access Siaje tables
 */
class SiajeRepository
{

    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }


    /**
     * Returns the $index item of the siaje_etudes table
     *
     * @param int $index
     * @return \stdClass
     */
    public function getEtudeByIndex(int $index)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM siaje_etudes LIMIT :index,1 ');
        $stmt->bindValue('index', $index, PDO::PARAM_INT);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    public function getEtudeById(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_etudes` WHERE id_etude = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    public function getEntrepriseById(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_entreprises` WHERE id_ent = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    /**
     * Give an employee id, get the associated entreprise
     *
     * @param int $id
     * @return \stdClass
     */
    public function getEntrepriseByContact(int $id)
    {
        $contact = $this->getContactById($id);

        if (!$contact) {
            return null;
        }

        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_entreprises` WHERE id_ent = :id');
        $stmt->bindParam(':id', $contact->id_ent);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    public function getContactById(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_contacts` WHERE id_contact = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getContactsByEntreprise(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_contacts` WHERE id_ent = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $res = $stmt->fetchAll(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    public function getPhasesByEtude(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_phases` WHERE id_etude = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return [];
        }
        $res = $stmt->fetchAll(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    public function getJehsByEtude(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_jehs` WHERE id_etude = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return [];
        }
        $res = $stmt->fetchAll(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    public function getIntervenantsByEtude(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_intervenants` WHERE id_etude = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return [];
        }
        $res = $stmt->fetchAll(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    public function getEtudiantById(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_etudiants` WHERE id_etudiant = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    public function getIntervenantById(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_intervenants` WHERE id_inter = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }

    /**
     * @param int $id id of intervenant. Used in a first query to return the etudiant.
     * @return \stdClass|null
     */
    public function getEtudiantByIntervenant(int $id)
    {
        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_intervenants` WHERE id_inter = :id');
        $stmt->bindParam(':id', $id);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $inter = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt->closeCursor();

        $stmt = $this->em->getConnection()->prepare('SELECT * FROM `siaje_etudiants` WHERE id_etudiant = :id');
        $stmt->bindParam(':id', $inter->id_etudiant);
        $querySuccess = $stmt->execute();
        if (!$querySuccess) {
            return null;
        }
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $res;
    }
}
