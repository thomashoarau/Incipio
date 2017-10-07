<?php

/*
 * This file is part of the Incipio package.
 *
 * (c) Florian Lefevre
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mgate\TresoBundle\Controller;

use JMS\Serializer\Exception\LogicException;
use Mgate\TresoBundle\Entity\BV;
use Mgate\TresoBundle\Form\Type\BVType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BVController extends Controller
{
    /**
     * @Security("has_role('ROLE_TRESO')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $bvs = $em->getRepository('MgateTresoBundle:BV')->findAll();

        return $this->render('MgateTresoBundle:BV:index.html.twig', ['bvs' => $bvs]);
    }

    /**
     * @Security("has_role('ROLE_TRESO')")
     * @param BV $bv
     * @return Response
     */
    public function voirAction(BV $bv)
    {
        return $this->render('MgateTresoBundle:BV:voir.html.twig', ['bv' => $bv]);
    }

    /**
     * @Security("has_role('ROLE_TRESO', 'ROLE_SUIVEUR')")
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function modifierAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$bv = $em->getRepository('MgateTresoBundle:BV')->find($id)) {
            $bv = new BV();
            $bv->setTypeDeTravail('Réalisateur')
                ->setDateDeVersement(new \DateTime('now'))
                ->setDateDemission(new \DateTime('now'));
        }

        $form = $this->createForm(BVType::class, $bv);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $bv->setCotisationURSSAF();
                $charges = $em->getRepository('MgateTresoBundle:CotisationURSSAF')->findAllByDate($bv->getDateDemission());
                foreach ($charges as $charge) {
                    $bv->addCotisationURSSAF($charge);
                }
                if ($charges === null) {
                    throw new LogicException('Il n\'y a aucune cotisation Urssaf définie pour cette période.Pour ajouter des cotisations URSSAF : ' . $this->get('router')->generate('MgateTreso_CotisationURSSAF_index') . '.');
                }

                $baseURSSAF = $em->getRepository('MgateTresoBundle:BaseURSSAF')->findByDate($bv->getDateDemission());
                if ($baseURSSAF === null) {
                    throw new LogicException('Il n\'y a aucune base Urssaf définie pour cette période.Pour ajouter une base URSSAF : ' . $this->get('router')->generate('MgateTreso_BaseURSSAF_index') . '.');
                }
                $bv->setBaseURSSAF($baseURSSAF);

                $em->persist($bv);
                $em->flush();

                return $this->redirect($this->generateUrl('MgateTreso_BV_index', []));
            }
        }

        return $this->render('MgateTresoBundle:BV:modifier.html.twig', [
            'form' => $form->createView(),
            'bv' => $bv,
        ]);
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @param BV $bv
     * @return RedirectResponse
     */
    public function supprimerAction(BV $bv)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($bv);
        $em->flush();

        return $this->redirect($this->generateUrl('MgateTreso_BV_index', []));
    }
}
