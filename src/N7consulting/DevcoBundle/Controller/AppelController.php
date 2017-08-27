<?php

namespace N7consulting\DevcoBundle\Controller;

use N7consulting\DevcoBundle\Entity\Appel;
use N7consulting\DevcoBundle\Form\Type\AppelType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AppelController extends Controller
{
    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     */
    public function indexAction()
    {
        return $this->render('N7consultingDevcoBundle:Default:index.html.twig', ['name' => 'tocard']);
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     */
    public function ajouterAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $appel = new Appel();

        $form = $this->createForm(AppelType::class, $appel);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->persist($appel);
                $em->flush();

                return $this->redirect($this->generateUrl('N7consultingDevco_appel_voir', ['id' => $appel->getId()]));
            }
        }

        return $this->render('N7consultingDevcoBundle:Appel:ajouter.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function modifierAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$appel = $em->getRepository('N7consulting\DevcoBundle\Entity\Appel')->find($id)) {
            throw $this->createNotFoundException('L\'appel demandé n\'existe pas !');
        }
        // On passe l'appel récupéré au formulaire
        $form = $this->createForm(AppelType::class, $appel);
        $deleteForm = $this->createDeleteForm($id);
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->persist($appel);
                $em->flush();

                return $this->redirect($this->generateUrl('N7consultingDevco_appel_voir', ['id' => $appel->getId()]));
            }
        }

        return $this->render('N7consultingDevcoBundle:Appel:modifier.html.twig', [
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     * utilisation du paramconverter
     */
    public function voirAction(Appel $appel, $id)
    {
        return $this->render('N7consultingDevcoBundle:Appel:voir.html.twig', ['appel' => $appel]);
    }

    /**
     * @Security("has_role('ROLE_CA')")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if (!$entity = $em->getRepository('N7consulting\DevcoBundle\Entity\Appel')->find($id)) {
                throw $this->createNotFoundException('L\'appel demandé n\'existe pas !');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('N7consultingDevco_homepage'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(['id' => $id])
            ->add('id', 'hidden')
            ->getForm();
    }
}
