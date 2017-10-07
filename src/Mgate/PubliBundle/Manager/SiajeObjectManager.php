<?php


namespace Mgate\PubliBundle\Manager;

use Doctrine\ORM\EntityManager;
use Mgate\PersonneBundle\Entity\Employe;
use Mgate\PersonneBundle\Entity\Filiere;
use Mgate\PersonneBundle\Entity\Membre;
use Mgate\PersonneBundle\Entity\Personne;
use Mgate\PersonneBundle\Entity\Prospect;
use Mgate\SuiviBundle\Entity\Etude;
use Mgate\SuiviBundle\Entity\GroupePhases;
use Mgate\SuiviBundle\Entity\Mission;
use Mgate\SuiviBundle\Entity\Phase;
use Mgate\SuiviBundle\Entity\RepartitionJEH;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Manage object merge and insertion
 */
class SiajeObjectManager extends CsvImporter
{

    const SIAJE_ETUDE_STATES = ['contact' => 1,
        'devis_envoye' => 1,
        'realisation' => 2,
        'pv_signe' => 2,
        'standby' => 3,
        'cloturee' => 4,
        'avortee' => 5
    ];

    const SIAJE_ENTITES = [
        'association' => 2,
        'entrepreneur' => 1,
        'groupe_inter' => 5,
        'groupe_nat' => 5,
        'je' => 8,
        'org_public' => 7,
        'pme' => 4,
        'tpe' => 3,
    ];

    const SIAJE_SOURCE_PROSPECTION = [
        'administration' => 3,
        'ancien_client' => 8,
        'ancien_eleve' => 6,
        'ancien_junior' => 5,
        'cnje' => 1,
        'etudiant' => 6,
        'prospe_directe' => 9,
        'salon' => 9,
        'spontane' => 7,
        'tiers' => 11,
    ];

    private $em;
    private $siajeRepository;
    private $validator;
    private $logger;

    private $filiere; // Filiere set by default to every imported students (so as to have valid entities)


    public function __construct(EntityManager $entityManager, SiajeRepository $siajeRepository, ValidatorInterface $validator, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->siajeRepository = $siajeRepository;
        $this->validator = $validator;
        $this->logger = $logger;
    }


    public function mergeProspect(Prospect $prospect, \stdClass $entreprise, array $contacts)
    {
        $this->logger->debug('Merge Prospect ' . $prospect->getNom() . ' (' . $prospect->getId() . ') with ' .
            $entreprise->raison_sociale . ' and its ' . count($contacts) . ' employees');
        if (empty($prospect->getAdresse()) && !empty($entreprise->adresse)) {
            $prospect->setAdresse($entreprise->adresse);
        }
        if (empty($prospect->getCodePostal()) && !empty($entreprise->codepostal)) {
            $prospect->setCodePostal($entreprise->codepostal);
        }
        if (empty($prospect->getVille()) && !empty($entreprise->ville)) {
            $prospect->setVille($entreprise->ville);
        }
        if (empty($prospect->getPays()) && !empty($entreprise->pays)) {
            $prospect->setPays($entreprise->pays);
        }
        if (empty($prospect->getEntite()) && !empty($entreprise->statut)) {
            $prospect->setEntite(self::SIAJE_ENTITES[$entreprise->statut]);
        }

        $constraints = $this->validator->validate($prospect);
        $this->stopOnInvalidObject('Merge prospect', $constraints);
        $this->em->flush();
        return $prospect;
    }

    /**
     * @param \stdClass $entreprise
     * @param array $contacts
     * @return Prospect
     */
    public function insertProspect(\stdClass $entreprise, array $contacts)
    {
        $this->logger->debug('Insert Prospect ' . $entreprise->raison_sociale . ' and its ' .
            count($contacts) . ' employees');
        $prospect = new Prospect();
        if (!empty($this->readField($entreprise, 'raison_sociale'))) {
            $prospect->setNom($this->readField($entreprise, 'raison_sociale'));
        } else {
            $prospect->setNom('Prospect inconnu ' . rand());
        }
        $prospect->setEntite(self::SIAJE_ENTITES[$entreprise->statut]);
        $prospect->setAdresse($this->readField($entreprise, 'adresse'));
        $prospect->setCodePostal($entreprise->codepostal);
        $prospect->setVille($entreprise->ville);
        $prospect->setPays($entreprise->pays);

        $constraints = $this->validator->validate($prospect);
        $this->stopOnInvalidObject('Insert Prospect', $constraints);
        $this->em->persist($prospect);
        /** Don't flush Prospect now or it will stop the employe cascade process and creates an error */


        foreach ($contacts as $c) {
            $pe = new Personne();
            $pe->setSexe(($c->titre === 'Mme' ? 'Madame' : 'Monsieur'));
            $pe->setPrenom($this->readField($c, 'prenom'));
            $pe->setNom($this->readField($c, 'nom'));
            $pe->setFix($c->fixe);
            $pe->setMobile($c->mobile);
            $pe->setEmail($c->email);
            $pe->setEmailEstValide(true);
            $pe->setEstAbonneNewsletter(false);
            $constraints = $this->validator->validate($pe);
            $this->stopOnInvalidObject('Persit Personne in insert', $constraints);


            $emp = new Employe();
            $emp->setProspect($prospect);
            $prospect->addEmploye($emp);
            $emp->setPoste($this->readField($c, 'poste'));
            $emp->setPersonne($pe);

            $constraints = $this->validator->validate($emp);
            $this->stopOnInvalidObject('Employee insert', $constraints);

            $this->em->persist($pe);
            $this->logger->debug('Persist ' . $emp->getPersonne()->getNomFormel());
        }
        $this->em->flush();

        return $prospect;
    }

    /**
     *
     */
    public function entrepriseStub()
    {
        $e = new \stdClass();
        $e->raison_sociale = 'Entreprise mal renseignée';
        $e->adresse = 'Entreprise incorrecte. squellette de remplacement';
        $e->statut = 'entrepreneur';
        $e->id_ent = 0;
        return $e;
    }


    public function mergeEtude(Etude $project, \stdClass $etude): Etude
    {

        return $project;
    }

    public function insertEtude($etude, Prospect $prospect): Etude
    {
        $e = new Etude();
        $dateSignatureCe = $this->stringToDateTime($etude->date_signature_ce);
        if ($dateSignatureCe) {
            $e->setMandat($dateSignatureCe->format('Y'));
        } else { // date_ajout always set
            $e->setMandat($this->stringToDateTime($etude->date_ajout)->format('Y'));
        }
        $e->setNom($this->readField($etude, 'intitule'));
        $e->setDescription($this->readField($etude, 'objectif'));
        $e->setDateCreation($this->stringToDateTime($etude->date_ajout));
        $e->setStateID(self::SIAJE_ETUDE_STATES[$etude->statut]);
        if ($etude->acompte !== '0.00') {
            $e->setAcompte(true);
            $e->setPourcentageAcompte(((float)$etude->acompte) / 100);
        } else {
            $e->setAcompte(false);
        }
        $e->setFraisDossier((float)$etude->frais_dossier);
        $e->setPresentationProjet($this->readField($etude, 'resume'));
        $e->setDescriptionPrestation($etude->methodologie);
        if (!empty($etude->provenance)) {
            $e->setSourceDeProspection(self::SIAJE_SOURCE_PROSPECTION[$etude->provenance]);
        } else {
            $e->setSourceDeProspection(self::SIAJE_SOURCE_PROSPECTION['tiers']); //
        }
        $e->setProspect($prospect);

        $constraints = $this->validator->validate($e);
        $this->stopOnInvalidObject('Persist Etude in insert', $constraints);
        $this->em->persist($e);
        $this->em->flush();
        return $e;
    }

    public function insertEtudiant(\stdClass $etudiant): Personne
    {
        $p = new Personne();
        $p->setPrenom((!empty($etudiant->prenom) ? $this->readField($etudiant, 'prenom') : 'Inconnu'));
        $p->setNom((!empty($etudiant->nom) ? $this->readField($etudiant, 'nom') : 'Inconnu'));
        $p->setEmail($etudiant->mail);
        $p->setEmailEstValide(true);
        $p->setEstAbonneNewsletter(false);
        $p->setSexe(($etudiant->titre == 'M.' ? 'Monsieur' : 'Madame'));
        $p->setMobile($etudiant->mobile);
        $p->setAdresse($this->readField($etudiant, 'adresse'));
        $p->setCodePostal(intval($etudiant->code_postal) !== 0 ? intval($etudiant->code_postal) : null);
        $p->setVille($this->readField($etudiant, 'ville'));
        $p->setPays($etudiant->pays);
        $constraints = $this->validator->validate($p);
        $this->stopOnInvalidObject('Insert etudiant: personne', $constraints);

        $m = new Membre();
        $m->setPersonne($p);
        $m->setDateDeNaissance($this->stringToDateTime($etudiant->date_naissance));
        $m->setPromotion((intval($etudiant->promotion) !== 0 ? intval($etudiant->promotion) : null));
        $m->setNationalite(($this->readField($etudiant, 'nationalite') === 'Française' ? 'FR' : null));
        $m->setLieuDeNaissance($this->readField($etudiant, 'commune_naissance') . ', ' .
            $this->readField($etudiant, 'dpt_naissance'));
        $m->setSecuriteSociale($etudiant->n_secu_sociale);
        $m->setCommentaire($this->readField($etudiant, 'commentaire'));
        $m->setFiliere($this->filiere ? $this->filiere : $this->filiereStub());
        $constraints = $this->validator->validate($m);
        $this->stopOnInvalidObject('Insert Etudiant: membre', $constraints);


        $this->em->persist($m);
        $this->em->persist($p);
        $this->em->flush();

        return $p;
    }

    public function mergeEtudiant(Personne $personne, \stdClass $etudiant)
    {
        throw new \Exception('yyhh');
        $personne->setEmail($etudiant->mail);
        $personne->setEmailEstValide(true);
        $personne->setEstAbonneNewsletter(false);
        $personne->setSexe(($etudiant->titre == 'M.' ? 'Monsieur' : 'Madame'));
        $personne->setMobile($etudiant->mobile);
        $personne->setAdresse($this->readField($etudiant, 'adresse'));
        $personne->setCodePostal(intval($etudiant->code_postal) !== 0 ? intval($etudiant->code_postal) : null);
        $personne->setVille($this->readField($etudiant, 'ville'));
        $personne->setPays($etudiant->pays);
        $constraints = $this->validator->validate($personne);
        $this->stopOnInvalidObject('Insert etudiant: personne', $constraints);

        $m = new Membre();
        $m->setPersonne($p);
        $m->setDateDeNaissance($this->stringToDateTime($etudiant->date_naissance));
        $m->setPromotion((intval($etudiant->promotion) !== 0 ? intval($etudiant->promotion) : null));
        $m->setNationalite(($this->readField($etudiant, 'nationalite') === 'Française' ? 'FR' : null));
        $m->setLieuDeNaissance($this->readField($etudiant, 'commune_naissance') . ', ' .
            $this->readField($etudiant, 'dpt_naissance'));
        $m->setSecuriteSociale($etudiant->n_secu_sociale);
        $m->setCommentaire($this->readField($etudiant, 'commentaire'));
        $constraints = $this->validator->validate($m);
        $this->stopOnInvalidObject('Insert Etudiant: membre', $constraints);


        $this->em->persist($m);
        $this->em->persist($p);
        $this->em->flush();

        return $p;
    }

    /**
     * @param Etude $project
     * @param array $phases array of \stdClass, from the siaje_phases table
     * @param array $jehs array of \stdClass, from the siaje_jehs table
     * @param array $missions array . ['id_inter' => $mission] Prepopulated missions with valid member.
     *
     * @note On siaje, when a phase has several JEH, it is composed of several lines of jehs.
     * Those lines have a same title.
     */
    public function insertPhases(Etude $project, array $phases, array $jehs, array $missions)
    {

        foreach ($phases as $phase) {
            $g = new GroupePhases(); //default group
            $g->setTitre($this->readField($phase, 'titre'));
            $g->setNumero($phase->ordre);
            $g->setDescription($this->readField($phase, 'description'));
            $g->setEtude($project);
            /** @var array $sousPhases jehs belonging to $phase */
            $sousPhases = array_filter($jehs, function ($item) use ($phase) {
                return $item->id_phase === $phase->id_phase;
            });
            /** @var array $sousPhases cf @note */
            $sousPhases = $this->cleanJEHArray($sousPhases);
            foreach ($sousPhases as $sousPhase) {
                $p = new Phase();
                $p->setEtude($project);
                $p->setTitre($sousPhase->intitule);
                $p->setGroupe($g);
                $p->setPosition($sousPhase->ordre);
                $p->setNbrJEH($sousPhase->nb_jeh);
                $p->setPrixJEH($sousPhase->prix_jeh_ht);
                $delay = ($phase->semaine_fin - $phase->semaine_debut) * 7;
                $p->setDelai($delay);
                // If one intervenant is specified
                if ($sousPhase->id_inter !== 0) {
                    /** @var Mission $m */
                    $m = $missions[$sousPhase->id_inter];
                    $m->setFinOm($project->getAp()->getDateSignature()->modify('+'.$delay.'D'));
                    $intervenant = $this->siajeRepository->getIntervenantById($sousPhase->id_inter);

                    if(!$m->getEtude()) {
                        /** Finish mission filling */
                        $m->setEtude($project);
                        $m->setDebutOm($project->getAp()->getDateSignature());
                        // Might be wrong because several pourcentage are possible.
                        $m->setPourcentageJunior($sousPhase->prix_jeh_ht / $intervenant->remuneration);
                    }
                    $missions[$sousPhase->id_inter] = $m; // update Mission object

                    $rp = new RepartitionJEH();
                    $rp->setNbrJEH($sousPhase->nb_jeh);
                    $rp->setPrixJEH($intervenant->remuneration);
                    $rp->setMission($m);

                    $constraints = $this->validator->validate($rp);
                    $this->stopOnInvalidObject('RepartitionJEH validation', $constraints);

                    $constraints = $this->validator->validate($m);
                    $this->stopOnInvalidObject('Mission validation', $constraints);
                    $this->em->persist($rp);
                    $this->em->persist($m);
                }
                $constraints = $this->validator->validate($p);
                $this->stopOnInvalidObject('Jeh loop', $constraints);
                $this->em->persist($p);
            }
            $constraints = $this->validator->validate($g);
            $this->stopOnInvalidObject('GroupePhase loop', $constraints);
            $this->em->persist($g);

        }
        $this->em->flush();


       /* //manage project manager
        $contact = explode(' ', $this->normalize($this->readArray($data, 'Suiveur principal', true)));
        $firstname = $contact[0];
        unset($contact[0]);
        $surname = implode(' ', $contact);
        $pm = $this->em->getRepository('MgatePersonneBundle:Personne')->findOneBy(['nom' => $surname, 'prenom' => $firstname]);

        if ($pm !== null) {
            $e->setSuiveur($pm);
        } else {
            //create a new member and a new person
            if (array_key_exists($this->readArray($data, 'Suiveur principal', true), $array_manager) && $this->readArray($data, 'Suiveur principal', true) != '') {
                //has already been created before
                $e->setSuiveur($array_manager[$this->readArray($data, 'Suiveur principal', true)]);
            } else {
                $pm = new Personne();
                $pm->setPrenom($firstname);
                if ($surname == '') {
                    $pm->setNom('inconnu');
                } else {
                    $pm->setNom($surname);
                }
                $pm->setEmailEstValide(false);
                $pm->setEstAbonneNewsletter(false);
                $this->em->persist($pm);
                $m = new Membre();
                $m->setPersonne($pm);
                $this->em->persist($m);
                $e->setSuiveur($pm);
                $array_manager[$this->readArray($data, 'Suiveur principal', true)] = $pm;
            }
        }

        //manage AP & CC
        if ($this->dateManager($this->readArray($data, 'Date signature CC')) !== null) {
            $ap = new Ap();
            $ap->setEtude($e);
            $this->em->persist($ap);

            $cc = new Cc();
            $cc->setEtude($e);
            $cc->setDateSignature($this->dateManager($this->readArray($data, 'Date signature CC')));
            if (isset($pe)) {
                //if firm has been created in this loop iteration
                $cc->setSignataire2($pe);
            }
            $this->em->persist($cc);
        }

        //manage PVR
        if ($this->dateManager($this->readArray($data, 'Date signature PV')) !== null) {
            $pv = new ProcesVerbal();
            $pv->setEtude($e);
            $pv->setDateSignature($this->dateManager($this->readArray($data, 'Date signature PV')));
            $this->em->persist($pv);
        }*/
    }

    public function createMissions($jehs): array
    {
        /** Recupere un tableau d'id d'intervenants */
        /** @var array $intervenants id of the intervenants on the project */
        $intervenants = array_unique(array_map(function($item){
            return ($item->id_inter != 0 ? $item->id_inter:null);
        },$jehs));

        /** recupere un tableau de membres */
        $membres = [];
        foreach ($intervenants as $intervenant){
            $etudiant = $this->siajeRepository->getEtudiantByIntervenant($intervenant);
            // Works because assume mysql is used and mysql is case insensitive.
            $pm = $this->em->getRepository('MgatePersonneBundle:Personne')->findOneBy([
                'nom' => $this->readField($etudiant, 'nom'),
                'prenom' => $this->readField($etudiant, 'prenom')]);
            if($pm){
                $membres[$intervenant] = $pm->getMembre();
            }
            else {
                $membres[$intervenant] = $this->siajeObjectManager->insertEtudiant($etudiant)->getMembre();
            }
        }

        /** créé les missions */
        $missions = [];
        foreach ($membres as $id_inter => $membre){
            $m = new Mission();
            $m->setIntervenant($membre);
            $missions[$id_inter] = $m;
        }

        return $missions;
    }


    private function stopOnInvalidObject(string $point, ConstraintViolationListInterface $constraints)
    {
        if (count($constraints) !== 0) {

            $message = 'At ' . $point;
            /** @var ConstraintViolationInterface $cs */
            foreach ($constraints as $cs) {
                $message .= ' * ' . $cs->getPropertyPath() . ' ' . $cs->getMessage();
            }
            throw new \Exception($message);
        }
    }

    /**
     * Used in member creation process to have a valid filiere.
     *
     * @return Filiere a valid filiere object.
     */
    private function filiereStub(): Filiere
    {
        if ($this->filiere) {
            return $this->filiere;
        }

        $f = $this->em->getRepository('MgatePersonneBundle:Filiere')->findOneByNom('Default');

        if ($f) {
            $this->filiere = $f;
            return $f;
        }
        $f = new Filiere();
        $f->setNom('Default');
        $f->setDescription('Default filiere for Siaje import');
        $constraints = $this->validator->validate($f);
        $this->stopOnInvalidObject('Filiere stub ', $constraints);
        $this->em->persist($f);
        $this->filiere = $f;

        return $f;
    }

    /**
     * @param array $sousPhases lines of siaje_jehs as an array of \stdClass
     * @return array of \stdClass. Each intitule is unique and nb_jeh is set to the correct value (always 0 in siaje).
     */
    private function cleanJEHArray(array $sousPhases)
    {
        $result = [];
        $knownIntitules = []; // array of discovered titles. Same index as object in result
        foreach ($sousPhases as $s) {
            if (!in_array($s->intitule, $knownIntitules)) {
                $result[] = $s;
                $knownIntitules[] = $s->intitule;
            } else {
                $index = array_search($s->intitule, $knownIntitules);
                $result[$index]->nb_jeh += 1;
            }
        }

        return $result;

    }



}

