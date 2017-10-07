<?php

namespace Mgate\PubliBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ImportController extends Controller
{
    const SIAJE_SQL = 'Siaje SQL';
    const AVAILABLE_FORMATS = [self::SIAJE_SQL];

    /**
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        set_time_limit(0);
        $form = $this->createFormBuilder([])
            ->add('import_method', ChoiceType::class, ['label' => 'Type du fichier',
                    'required' => true,
                    'choices' => $this::AVAILABLE_FORMATS,
                    'choice_label' => function ($value) {
                        return $value;
                    },
                    'expanded' => true,
                    'multiple' => false,]
            )
            ->add('file', FileType::class, ['label' => 'Fichier',
                    'required' => true,
                    'attr' => ['cols' => '100%', 'rows' => 5]]
            )
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('import_method')->getData() == self::SIAJE_SQL) {
                $data = $form->getData();
                $siajeImporter = $this->get('Mgate.import.siaje_etude');
                // Throw an exception if things go wrong.
                $results = $siajeImporter->run($data['file']);

                $this->addFlash('success', 'Document importé. ' . $results['inserted_projects'] . ' études importées');

                return $this->redirect($this->generateUrl('Mgate_publi_import'));
            }
        }

        return $this->render('MgatePubliBundle:Import:index.html.twig', ['form' => $form->createView()]);
    }


}
