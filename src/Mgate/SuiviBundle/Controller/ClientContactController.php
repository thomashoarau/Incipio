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

use Mgate\SuiviBundle\Entity\ClientContact;
use Mgate\SuiviBundle\Entity\Etude;
use Mgate\SuiviBundle\Form\Type\ClientContactHandler;
use Mgate\SuiviBundle\Form\Type\ClientContactType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ClientContactController extends Controller
{
    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('MgateSuiviBundle:ClientContact')->findBy([], ['date' => 'ASC']);

        return $this->render('MgateSuiviBundle:ClientContact:index.html.twig', [
            'contactsClient' => $entities,
        ]);
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     *
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

        $clientcontact = new ClientContact();
        $clientcontact->setEtude($etude);
        $form = $this->createForm(ClientContactType::class, $clientcontact);
        $formHandler = new ClientContactHandler($form, $request, $em);

        if ($formHandler->process()) {
            return $this->redirect($this->generateUrl('MgateSuivi_clientcontact_voir', ['id' => $clientcontact->getId()]));
        }

        return $this->render('MgateSuiviBundle:ClientContact:ajouter.html.twig', [
            'form' => $form->createView(),
            'etude' => $etude,
        ]);
    }

    private function compareDate(ClientContact $a, ClientContact $b)
    {
        if ($a->getDate() == $b->getDate()) {
            return 0;
        } else {
            return ($a->getDate() < $b->getDate()) ? -1 : 1;
        }
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     *
     * @param ClientContact $clientContact
     *
     * @return Response
     */
    public function voirAction(ClientContact $clientContact)
    {
        $etude = $clientContact->getEtude();

        if ($this->get('Mgate.etude_manager')->confidentielRefus($etude, $this->getUser())) {
            throw new AccessDeniedException('Cette étude est confidentielle');
        }

        $etude = $clientContact->getEtude();
        $contactsClient = $etude->getClientContacts()->toArray();
        usort($contactsClient, [$this, 'compareDate']);

        return $this->render('MgateSuiviBundle:ClientContact:voir.html.twig', [
            'contactsClient' => $contactsClient,
            'selectedContactClient' => $clientContact,
            'etude' => $etude,
            ]);
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     *
     * @param Request       $request
     * @param ClientContact $clientContact
     *
     * @return RedirectResponse|Response
     */
    public function modifierAction(Request $request, ClientContact $clientContact)
    {
        $em = $this->getDoctrine()->getManager();

        $etude = $clientContact->getEtude();

        if ($this->get('Mgate.etude_manager')->confidentielRefus($etude, $this->getUser())) {
            throw new AccessDeniedException('Cette étude est confidentielle');
        }

        $form = $this->createForm(ClientContactType::class, $clientContact);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->flush();
                $this->addFlash('success', 'Contact client modifié');

                return $this->redirectToRoute('MgateSuivi_clientcontact_voir', ['id' => $clientContact->getId()]);
            }
            $this->addFlash('danger', 'Le formulaire contient des erreurs.');
        }

        return $this->render('MgateSuiviBundle:ClientContact:modifier.html.twig', [
            'form' => $form->createView(),
            'clientcontact' => $clientContact,
            'etude' => $etude,
        ]);
    }
}
