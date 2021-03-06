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

namespace HLP\NebulaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\Common\Collections\Criteria;

/**
 * Meta
 *
 * @ORM\Table(name="hlp_nebula_meta")
 * @ORM\Entity(repositoryClass="HLP\NebulaBundle\Entity\MetaRepository")
 * @UniqueEntity(fields={"metaId"}, ignoreNull=false, message="The meta ID must be unique.")
 */
class Meta
{
    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="metas")
     **/
    private $users;
    
    /**
     * @ORM\OneToMany(targetEntity="HLP\NebulaBundle\Entity\Author", mappedBy="meta", cascade={"remove"}, cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $authors;
    
    /**
     * @ORM\OneToOne(targetEntity="HLP\NebulaBundle\Entity\Logo", cascade={"persist","remove"})
     * @Assert\Valid
     */
    private $logo;
    
    /**
     * @ORM\OneToMany(targetEntity="HLP\NebulaBundle\Entity\Branch", mappedBy="meta", cascade={"remove"}, fetch="EXTRA_LAZY", indexBy="branchId")
     */
    private $branches;
    
    /**
     * @ORM\OneToMany(targetEntity="HLP\NebulaBundle\Entity\Build", mappedBy="meta", cascade={"remove"}, fetch="EXTRA_LAZY")
     */
    private $builds;
    
    /**
     * @ORM\ManyToMany(targetEntity="HLP\NebulaBundle\Entity\Category", cascade={"persist"})
     * @ORM\JoinTable(name="hlp_nebula_meta_category")
     */
    private $categories;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var  string
     *
     * @ORM\Column(name="type", type="string", length=30)
     * @Assert\NotBlank()
     * @Assert\Length(max=30)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="metaId", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     * @Assert\Regex("/^[-\w]+$/", message="Special characters not allowed in the meta ID.")
     * @Assert\Regex("/^-/", match=false, message="Dash not allowed at the beginning of the meta ID.")
     */
    private $metaId;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="firstRelease", type="date")
     * @Assert\Date()
     */
    private $firstRelease;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var array
     *
     * @ORM\Column(name="features", type="array")
     * @Assert\All({
     *     @Assert\NotBlank,
     *     @Assert\Length(max=255)
     * })
     */
    private $features;
    
    /**
     * @var array
     *
     * @ORM\Column(name="keywords", type="array")
     * @Assert\All({
     *     @Assert\Length(max=255)
     * })
     */
    private $keywords;

    /**
     * @var  string
     *
     * @ORM\Column(name="releaseThread", type="string", length=255, nullable=true)
     */
    private $releaseThread;

    /**
     * @var integer
     *
     * @ORM\Column(name="installCount", type="integer")
     */
    private $installCount = 0;

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
     * Set type
     *
     * @param string $type
     * @return Meta
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set metaId
     *
     * @param string $metaId
     * @return Meta
     */
    public function setMetaId($metaId)
    {
        $this->metaId = $metaId;

        return $this;
    }

    /**
     * Get metaId
     *
     * @return string 
     */
    public function getMetaId()
    {
        return $this->metaId;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return Meta
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string 
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set firstRelease
     *
     * @param \DateTime $firstRelease
     * @return Meta
     */
    public function setFirstRelease($firstRelease)
    {
        $this->firstRelease = $firstRelease;

        return $this;
    }

    /**
     * Get firstRelease
     *
     * @return \DateTime 
     */
    public function getFirstRelease()
    {
        return $this->firstRelease;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Meta
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Meta
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set features
     *
     * @param array $features
     * @return Meta
     */
    public function setFeatures($features)
    {
        $this->features = $features;

        return $this;
    }

    /**
     * Get features
     *
     * @return array 
     */
    public function getFeatures()
    {
        return $this->features;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->branches = new \Doctrine\Common\Collections\ArrayCollection();
        $this->authors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->firstRelease = new \Datetime();
        $this->nbBranches = 0;
        $this->nbBuilds = 0;
    }
    
    public function __toString()
    {
        return $this->metaId;
    }

    /**
     * Add branches
     *
     * @param \HLP\NebulaBundle\Entity\Branch $branches
     * @return Meta
     */
    public function addBranch(\HLP\NebulaBundle\Entity\Branch $branches)
    {
        $this->branches[] = $branches;
        $branches->setMeta($this);
        return $this;
    }

    /**
     * Remove branches
     *
     * @param \HLP\NebulaBundle\Entity\Branch $branches
     */
    public function removeBranch(\HLP\NebulaBundle\Entity\Branch $branches)
    {
        $this->branches->removeElement($branches);
    }

    /**
     * Get branches
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBranches()
    {
        return $this->branches;
    }

    /**
     * Get public branches
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPublicBranches()
    {
        $expr = Criteria::expr()->eq('public', true);
        $crit = Criteria::create()->where($expr);
        return $this->branches->matching($crit);
    }

    /**
     * Get the default branch (usually "master")
     *
     * @return \HLP\NebulaBundle\Entitiy\Branch
     */
    public function getDefaultBranch()
    {
        $expr = Criteria::expr()->eq('isDefault', true);
        $crit = Criteria::create()->where($expr);
        return $this->branches->matching($crit)->first();
    }
    
    /**
     * @Assert\Callback
     */
    public function forbiddenWords(ExecutionContextInterface $context)
    {
        $forbiddenWords = Array('metas','profile','activity');
      
        if(in_array($this->metaId, $forbiddenWords)) {
            $context->addViolationAt(
                'metaId',
                'meta ID is a forbidden word ("'.$this->metaId.'") !',
                array(),
                null
            );
        }
    }

    /**
     * Add authors
     *
     * @param \HLP\NebulaBundle\Entity\Author $authors
     * @return Meta
     */
    public function addAuthor(\HLP\NebulaBundle\Entity\Author $author)
    {
        $this->authors[] = $author;
        $author->setMeta($this);
        return $this;
    }

    /**
     * Remove authors
     *
     * @param \HLP\NebulaBundle\Entity\Author $authors
     */
    public function removeAuthor(\HLP\NebulaBundle\Entity\Author $author)
    {
        $this->authors->removeElement($author);
        $author->setMeta(null);
    }

    /**
     * Get authors
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * Set keywords
     *
     * @param array $keywords
     * @return Meta
     */
    public function setKeywords($keywords)
    {
        $this->keywords = explode(',', $keywords);
        
        foreach($this->keywords as $key => $keyword)
        {
            $this->keywords[$key] = trim($keyword);
        }
        
        $this->keywords = array_filter($this->keywords);
        
        return $this;
    }

    /**
     * Get keywords
     *
     * @return array 
     */
    public function getKeywords()
    {
        $keywordsStr = '';
        if(isset($this->keywords))
        {
            foreach($this->keywords as $keyword)
            {
                $keywordsStr .= $keyword.', ';
            }
        }
        return $keywordsStr;
    }

    /**
     * Set release thread
     *
     * @param string
     * @return Meta
     */
    public function setReleaseThread($value)
    {
        $this->releaseThread = $value;
        return $this;
    }

    /**
     * Get release thread
     *
     * @return string
     */
    public function getReleaseThread()
    {
        return $this->releaseThread;
    }

    /**
     * Get the install count
     *
     * @return integer
     */
    public function getInstallCount()
    {
        return $this->installCount;
    }

    /**
     * Add categories
     *
     * @param \HLP\NebulaBundle\Entity\Category $categories
     * @return Meta
     */
    public function addCategory(\HLP\NebulaBundle\Entity\Category $categories)
    {
        $this->categories[] = $categories;

        return $this;
    }

    /**
     * Remove categories
     *
     * @param \HLP\NebulaBundle\Entity\Category $categories
     */
    public function removeCategory(\HLP\NebulaBundle\Entity\Category $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set logo
     *
     * @param \HLP\NebulaBundle\Entity\Logo $logo
     * @return Meta
     */
    public function setLogo(\HLP\NebulaBundle\Entity\Logo $logo = null)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return \HLP\NebulaBundle\Entity\Logo 
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Add users
     *
     * @param \HLP\NebulaBundle\Entity\User $users
     * @return Meta
     */
    public function addUser(\HLP\NebulaBundle\Entity\User $user)
    {
        $this->users[] = $user;
        $user->addMeta($this);
        return $this;
    }

    /**
     * Remove users
     *
     * @param \HLP\NebulaBundle\Entity\User $users
     */
    public function removeUser(\HLP\NebulaBundle\Entity\User $user)
    {
        $this->users->removeElement($user);
        $user->removeMeta($this);
        return $this;
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set nbBranches
     *
     * @param integer $nbBranches
     * @return Meta
     */
    public function setNbBranches($nbBranches)
    {
        $this->nbBranches = $nbBranches;

        return $this;
    }

    /**
     * Get nbBranches
     *
     * @return integer 
     */
    public function getNbBranches()
    {
        return $this->branches->count();
    }

    /**
     * Set nbBuilds
     *
     * @param integer $nbBuilds
     * @return Meta
     */
    public function setNbBuilds($nbBuilds)
    {
        $this->nbBuilds = $nbBuilds;

        return $this;
    }

    /**
     * Get nbBuilds
     *
     * @return integer 
     */
    public function getNbBuilds()
    {
        return $this->builds->count();
    }

    /**
     * Add builds
     *
     * @param \HLP\NebulaBundle\Entity\Build $builds
     * @return Meta
     */
    public function addBuild(\HLP\NebulaBundle\Entity\Build $builds)
    {
        $this->builds[] = $builds;
        $builds->setMeta($this);
        return $this;
    }

    /**
     * Remove builds
     *
     * @param \HLP\NebulaBundle\Entity\Build $builds
     */
    public function removeBuild(\HLP\NebulaBundle\Entity\Build $builds)
    {
        $this->builds->removeElement($builds);
    }

    /**
     * Get builds
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBuilds()
    {
        return $this->builds;
    }
}
