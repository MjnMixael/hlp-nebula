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

namespace HLP\NebulaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * File
 *
 * @ORM\Table(name="hlp_nebula_executable")
 * // @ORM\Entity(repositoryClass="HLP\NebulaBundle\Entity\ExecutableRepository")
 */
class Executable
{
    /**
     * @ORM\ManyToOne(targetEntity="HLP\NebulaBundle\Entity\Package", inversedBy="files")
     * @ORM\JoinColumn(nullable=false)
     */
    private $package;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=100)
     * @Assert\Length(max=255)
     * @Assert\Regex(
     *     pattern="/^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z-\.]+)?(\+[0-9A-Za-z-\.]+)?$/",
     *     message="This is not a valid version number."
     * )
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="file", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     * @Assert\Regex(
     *     pattern="/^([\\\/]?[^\0\\\/:\*\?\x22<>\|]+)*[\\\/]?$/",
     *     message="The file path must be a valid relative path."
     * )
     */
    private $file;

    /**
     * @var boolean
     *
     * @ORM\Column(name="debug", type="boolean")
     */
    private $debug;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return Executable
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set file
     *
     * @param string $file
     * @return Executable
     */
    public function setFile($file)
    {
        $this->file = trim($file, '/\\');

        return $this;
    }

    /**
     * Get file
     *
     * @return string 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set debug
     *
     * @param boolean $debug
     * @return File
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Get debug
     *
     * @return boolean 
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set package
     *
     * @param \HLP\NebulaBundle\Entity\Package $package
     * @return File
     */
    public function setPackage(\HLP\NebulaBundle\Entity\Package $package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \HLP\NebulaBundle\Entity\Package 
     */
    public function getPackage()
    {
        return $this->package;
    }
    
    public function __clone()
    {
         if ($this->id) {
            $this->id = null;
         }
    }
}
