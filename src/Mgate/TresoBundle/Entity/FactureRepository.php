<?php

/*
 * This file is part of the Incipio package.
 *
 * (c) Florian Lefevre
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mgate\TresoBundle\Entity;

use Doctrine\ORM\EntityRepository;

class FactureRepository extends EntityRepository
{
    /**
     * Renvoie les facture d'achat ou de vente sur un mois selon la date d'emission pour les factures d'achat et d'encaissement pour les factures de vente.
     * YEAR MONTH DAY sont défini dans DashBoardBundle/DQL.
     *
     * @param int  $type
     * @param int  $month
     * @param int  $year
     * @param bool $trimestriel
     *
     * @return array
     */
    public function findAllTVAByMonth($type, $month, $year, $trimestriel = false)
    {
        $date = ($type == 1 ? 'dateEmission' : 'dateVersement');
        $qb = $this->_em->createQueryBuilder();
        $query = $qb->select('f')
            ->from('MgateTresoBundle:Facture', 'f')
            ->where('f.type ' . ($type == Facture::TYPE_ACHAT ? '=' : '>') . ' ' . Facture::TYPE_ACHAT);
        if ($trimestriel) {
            $query->andWhere('MONTH(f.' . $date . ') >= :month')
                ->setParameter('month', $month)
                ->andWhere('MONTH(f.' . $date . ') < (:month + 2)');
        } else {
            $query->where("MONTH(f.$date) = $month");
        }

        $query->andWhere('YEAR(f.' . $date . ') = :year')
            ->setParameter('year', $year)
            ->orderBy('f.' . $date);

        return $query->getQuery()->getResult();
    }

    /**
     * Retourne la somme des montant HT des factures pour les études d'un mandat.
     *
     * @param int       $mandat mandat des etudes dont les factures seront prises en compte
     * @param bool|null $paid   est-ce que seul les factures payées doivent être prises en compte
     *
     * @return float | null
     */
    public function getCAFacture(int $mandat, ?bool $paid = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('sum(fd.montantHT) as montant')
            ->from('MgateTresoBundle:FactureDetail', 'fd')
            ->leftJoin('fd.facture', 'facture')
            ->where('facture.type  in (:type)')
            ->setParameter('type', [Facture::TYPE_VENTE_ACCOMPTE, Facture::TYPE_VENTE_INTERMEDIAIRE, Facture::TYPE_VENTE_SOLDE])
            ->leftJoin('facture.etude', 'etude')
            ->andWhere('etude.mandat = :mandat')
            ->setParameter('mandat', $mandat);

        if ($paid !== null && $paid) {
            $qb->andWhere('facture.dateVersement IS NOT NULL');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
