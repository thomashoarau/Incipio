<?php

namespace Mgate\PubliBundle\Manager;

use Doctrine\ORM\EntityManager;
use Mgate\PersonneBundle\Entity\Prospect;
use Mgate\SuiviBundle\Entity\Ap;
use Mgate\SuiviBundle\Entity\Cc;
use Mgate\SuiviBundle\Entity\Etude;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This class is the main business logic for the siaje import.
 * It should not create object (no new XXXX() allowed).
 * It calls and orchestrates some lower level methods to import data.
 * The run() method should be the only public method of this class.
 */
class SiajeEtudeImporter extends CsvImporter
{

    /** @var EntityManager $em entity manager */
    private $em;
    /** @var SiajeRepository $siajeRepository DAO to access Siaje tables */
    private $siajeRepository;
    /** @var SiajeObjectManager $siajeObjectManager Set of low level methods to create and persist objects */
    private $siajeObjectManager;
    /** @var LoggerInterface $logger To log the process */
    private $logger;

    public function __construct(EntityManager $entityManager, SiajeRepository $siajeRepository,
                                SiajeObjectManager $siajeObjectManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->siajeRepository = $siajeRepository;
        $this->siajeObjectManager = $siajeObjectManager;
        $this->logger = $logger;
    }


    /**
     * @param UploadedFile $file resources file contzaining data to import
     *
     * @return mixed Process Import.
     */
    public function run(UploadedFile $file)
    {
        /** No use of $file->guessExtension because decompressed Siaje file are seen as null */
        if ($file->getClientOriginalExtension() === 'sql') {

            /** Import the file in database with special options */
//            $sql = file_get_contents($file);
//            $sql = 'SET GLOBAL FOREIGN_KEY_CHECKS=0; SET SESSION sql_mode = \'NO_ZERO_DATE\'; ' . $sql; // Bad for performances, nothing better for the moment
//            $stmt = $this->em->getConnection()->prepare($sql);
//            $b = $stmt->execute();
//            $stmt->closeCursor();
//            $this->logger->debug('Import Siaje database: '.$b);
//
//            if (!$b) {
//                throw new \RuntimeException('L\'import SQL n\'a pas fonctionné correctement.');
//            }
            unset($sql);
            /** Iterate through etudes. */
            $insertedProjects = $this->iterateEtude();

            /** Remove all tables starting with siaje_ */
            $stmt = $this->em->getConnection()->prepare("SELECT 
                                    CONCAT( 'DROP TABLE ', GROUP_CONCAT(table_name) , ';' ) AS statement 
                                    FROM information_schema.tables WHERE table_name LIKE 'siaje_%'");
            $stmt->execute();
            $sql = $stmt->fetch(); // retrieve an object { statement => }
            $stmt->closeCursor();
            // Execute the generated sql query
            $stmt = $this->em->getConnection()->prepare($sql);
            $b = $stmt->execute();
            if (!$b) {
                throw new \RuntimeException('La suppression des tables Siaje n\'a pas fonctionné correctement.');
            }
            $stmt->closeCursor();

        } else {
            throw new \RuntimeException('Ce fichier est incorrect. Un fichier .sql est attendu.');
        }

        return ['inserted_projects' => $insertedProjects,];
    }

    private function iterateEtude(): int
    {
        $i = 0;
        while (true) {
            $this->logger->debug('Etude iterator on index ' . $i);
            $etude = $this->siajeRepository->getEtudeByIndex($i);
            if (!$etude) {
                return $i;
            }
            $prospect = $this->importEntreprise($etude); // import the prospect of an etude and its employees.
            $this->importEtude($etude, $prospect);
//        $this->importFactures($etude);
            $i++;
            echo $i;
        }
        return 0; // Makes Phpstorm happy, but definitely useless.
    }


    private function importEntreprise(\stdClass $etude)
    {
        $entreprise = $this->siajeRepository->getEntrepriseByContact($etude->id_client_contact);

        if (!$entreprise) { // Shitty siaje data: create one ghost entreprise with id 0 which will gather all incorrect employees.
            $entreprise = $this->siajeObjectManager->entrepriseStub();
        }

        // Does the entreprise already exists ?
        $prospect = $this->em->getRepository('MgatePersonneBundle:Prospect')
            ->findOneByNom($this->readField($entreprise, 'raison_sociale'));

        if ($prospect) { // merge informations and employees
            return $this->siajeObjectManager->mergeProspect($prospect, $entreprise, $this->siajeRepository->getContactsByEntreprise($entreprise->id_ent));
        }
        return $this->siajeObjectManager->insertProspect($entreprise, $this->siajeRepository->getContactsByEntreprise($entreprise->id_ent));

    }

    private function importEtude(\stdClass $etude, Prospect $prospect)
    {
        // Does the etude already exists ?
        $project = $this->em->getRepository('MgateSuiviBundle:Etude')->findOneByNom($this->readField($etude, 'intitule'));

        if ($project) {
            $project = $this->siajeObjectManager->mergeEtude($project, $etude);
//            $this->mergePhases();
        } else {
            /** Create Etude object */
            $project = $this->siajeObjectManager->insertEtude($etude, $prospect);
            /** Create AP and CC (if possible) */
            $project = $this->importApCc($etude, $prospect);
            /** Create Intervenants, Missions, GroupePhases and Phases */
            $this->importPhases($etude, $project);
            /** Create suiveurs */
            $this->importSuiveurs($etude, $project);
        }
        $this->em->flush();
        $this->em->clear();

        return $etude;

    }

    private function importPhases(\stdClass $etude, Etude $project)
    {
        $phases = $this->siajeRepository->getPhasesByEtude($etude->id_etude);
        $jehs = $this->siajeRepository->getJehsByEtude($etude->id_etude);

        /** @var array $missions ['id_inter' => Mission $mission] */
        $missions = $this->siajeObjectManager->createMissions($jehs);

        $this->siajeObjectManager->insertPhases($project, $phases, $jehs, $missions);
    }

    private function importSuiveurs(\stdClass $etude, Etude $project)
    {
        if ($etude->id_suiveur_principal !== 0) {
            $chefDeProjet = $this->siajeRepository->getEtudiantById($etude->id_suiveur_principal);
            $pm = $this->em->getRepository('MgatePersonneBundle:Personne')->findOneBy([
                'nom' => $this->readField($chefDeProjet, 'nom'),
                'prenom' => $this->readField($chefDeProjet, 'prenom')]);
            if ($pm) {
                $project->setSuiveur($pm);
                $this->siajeObjectManager->mergeEtudiant($pm, $chefDeProjet);
            } else {
                $project->setSuiveur($this->siajeObjectManager->insertEtudiant($chefDeProjet));
            }

        }
        if ($etude->id_responsable_qualite !== 0) {
            $qualite = $this->siajeRepository->getEtudiantById($etude->id_responsable_qualite);
            $rq = $this->em->getRepository('MgatePersonneBundle:Personne')->findOneBy([
                'nom' => $this->readField($qualite, 'nom'),
                'prenom' => $this->readField($qualite, 'prenom')]);
            if ($rq) {
                $project->setSuiveurQualite($rq);
                $this->siajeObjectManager->mergeEtudiant($rq, $qualite);
            } else {
                $project->setSuiveurQualite($this->siajeObjectManager->insertEtudiant($qualite));
            }

        }

    }


    private function importApCc(Etude $etude, Prospect $prospect)
    {
//        $ap = new Ap();
//        $ap->setEtude($etude);
//
//        $constraints = $this->
//
//        $cc = new Cc();
//        $cc->setEtude($etude);
    }

}
