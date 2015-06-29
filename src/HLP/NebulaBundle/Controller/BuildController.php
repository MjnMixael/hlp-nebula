<?php

/*
* Copyright 2014 HLP-Nebula authors, see NOTICE file

*
* Licensed under the EUPL, Version 1.1 or – as soon they
will be approved by the European Commission - subsequent
versions of the EUPL (the "Licence");
* You may not use this work except in compliance with the
Licence.
* You may obtain a copy of the Licence at:
*
*
http://ec.europa.eu/idabc/eupl

*
* Unless required by applicable law or agreed to in
writing, software distributed under the Licence is
distributed on an "AS IS" basis,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
express or implied.
* See the Licence for the specific language governing
permissions and limitations under the Licence.
*/ 

namespace HLP\NebulaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


use HLP\NebulaBundle\Entity\Meta;
use HLP\NebulaBundle\Entity\Branch;
use HLP\NebulaBundle\Entity\Build;
use HLP\NebulaBundle\Form\BuildType;
use HLP\NebulaBundle\Form\BuildTransferType;

class BuildController extends Controller
{
    /**
     * @ParamConverter("branch", options={"mapping": {"meta": "meta", "branch": "branchId"}, "repository_method" = "findOneWithParent"})
     * @Security("is_granted('EDIT', branch.getMeta())")
     */
    public function createAction(Request $request, Branch $branch)
    {
        $build = new Build;
        $form = $this->createForm(new BuildType($semver_pat), $build);

        $form->handleRequest($request);

        if ($form->isValid())
        {
            $build->setStatus(Build::WAITING);
            $branch->addBuild($build);
            
            $em = $this->getDoctrine()->getManager();

            $em->persist($build);
            $em->flush();

            // $request->getSession()
            //     ->getFlashBag()
            //     ->add('success', 'New build <strong>version '.$build->getVersion().'</strong> successfully created.');

            return $this->redirect($this->generateUrl('hlp_nebula_process', array(
                'meta'   => $build->getMeta(),
                'branch' => $build->getBranch(),
                'build'  => $build
            )));
        }
        
        if ((!$form->isValid()) && $request->isMethod('POST') )
        {
            $request->getSession()
                ->getFlashBag()
                ->add('error', '<strong>Invalid data !</strong> Please check this form again.');
        }
        
        return $this->render('HLPNebulaBundle:Build:create.html.twig', array(
            'meta'   => $branch->getMeta(),
            'branch' => $branch,
            'form'   => $form->createView()
        ));
    }
    
    /**
     * @ParamConverter("build", options={"mapping": {"meta": "meta", "branch": "branch", "build": "version"}, "repository_method" = "findOneWithParents"})
     */
    public function showDetailsAction(Build $build)
    {
        return $this->render('HLPNebulaBundle:Build:details.html.twig', array(
            'meta'   => $build->getMeta(),
            'branch' => $build->getBranch(),
            'build'  => $build
        ));
    }
    
    /**
     * @ParamConverter("build", options={"mapping": {"meta": "meta", "branch": "branch", "build": "version"}, "repository_method" = "findOneWithParents"})
     */
    public function showFilesAction(Build $build)
    {
        $build_data = json_decode($build->getGeneratedJSON())->mods[0];

        $archives = array();
        foreach ($build_data->packages as $pkg) {
            foreach ($pkg->files as $file) {
                if ($file->is_archive) {
                    $archives[$pkg->name . '/' . $file->filename] = $file;
                }
            }
        }
        
        return $this->render('HLPNebulaBundle:Build:files.html.twig', array(
            'meta'       => $build->getMeta(),
            'branch'     => $build->getBranch(),
            'build'      => $build,
            'build_data' => $build_data,
            'archives'   => $archives
        ));
    }

    public function rawAction($path)
    {
        $path = explode('/', $path);

        switch(count($path)) {
            case 1:
                $path[] = 'default';
            case 2:
                $path[] = 'latest';
        }

        list($metaId, $branch, $version) = $path;

        if ($branch == 'default') $branch = null;
        if ($version == 'latest') $version = null;

        $build = $this->getDoctrine()->getManager()->getRepository('HLPNebulaBundle:Build')
            ->findSingleBuild($metaId, $branch, $version);

        if ($build === null) {
            throw new NotFoundHttpException('Build not found!');
        }

        if (null == $build->getGeneratedJSON()) {
            if ($build->getState() == Build::FAILED) {
                throw new NotFoundHttpException("No JSON data for this build, validation failed.");
            } else {
                throw new NotFoundHttpException("No JSON data for this build, validation not finished.");
            }
        }

        $response = new Response($build->getGeneratedJSON());
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @ParamConverter("build", options={"mapping": {"meta": "meta", "branch": "branch", "build": "version"}, "repository_method" = "findOneWithParents"})
     * @Security("is_granted('EDIT', build.getBranch().getMeta())")
     */
    public function newBuildFromAction(Request $request, Build $build)
    {
        $branch = $build->getBranch();
        $meta = $branch->getMeta();
        
        if (false === $this->get('security.context')->isGranted('EDIT', $meta)) {
            throw new AccessDeniedException('Unauthorised access!');
        }

        $newBuild = clone $build;
        $form = $this->createForm(new BuildType(), $newBuild);
        $form->handleRequest($request);

        if ($form->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            $em->persist($newBuild);
            $em->flush();

            $request->getSession()
                ->getFlashBag()
                ->add('success', "New build <strong>version ".$newBuild->getVersion()."</strong> successfully created from <strong>version ".$build->getVersion()."</strong>.");

            return $this->redirect($this->generateUrl('hlp_nebula_process', array(
                'meta'   => $meta,
                'branch' => $branch,
                'build'  => $newBuild
            )));
        }

        if ((!$form->isValid()) && $request->isMethod('POST') )
        {
            $request->getSession()->getFlashBag()
                ->add('error', '<strong>Invalid data !</strong> Please check this form again.');
        }

        return $this->render('HLPNebulaBundle:Build:create.html.twig', array(
            'meta'   => $branch->getMeta(),
            'branch' => $branch,
            // 'build'  => $build,
            'form'   => $form->createView()
        ));
    }

    /**
     * @ParamConverter("build", options={"mapping": {"meta": "meta", "branch": "branch", "build": "version"}, "repository_method" = "findOneWithParents"})
     * @Security("is_granted('EDIT', build.getBranch().getMeta())")
     */
    public function transferAction(Request $request, Build $build)
    {
        $branch = $build->getBranch();
        $meta = $branch->getMeta();

        if (false === $this->get('security.context')->isGranted('EDIT', $meta)) {
            throw new AccessDeniedException('Unauthorised access!');
        }

        $newBuild = clone $build;
        $form = $this->createForm(new BuildTransferType(), $newBuild);
        if ($form->handleRequest($request)->isValid())
        {

            $em = $this->getDoctrine()->getManager();
            $em->persist($newBuild);
            $em->flush();

            $request->getSession()->getFlashBag()
                ->add('success', "New build <strong>version ".$newBuild->getVersion()."</strong> successfully created from <strong>version ".$build->getVersion()."</strong>.");

            return $this->redirect($this->generateUrl('hlp_nebula_process', array(
                'meta'    => $meta,
                'branch' => $newBuild->getBranch(),
                'build'  => $newBuild
            )));
        }

        if ((!$form->isValid()) && $request->isMethod('POST') )
        {
            $request->getSession()->getFlashBag()
                ->add('error', '<strong>Invalid data !</strong> Please check this form again.');
        }

        return $this->render('HLPNebulaBundle:Build:create.html.twig', array(
            'meta'   => $branch->getMeta(),
            'branch' => $branch,
            // 'build'  => $build,
            'form'   => $form->createView()
        ));
    }
  
    /**
     * @ParamConverter("build", options={"mapping": {"meta": "meta", "branch": "branch", "build": "version"}, "repository_method" = "findOneWithParents"})
     */
    public function processAction(Build $build)
    {
        $branch = $build->getBranch();
        $meta = $branch->getMeta();
        $sec = $this->get('security.context');
    
        if($build->getState() <= Build::PROCESSING)
        {
            $ks = $this->container->get('hlpnebula.knossos');

            if ($build->getState() == Build::WAITING)
            {
                if ($sec->isGranted('EDIT', $meta))
                {
                    $jsonBuilder = $this->container
                        ->get('hlpnebula.json_builder');
            
                    $data = $jsonBuilder->createFromBuild($build, false);
                    $data = json_encode(Array('mods' => Array($data)));
            
                    $webhook = $this->generateUrl('hlp_nebula_process_finalise', array(
                        'meta'    => $meta,
                        'branch' => $branch,
                        'build'  => $build
                    ), true);
            
                    $ksresponse = $ks->requestConversion($data, $webhook);
            
                    if($ksresponse)
                    {
                        $build->setState(Build::PROCESSING);
                        $build->setConverterToken($ksresponse->token);
                        $build->setConverterTicket($ksresponse->ticket);
              
                        $em = $this->getDoctrine()->getManager();
                        $em->flush();
                    }
                    else
                    {
                        return $this->render('HLPNebulaBundle:Build:process.html.twig', array(
                            'meta'          => $meta,
                            'branch'        => $branch,
                            'build'         => $build,
                            'is_owner'      => true,
                            'request_failed' => true
                        ));
                    }
                } else {
                    $request->getSession()->getFlashBag()
                        ->add('danger', "I'm sorry but you don't have the rights to do that!");

                    return $this->redirect($this->generateUrl('hlp_nebula_build', array(
                        'meta'   => $meta,
                        'branch' => $branch,
                        'build'  => $build
                    )));
                }
            }

            $ksticket = $build->getConverterTicket();

            return $this->render('HLPNebulaBundle:Build:process.html.twig', array(
                'meta'             => $meta,
                'branch'           => $branch,
                'build'            => $build,
                'ksticket'         => $ksticket,
                'converter_script' => $ks->getScriptURL(),
                'ws_url'           => $ks->getWsURL(),
                'is_owner'         => $sec->isGranted('EDIT', $meta),
                'request_failed'   => false
            ));
        }

        return $this->redirect($this->generateUrl('hlp_nebula_build', array(
            'meta'   => $meta,
            'branch' => $branch,
            'build'  => $build
        )));
    }

    /**
     * @ParamConverter("build", options={"mapping": {"meta": "meta", "branch": "branch", "build": "version"}, "repository_method" = "findOneWithParents"})
     * @Security("is_granted('EDIT', build.getBranch().getMeta())")
     */
    public function reprocessAction(Build $build)
    {
        $build->setState(Build::WAITING);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirect($this->generateUrl('hlp_nebula_process', array(
            'meta' => $build->getMeta(),
            'branch' => $build->getBranch(),
            'build' => $build
        )));
    }

    /**
     * @ParamConverter("build", options={"mapping": {"meta": "meta", "branch": "branch", "build": "version"}, "repository_method" = "findOneWithParents"})
     */
    public function processFinaliseAction(Request $request, Build $build)
    {
        $branch = $build->getBranch();
        $meta = $branch->getMeta();
    
        if($build->getState() == Build::PROCESSING && $build->getConverterTicket() !== null)
        {
            $ks = $this->container->get('hlpnebula.knossos');
            $ksresponse = $ks->retrieveConverted($build->getConverterTicket(), $build->getConverterToken());
    
            if($ksresponse)
            {
                if($ksresponse->finished == true)
                {
                    if($ksresponse->success == true)
                    {
                        $build->setGeneratedJSON($ksresponse->json);
                        $build->setState(Build::DONE);
                        
                        $request->getSession()->getFlashBag()
                            ->add('success', "Build <strong>version ".$build->getVersion()."</strong> has been successfully validated.");
                    }
                    else
                    {
                        $build->setState(Build::FAILED);
                        
                        $request->getSession()->getFlashBag()
                            ->add('warning', "Build <strong>version ".$build->getVersion()."</strong> validation has failed.");
                    }

                    $this->getDoctrine()->getManager()->flush();
                }
                else
                {
                    return $this->redirect($this->generateUrl('hlp_nebula_process', array(
                        'meta'   => $meta,
                        'branch' => $branch,
                        'build'  => $build
                    )));
                }
            }
            else
            {
                $sec = $this->container->get('security.context');
                return $this->render('HLPNebulaBundle:Build:process.html.twig', array(
                    'meta'          => $meta,
                    'branch'        => $branch,
                    'build'         => $build,
                    'is_owner'      => $sec->isGranted('EDIT', $meta),
                    'request_failed' => true
                ));
            }
        }

        if($request->getMethod() == 'POST')
        {      
            $response = new Response(json_encode(array('cancelled' => $build->getState() == Build::FAILED)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return $this->redirect($this->generateUrl('hlp_nebula_build', array(
            'meta'   => $meta,
            'branch' => $branch,
            'build'  => $build
        )));
    }

    /**
     * @ParamConverter("build", options={"mapping": {"meta": "meta", "branch": "branch", "build": "version"}, "repository_method" = "findOneWithParents"})
     */
    public function processForceFailAction(Request $request, Build $build)
    {
        $branch = $build->getBranch();
        $meta = $branch->getMeta();
    
        if (false === $this->get('security.context')->isGranted('EDIT', $meta)) {
            throw new AccessDeniedException('Unauthorised access!');
        }
    
        if($build->getState() == Build::PROCESSING)
        {
            $build->setState(Build::PROCESSING);
            $this->getDoctrine()->getManager()->flush();
      
            $request->getSession()->getFlashBag()
                ->add('warning', "Build <strong>version ".$build->getVersion()."</strong> has been marked as failed. Processing canceled.");
        }

        return $this->redirect($this->generateUrl('hlp_nebula_build', array(
            'meta'   => $meta,
            'branch' => $branch,
            'build'  => $build
        )));
    }

    /**
     * @ParamConverter("build", options={"mapping": {"meta": "meta", "branch": "branch", "build": "version"}, "repository_method" = "findOneWithParents"})
     */
    public function deleteAction(Request $request, Build $build)
    {
        $branch = $build->getBranch();
        $meta = $branch->getMeta();

        if (false === $this->get('security.context')->isGranted('EDIT', $meta)) {
            throw new AccessDeniedException('Unauthorized access!');
        }

        $form = $this->createFormBuilder()->getForm();
        if ($form->handleRequest($request)->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            $em->remove($build);
            $em->flush();

            $request->getSession()->getFlashBag()
                ->add('success', "Build <strong>version ".$build->getVersion()."</strong> has been deleted.");

            return $this->redirect($this->generateUrl('hlp_nebula_branch', array(
                'meta'   => $meta,
                'branch' => $branch
            )));
        }

        return $this->render('HLPNebulaBundle:Build:delete.html.twig', array(
            'meta'   => $meta,
            'branch' => $branch,
            'build'  => $build,
            'form'   => $form->createView()
        ));
    }
}
