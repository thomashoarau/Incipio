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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MissionController extends Controller
{
    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function avancementAction(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $em = $this->getDoctrine()->getManager();
            $avancement = !empty($request->request->get('avancement')) ? intval($request->request->get('avancement')) : 0;
            $id = !empty($request->request->get('id')) ? $request->request->get('id') : 0;
            $intervenant = !empty($request->request->get('intervenant')) ? intval($request->request->get('intervenant')) : 0;

            $etude = $em->getRepository('Mgate\SuiviBundle\Entity\Etude')->find($id);
            if (!$etude) {
                throw $this->createNotFoundException('L\'étude n\'existe pas !');
            } else {
                $etude->getMissions()->get($intervenant)->setAvancement($avancement);
                $em->persist($etude->getMissions()->get($intervenant));
                $em->flush();
            }

            return $this->redirect($this->generateUrl('MgateSuivi_mission_avancement'));
        }

        return new Response('ok !');
    }
}
