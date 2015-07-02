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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\Event\PostFlushEventArgs;

use HLP\NebulaBundle\Entity\Build;
use HLP\NebulaBundle\Entity\Branch;

class JSONBuilder extends ContainerAware
{
    protected static $changedPrivateBranches = array();
    protected static $publicBranchChanged = false;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
    * @param object $build
    * @return array
    */
    public function createFromBuild(Build $build, $finalise = true)
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
            $host = sprintf('http%s://%s',
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

    protected function _rebuildRepo($path, $branch_collection)
    {
        $lock = $path . '.lock';
        if (file_exists($lock)) {
            // Abort!
            return;
        }

        file_put_contents($lock, '');

        $data = '';
        foreach ($branch_collection as $branch) {
            foreach($branch->getBuilds() as $build) {
                $bd = $build->getGeneratedJSON();
                $start = strpos($bd, '"mods":[') + 8;
                $end = strrpos($bd, ']');
                $data .= ',' . substr($bd, $start, $end - $start);
            }
        }

        file_put_contents($path, '{"mods":[' . substr($data, 1) . ']}');
        unlink($lock);
    }

    protected function _privateRepoPath($branch)
    {
        $path = realpath($this->container->getParameter('web_path') . '/privrepo') . '/';
        $sub_path = $branch->getMeta()->getMetaId() . '/' . $branch->getBranchId();
        $sub_path .= '_' . $branch->getPrivateKey() . '.json';

        // Paranoid path check.
        $fs = new Filesystem();
        $full_path = $path . $sub_path;
        $rel_path = rtrim($fs->makePathRelative($full_path, $path), '/');

        if ($rel_path != $sub_path || strpos($rel_path, '../') !== false) {
            var_dump([$path, $full_path, $rel_path]);
            throw new AccessDeniedException('Weird paths!');
        }

        return $full_path;
    }

    public function rebuildPublicRepo()
    {
        $branches = $this->container->get('doctrine')->getManager()
            ->getRepository('HLPNebulaBundle:Branch')->getDefaultBranches();
        $path = $this->container->getParameter('web_path') . '/repo/public.json';

        $this->_rebuildRepo($path, $branches);
    }

    public function rebuildPrivateRepo($branch)
    {

        $path = $this->_privateRepoPath($branch);
        $fs = new Filesystem();

        $fs->mkdir(dirname($path));
        $this->_rebuildRepo($path, array($branch));
    }

    public function markBranchAsChanged($branch)
    {
        if ($branch->isPublic()) {
            if ($branch->getIsDefault()) {
                self::$publicBranchChanged = true;
            }
        } else if (!in_array($branch, self::$changedPrivateBranches)) {
            self::$changedPrivateBranches[] = $branch;
        }
    }

    public function removeBranch($branch)
    {
        if (!$branch->isPublic()) {
            $path = $this->_privateRepoPath($branch);

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        foreach (self::$changedPrivateBranches as $branch) {
            $this->rebuildPrivateRepo($branch);
        }

        if (self::$publicBranchChanged) {
            $this->rebuildPublicRepo();
        }

        self::$changedPrivateBranches = array();
        self::$publicBranchChanged = false;
    }
}
