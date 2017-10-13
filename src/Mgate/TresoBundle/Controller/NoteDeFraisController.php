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

use Mgate\TresoBundle\Entity\NoteDeFrais as NoteDeFrais;
use Mgate\TresoBundle\Form\Type\NoteDeFraisType as NoteDeFraisType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NoteDeFraisController extends Controller
{
    /**
     * @Security("has_role('ROLE_TRESO')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $nfs = $em->getRepository('MgateTresoBundle:NoteDeFrais')->findAll();

        return $this->render('MgateTresoBundle:NoteDeFrais:index.html.twig', ['nfs' => $nfs]);
    }

    /**
     * @Security("has_role('ROLE_TRESO')")
     *
     * @param NoteDeFrais $nf
     *
     * @return Response
     */
    public function voirAction(NoteDeFrais $nf)
    {
        return $this->render('MgateTresoBundle:NoteDeFrais:voir.html.twig', ['nf' => $nf]);
    }

    /**
     * @Security("has_role('ROLE_TRESO')")
     *
     * @param Request $request
     * @param $id
     *
     * @return RedirectResponse|Response
     */
    public function modifierAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$nf = $em->getRepository('MgateTresoBundle:NoteDeFrais')->find($id)) {
            $nf = new NoteDeFrais();
            $now = new \DateTime('now');
            $nf->setDate($now);
        }

        $form = $this->createForm(NoteDeFraisType::class, $nf);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                foreach ($nf->getDetails() as $nfd) {
                    $nfd->setNoteDeFrais($nf);
                }
                $em->persist($nf);
                $em->flush();
                $this->addFlash('success', 'Note de frais enregistrée');

                return $this->redirect($this->generateUrl('MgateTreso_NoteDeFrais_voir', ['id' => $nf->getId()]));
            }
            $this->addFlash('danger', 'Le formulaire contient des erreurs.');
        }

        return $this->render('MgateTresoBundle:NoteDeFrais:modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param NoteDeFrais $nf
     *
     * @return RedirectResponse
     */
    public function supprimerAction(NoteDeFrais $nf)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($nf);
        $em->flush();
        $this->addFlash('success', 'Note de frais supprimée');

        return $this->redirect($this->generateUrl('MgateTreso_NoteDeFrais_index', []));
    }
}
