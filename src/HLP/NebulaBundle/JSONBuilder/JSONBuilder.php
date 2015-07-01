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

// src/HLP/NebulaBundle/JSONBuilder/JSONBuilder.php

namespace HLP\NebulaBundle\JSONBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

class JSONBuilder
{
    /**
    * @param object $build
    * @return array
    */
    public function createFromBuild(\HLP\NebulaBundle\Entity\Build $build, $finalise = true)
    {
        $branch = $build->getBranch();
        $meta = $branch->getMeta();

        $data = Array();
        $data['type'] = $meta->getType();
        $data['id'] = $meta->getMetaId();
        $data['title'] = $meta->getTitle();
        $data['version'] = $build->getVersion();

        if($meta->getDescription()) {
            $data['description'] = $meta->getDescription();
        }

        if(($logo = $meta->getLogo())) {
            $host = sprintf('http%s://%s/',
                isset($_SERVER['HTTPS']) ? 's' : '',
                isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'
            );
            $data['logo'] = $host . $logo->getWebPath();
        }

        if($build->getNotes() || $meta->getNotes()) {
            $data['notes'] = trim($meta->getNotes() . "\n\n" . $build->getNotes());
        }

        if($build->getFolder()) {
            $data['folder'] = $build->getFolder();
        }

        if(count($build->getPackages()) > 0) {
            $data['packages'] = Array();

            foreach ($build->getPackages() as $package) {
                $pkg = array(
                    'name'   => $package->getName(),
                    'status' => $package->getStatus()
                );
                
                if($package->getNotes()) {
                    $pkg['notes'] = $package->getNotes();
                }

                if(count($package->getDependencies()) > 0) {
                    $pkg['dependencies'] = array();

                    foreach ($package->getDependencies() as $dependency) {
                        $d = array(
                            'id'      => $dependency->getDepId(),
                            'version' => $dependency->getVersion()
                        );

                        if(count($dependency->getDepPkgs()) > 0) {
                            $d['packages'] = $dependency->getDepPkgs();
                        }

                        $pkg['dependencies'][] = $d;
                    }
                }

                if(count($package->getEnvVars()) > 0) {
                    $pkg['environment'] = Array();

                    foreach ($package->getEnvVars() as $envVar) {
                        $pkg['environment'][] = array(
                            'type' => $envVar->getType(),
                            'value' => $envVar->getValue()
                        );
                    }
                }

                if(count($package->getExecutables()) > 0) {
                    $pkg['executables'] = array();

                    foreach ($package->getExecutables() as $exe) {
                        $pkg['executables'][] = array(
                            'version' => $exe->getVersion(),
                            'file' => $exe->getFile(),
                            'debug' => $exe->getDebug()
                        );
                    }
                }

                if(count($package->getFiles()) > 0) {
                    $pkg['files'] = Array();

                    foreach ($package->getFiles() as $file) {
                        $f = array(
                            'filename' => $file->getFilename(),
                            'is_archive' => $file->getIsArchive(),
                            'urls' => $file->getUrls()
                        );

                        if($file->getDest()) {
                            $f['dest'] = $file->getDest();
                        }

                        $pkg['files'][] = $f;
                    }
                }

                $data['packages'][] = $pkg;
            }
        }

        if(count($build->getActions()) > 0) {
            $data['actions'] = Array();

            foreach ($build->getActions() as $i => $action) {
                $act = Array(
                    'type' => $action->getType(),
                    'paths' => $action->getPaths(),
                    'glob' => $action->getGlob()
                );

                if('move' == $action->getType()) {
                    $act['dest'] = $action->getDest();
                }

                $data['actions'][] = $act;
            }
        }

        if($finalise)
        {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        else
        {
            return $data;
        }
    }
}
