<?php

/*
* Copyright 2014 HLP-Nebula authors, see NOTICE file
4
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
5
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

class AJAXController extends Controller
{
  public function searchMetasAction(Request $request)
  {
    if($request->isXmlHttpRequest())
    {
      $term = $request->query->get('term');
      $data = array();

      if(!empty($term))
      {
        $metas = $this->getDoctrine()->getManager()
          ->getRepository('HLPNebulaBundle:Meta')->searchMetas($term);

        foreach($metas as $meta)
        {
          $data[] = $meta->getMetaId();
        }
      }
      
      return new JsonResponse($data);
    }
    else
    {
      return $this->redirect('/');
    }
  }

  public function searchUsersAction(Request $request)
  {
    if($request->isXmlHttpRequest())
    {
      $term = $request->query->get('term');
      $data = array();

      if(!empty($term))
      {
        $users = $this->getDoctrine()->getManager()
          ->getRepository('HLPNebulaBundle:User')->searchUsers($term);

        foreach($users as $user)
        {
          $data[] = $user->getUsernameCanonical();
        }
      }
      
      return new JsonResponse($data);
    }
    else
    {
      return $this->redirect('/');
    }
  }
  
  public function searchPackagesInMetaAction(Request $request, $meta)
  {
    if($request->isXmlHttpRequest())
    {

      $em = $this->getDoctrine()->getManager();
      $term = $request->query->get('term');
      
      $packages = $em->getRepository('HLPNebulaBundle:Package')->searchByMetaAndTerm($meta, $term);
      
      $data = array();
      foreach($packages as $package)
      {
        $data[] = $package->getName();
      }
      
      return new JsonResponse(array_unique($data));
    }
    else
    {
      return $this->redirect('/');
    }
  }

  /**
   * Generate a ticket for direct communication with the Knossos server
   * 
   * @Security("has_role('ROLE_USER')")
   */
  public function generateTicketAction(Request $request)
  {
    $response = new Response($this->container->get('hlpnebula.knossos')->generateToken());
    $response->headers->set('Content-Type', 'text/plain');

    return $response;
  }

  public function searchIndexAction(Request $request)
  {
    $metas = $this->getDoctrine()->getManager()
      ->getRepository('HLPNebulaBundle:Meta')->findAll();

    $data = array();
    foreach($metas as $meta)
    {
      $desc = $meta->getDescription();
      $notes = $meta->getNotes();
      $tags = $meta->getKeywords();

      $data[] = array(
        'title' => $meta->getTitle(),
        'text'  => (empty($desc) ? '' : $desc . "\n") . (empty($notes) ? '' : $notes),
        'tags'  => empty($tags) ? '' : implode(' ', $meta->getKeywords()),
        'url'   => $this->generateUrl('hlp_nebula_repository_meta_overview', array('meta' => $meta->getMetaId()))
      );
    }

    return new JsonResponse(array('pages' => $data));
  }
}
