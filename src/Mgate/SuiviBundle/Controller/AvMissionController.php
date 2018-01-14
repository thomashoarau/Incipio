<?php

/*
 * This file is part of the Incipio package.
 *
 * (c) Florian Lefevre
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mgate\SuiviBundle\Controller;

use Mgate\SuiviBundle\Entity\AvMission;
use Mgate\SuiviBundle\Entity\Etude;
use Mgate\SuiviBundle\Form\Type\AvMissionHandler;
use Mgate\SuiviBundle\Form\Type\AvMissionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AvMissionController extends Controller
{
    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     * @param Request $request
     * @param Etude   $etude
     *
     * @return RedirectResponse|Response
     */
    public function addAction(Request $request, Etude $etude)
    {
        $em = $this->getDoctrine()->getManager();

        if ($this->get('Mgate.etude_manager')->confidentielRefus($etude, $this->getUser())) {
            throw new AccessDeniedException('Cette étude est confidentielle');
        }

        $avmission = new AvMission();
        $avmission->setEtude($etude);
        $form = $this->createForm(AvMissionType::class, $avmission);
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->persist($avmission);
                $em->flush();
                $this->addFlash('success', 'Avenant de mission ajouté');

                return $this->redirectToRoute('MgateSuivi_etude_voir', ['nom' => $etude->getNom()]);
            }
            $this->addFlash('danger', 'Le formulaire contient des erreurs.');
        }

        return $this->render('MgateSuiviBundle:AvMission:ajouter.html.twig', [
            'etude' => $etude,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     *
     * @param Request   $request
     * @param AvMission $avmission
     *
     * @return RedirectResponse|Response
     */
    public function modifierAction(Request $request, AvMission $avmission)
    {
        $em = $this->getDoctrine()->getManager();

        $etude = $avmission->getEtude();

        if ($this->get('Mgate.etude_manager')->confidentielRefus($etude, $this->getUser())) {
            throw new AccessDeniedException('Cette étude est confidentielle');
        }

        $form = $this->createForm(AvMissionType::class, $avmission);

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->flush();

                return $this->redirectToRoute('MgateSuivi_etude_voir', ['nom' => $etude->getNom()]);
            }
        }

        return $this->render('MgateSuiviBundle:AvMission:modifier.html.twig', [
            'etude' => $etude,
            'form' => $form->createView(),
            'avmission' => $avmission,
        ]);
    }
}
