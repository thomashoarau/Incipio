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

use Mgate\SuiviBundle\Entity\Mission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MissionController extends Controller
{
    /**
     * @Security("has_role('ROLE_SUIVEUR')")
     *
     * @param Mission $mission
     * @param Request $request
     *
     * @return Response
     */
    public function avancementAction(Mission $mission, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $avancement = !empty($request->request->get('avancement')) ? intval($request->request->get('avancement')) : 0;

        $mission->setAvancement($avancement);
        $em->persist($mission);
        $em->flush();

        return new Response($avancement);
    }
}
