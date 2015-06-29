<?php

namespace HLP\NebulaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ngld\CommonBundle\DependencyInjection\ContainerRef;

/**
 * Logo
 *
 * @ORM\Table(name="hlp_nebula_logo")
 * @ORM\Entity(repositoryClass="HLP\NebulaBundle\Entity\LogoRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Logo
{
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
     * @ORM\Column(name="ext", type="string", length=10)
     */
    private $ext;

    /**
     * @Assert\File(
     *     maxSize = "1024k"
     * )
     * @Assert\Image(
     *     minWidth = 255,
     *     maxWidth = 255,
     *     minHeight = 112,
     *     maxHeight = 112
     * )
     */
    private $file;
    private $_oldFile;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function getFile()
    {
        return $this->file;
    }
    
    public function setFile(UploadedFile $file)
    {
        if (is_file($this->getAbsolutePath())) {
            $this->_oldFile = $this->getAbsolutePath();
        }

        $this->file = $file;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->file) {
            $this->ext = $this->file->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }

        if (isset($this->_oldFile)) {
            unlink($this->_oldFile);
            $this->_oldFile = null;
        }

        $this->file->move(
            $this->getUploadRootDir(),
            $this->id . '.' . $this->ext
        );
        $this->file = null;
    }

    /**
     * @ORM\PreRemove()
     */
    public function rememberFilename()
    {
        $this->_oldFile = $this->getAbsolutePath();
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if (isset($this->_oldFile)) {
            unlink($this->_oldFile);
        }
    }

    public function getUploadDir()
    {
        return 'uploads/img';
    }

    protected function getUploadRootDir()
    {
        return ContainerRef::get()->getParameter('web_path') . '/' . $this->getUploadDir();
    }

    public function getAbsolutePath()
    {
        return $this->getUploadRootDir() . '/' . $this->id . '.' . $this->ext;
    }

    public function getWebPath()
    {
        return ContainerRef::get()->get('templating.helper.assets')->getUrl($this->getUploadDir() . '/' . $this->id . '.' . $this->ext);
    }
}
