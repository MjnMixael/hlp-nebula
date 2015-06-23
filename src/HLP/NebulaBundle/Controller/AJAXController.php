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
use Symfony\Component\HttpFoundation\JsonResponse;

class AJAXController extends Controller
{
  public function searchModsAction(Request $request)
  {
    if($request->isXmlHttpRequest())
    {
      $term = $request->query->get('term');
      $data = array();

      if(!empty($term))
      {
        $metas = $this->getDoctrine()->getManager()
          ->getRepository('HLPNebulaBundle:Meta')->searchMetas($term);

        foreach($mods as $mod)
        {
          $data[] = $mod->getModId();
        }
      }
      
      $response = new JsonResponse();
      $response->setData($data);
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }
    else
    {
      return $this->redirect('/');
    }
  }
  
  public function searchPackagesInModAction(Request $request, $meta)
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
      
      $response = new JsonResponse();
      $response->setData(array_unique($data));
      $response->headers->set('Content-Type', 'application/json');

      return $response;
    }
    else
    {
      return $this->redirect('/');
    }
  }
}
