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
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use HLP\NebulaBundle\Entity\Meta;
use HLP\NebulaBundle\Entity\Branch;
use HLP\NebulaBundle\Entity\Build;
use HLP\NebulaBundle\Entity\User;
use HLP\NebulaBundle\Form\MetaType;
use HLP\NebulaBundle\Form\MetaEditType;
use HLP\NebulaBundle\Form\BranchType;

class MetaController extends Controller
{
    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     */
    public function showOverviewAction(Request $request, Meta $meta)
    {
        $builds = $meta->getDefaultBranch()->getBuilds();
        $data = array();

        foreach ($builds as $build) {
            if($build->getState() == Build::DONE) {
                $b = json_decode($build->getGeneratedJSON())->mods[0];
                $b->build = $build;
                $data[] = $b;
            }
        }

        return $this->render('HLPNebulaBundle:Meta:overview.html.twig', [
            'meta' => $meta,
            'builds' => $builds,
            'meta_data' => $data
        ]);
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     */
    public function showDetailsAction(Request $request, Meta $meta)
    {
        $session = new Session();
        $session->set('meta_refer', $this->getRequest()->getUri());

        return $this->render('HLPNebulaBundle:Meta:details.html.twig', array(
            'meta'   => $meta
        ));
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     */
    public function showBranchesAction(Request $request, Meta $meta)
    {
        if($this->isGranted('EDIT', $meta)) {
            $branches = $meta->getBranches();
        } else {
            $branches = $meta->getPublicBranches();
        }

        $request->getSession()->set('branch_refer', $this->getRequest()->getUri());

        return $this->render('HLPNebulaBundle:Meta:branches.html.twig', array(
            'meta'           => $meta,
            'branchesList'   => $branches,
        ));
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     */
    public function showTeamAction(Meta $meta, $page)
    {
        if ($page < 1) {
            throw $this->createNotFoundException("Page ".$page." not found.");
        }

        $nbPerPage = 10;

        $usersList = $this->getDoctrine()->getManager()
            ->getRepository('HLPNebulaBundle:User')
            ->getUsers($meta, $page, $nbPerPage)
        ;

        $nbPages = ceil(count($usersList)/$nbPerPage);

        if ($page > $nbPages && $nbPages != 0) {
            throw $this->createNotFoundException("Page ".$page." not found.");
        }

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('hlp_nebula_repository_meta_team_add', array('meta' => $meta)))
            ->add('user', TextType::class)
            ->getForm();

        return $this->render('HLPNebulaBundle:Meta:team.html.twig', array(
            'form'  => $form->createView(),
            'meta'  => $meta,
            'usersList' => $usersList,
            'nbPages' => $nbPages,
            'page' => $page
        ));
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     * @Security("is_granted('EDIT', meta)")
     */
    public function addTeamMemberAction(Request $request, Meta $meta)
    {
        $form = $this->createFormBuilder()
            ->add('user', TextType::class)
            ->getForm();

        $form->handleRequest($request);

        if($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $userName = $form->getData()['user'];
            $user = $em->getRepository('HLPNebulaBundle:User')->findSingleUser($userName);

            if(empty($user)) {
                $request->getSession()->getFlashBag()
                    ->add('error', 'User ' . $userName . ' was not found!');

                return $this->redirect($this->generateUrl('hlp_nebula_repository_meta_team', array('meta' => $meta)));
            } else {
                $meta->addUser($user);
                $em->flush();
            }
        } else {
            $request->getSession()->getFlashBag()
                ->add('error', 'Invalid data was sent!');
        }

        return $this->redirect($this->generateUrl('hlp_nebula_repository_meta_team', array('meta' => $meta)));
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     * @ParamConverter("user", options={"mapping": {"user": "usernameCanonical"}})
     * @Security("is_granted('EDIT', meta)")
     */
    public function removeTeamMemberAction(Request $request, Meta $meta, User $user)
    {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
                
            $meta->removeUser($user);
            $em->flush();

            return $this->redirect($this->generateUrl('hlp_nebula_repository_meta_team', array('meta' => $meta)));
        }

        return $this->render('HLPNebulaBundle:Meta:remove_team_member.html.twig', array(
            'form'   => $form->createView(),
            'meta'   => $meta,
            'member' => $user
        ));
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     */
    public function showActivityAction(Meta $meta)
    {
        return $this->render('HLPNebulaBundle:Meta:activity.html.twig', array(
            'meta'   => $meta
        ));
    }

    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function createAction(Request $request)
    {
        $meta = new Meta;

        $form = $this->createForm(new MetaType(), $meta);

        if ($form->handleRequest($request)->isValid())
        {
            $defaultBranch = new Branch;
            $defaultBranch->setBranchId("master");
            $defaultBranch->setName("Master");
            $defaultBranch->setNotes("This is a default branch, created automatically on mod creation.");
            $defaultBranch->setIsDefault(true);

            $meta->addBranch($defaultBranch);
            $meta->addUser($this->getUser());

            $em = $this->getDoctrine()->getManager();

            $em->persist($meta);
            $em->persist($defaultBranch);

            $em->flush();

            return $this->redirect($this->generateUrl('hlp_nebula_repository_branch', array(
                'meta'    => $meta,
                'branch'  => $defaultBranch
            )));
        }

        return $this->render('HLPNebulaBundle:Meta:create.html.twig', array(
            'form'  => $form->createView()
        ));
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     * @Security("is_granted('EDIT', meta)")
     */
    public function updateAction(Request $request, Meta $meta)
    {
        $session = new Session();
        $referURL = $session->get('meta_refer');

        $form = $this->createForm(new MetaEditType(), $meta);

        if ($form->handleRequest($request)->isValid()) {
            foreach ($meta->getBranches() as $branch) {
                $branch->setIsDefault(false);
            }

            $defaultBranch = $form->get('defaultBranch')->getData();

            if ($defaultBranch) {
                $defaultBranch->setIsDefault(true);
            }

            $em = $this->getDoctrine()
                ->getManager();

            $em->flush();

            $request->getSession()
                ->getFlashBag()
                ->add('success', 'Mod "'.$meta->getTitle().'" (id: '.$meta->getMetaId().') has been successfully edited.');

            if(empty($referURL)) {
                $referURL = $this->generateUrl('hlp_nebula_repository_meta_overview', array('meta' => $meta));
            }
            return $this->redirect($referURL);
        }

        return $this->render('HLPNebulaBundle:Meta:update.html.twig', array(
            'meta'      => $meta,
            'form'     => $form->createView(),
            'referURL' => $referURL
        ));
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     * @Security("is_granted('DELETE', meta)")
     */
    public function deleteAction(Request $request, Meta $meta)
    {
        $session = new Session();
        $referURL = $session->get('meta_refer');

        $form = $this->createFormBuilder()->getForm();

        if ($form->handleRequest($request)->isValid()) {
            $em = $this->getDoctrine()
                ->getManager();

            $em->remove($meta);
            $em->flush();

            $request->getSession()
                ->getFlashBag()
                ->add('success', 'Mod "'.$meta->getTitle().'" (id: '.$meta->getMetaId().') has been deleted.');

            return $this->redirect($this->generateUrl('hlp_nebula_user', array('user' => $this->getUser())));
        }

        return $this->render('HLPNebulaBundle:Meta:delete.html.twig', array(
            'meta'      => $meta,
            'form'     => $form->createView(),
            'referURL' => $referURL
        ));
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     */
    public function repoAction(Request $request, Meta $meta)
    {
        return $this->redirect($this->generateUrl('hlp_nebula_fs2mod_repo', array(
            'meta' => $meta,
            'branch' => $this->getDefaultBranch()
        )));
    }

    /**
     * @ParamConverter("meta", options={"mapping": {"meta": "metaId"}})
     */
    public function trackInstallAction(Request $request, Meta $meta)
    {
        $repo = $this->getDoctrine()->getManager()->getRepository('HLPNebulaBundle:Meta');
        if($repo->incInstallCount($meta->getMetaId())) {
            return new JsonResponse(array('success' => true));
        } else {
            return new JsonResponse(array('success' => false));
        }
    }
}
