<?php

namespace Djerrah\FileUploaderBundle\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Djerrah\CoreBundle\Model\Is;
use Djerrah\CoreBundle\Model\Stub;
use Doctrine\Common\Annotations\Annotation;

/**
 * Djerrah\CoreBundle\Entity\File
 * @ORM\Entity(repositoryClass="Djerrah\FileUploaderBundle\Repository\FileRepository")
 * @ORM\Table(name="uploader_file")
 *
 * @ORM\HasLifecycleCallbacks
 */
class File
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    protected $id;

    /**
     * @var string $name
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string $description
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string $tags
     * @ORM\Column(name="tags", type="text", nullable=true)
     */
    private $tags;

    /**
     * @var string $logo_url
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string $upload_dir
     */
    private $uploadDir;

    /**
     * @var string $slug
     * @ORM\Column(name="slug", type="string", length=255, nullable=true, unique=true)
     * @Gedmo\Slug(fields={"name", "name"})
     */
    private $slug;

    /**
     * @var string $type
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @Assert\File(
     *          maxSize="512M",
     *          mimeTypes={"image/jpeg","image/gif","image/png","image/x-png"},
     *          mimeTypesMessage="erreur : le mimeType ({{ type }}) n'est aps accepter ({{ types }})",
     *          groups={"image"}
     *  )
     *
     * @Assert\File(
     *          maxSize="512M",
     *          mimeTypes={"audio/x-aiff","audio/mpeg"},
     *          mimeTypesMessage="erreur : le mimeType ({{ type }}) n'est aps accepter ({{ types }})",
     *          groups={"audio"}
     *  )
     *
     * @Assert\File(
     *          maxSize="512M",
     *          mimeTypes={"video/mp4"},
     *          mimeTypesMessage="erreur : le mimeType ({{ type }}) n'est aps accepter ({{ types }})",
     *          groups={"video"}
     *  )
     *
     * @Assert\File(
     *          maxSize="512M",
     *          mimeTypes={"image/jpeg","image/gif","image/png","image/x-png","audio/x-aiff","audio/mpeg"},
     *          mimeTypesMessage="erreur : le mimeType ({{ type }}) n'est aps accepter ({{ types }})",
     *          groups={"questionnaireFiles"}
     *  )
     *
     * @Assert\File(
     *          maxSize="512M",
     *          mimeTypes={
     *                  "application/vnd.ms-excel","application/x-xls","application/xls","application/x-dos_ms_excel",
     *                  "application/x-excel","application/x-ms-excel","application/msexcel","application/x-msexcel",
     *                  "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
     *          },
     *          mimeTypesMessage="erreur : le mimeType ({{ type }}) n'est aps accepter ({{ types }})",
     *          groups={"excel"}
     *  )
     */
    private $file;


    /**
     * @var string $size
     * @ORM\Column(name="size", type="integer", nullable=true)
     */
    private $size;

    /**
     * @var string $tempFilename
     */
    private $tempFilename;

    /**
     * @var bool $removeFile
     */
    private $removeFile = false;

    /**
     * @var \Datetime $createdAt
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \Datetime $updatedAt
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    public function __construct($folder = "uploads/files")
    {
        $this->setUploadDir($folder);
    }


    /**
     * GET  Id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return \Djerrah\CoreBundle\Entity\File
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $description
     *
     * @return \Djerrah\CoreBundle\Entity\File
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $tags
     *
     * @return \Djerrah\CoreBundle\Entity\File
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }


    public function __toString()
    {
        return (string)(($this->id != null) ? $this->name : 'New File');
    }


    /**
     * Set url
     *
     * @param string $url
     *
     * @return \Djerrah\CoreBundle\Entity\File
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return \Djerrah\CoreBundle\Entity\File
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $uploadDir
     *
     * @return \Djerrah\CoreBundle\Entity\File
     */
    public function setUploadDir($uploadDir)
    {
        if ($uploadDir[0] == '/') {
            $uploadDir = substr($uploadDir, 1);
        }

        if ($uploadDir[strlen($uploadDir) - 1] != '/') {
            $uploadDir .= "/";
        }

        $rootDire = $this->getUploadRootDir() . $uploadDir;

        if (!is_dir($rootDire)) {
            mkdir($rootDire, 0777, true);
        }

        $this->uploadDir = $uploadDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        return $this->uploadDir;
    }

    /**
     * @param string $type
     *
     * @return \Djerrah\CoreBundle\Entity\File
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $size
     *
     * @return \Djerrah\CoreBundle\Entity\File
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
        if (null !== $this->url) {
            $this->tempFilename = $this->url;
            $this->url          = null;
        }
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getRemoveFile()
    {
        return $this->removeFile;
    }

    public function setRemoveFile($removeFile = false)
    {
        if ($removeFile && null !== $this->url) {
            if (file_exists($this->url)) {
                unlink($this->url);
            }
            $this->description = null;
            $this->type        = null;
            $this->size        = null;
            $this->name        = null;
            $this->url         = null;
        }

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpload(LifecycleEventArgs $event)
    {

        if (null === $this->file) {
            return;
        }

        #$this->url =  uniqid() . '.' . $this->file->guessExtension();
        $this->url  = $this->getUploadDir() . $this->file->getClientOriginalName();
        $this->type = $this->file->getClientMimeType();
        $this->size = $this->file->getClientSize();

        if (null === $this->name) {
            $this->name = $this->file->getClientOriginalName();
        }
    }

    /**
     * @ORM\PostPersist
     * @ORM\PostUpdate
     */
    public function upload()
    {

        if (null === $this->file) {
            return;
        }

        $this->removeOldUploadFileIfExist();

        try {
            $this->file->move(
                $this->getUploadRootDir() . $this->getUploadDir(),
                $this->file->getClientOriginalName()
            );
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad(LifecycleEventArgs $event)
    {
    }

    /**
     * @ORM\PreRemove
     */
    public function preRemoveUpload()
    {
        $this->tempFilename = $this->getUploadRootDir() . $this->url;
    }

    /**
     * @ORM\PostRemove
     */
    public function removeUploadFile()
    {
        if (null !== $this->url) {

            if(file_exists($this->url)){
                unlink($this->url);
            }

            /*
            $info      = pathinfo($this->url);
            $directory = $this->getUploadRootDir() . $info['dirname'];
            if (is_dir($directory)) {
                $this->delTree($directory);
            }
            */
        }
    }

    /**
     *
     */
    private function removeOldUploadFileIfExist()
    {
        if (null !== $this->tempFilename) {
            $oldFile = $this->getUploadRootDir() . $this->tempFilename;

            if (file_exists($oldFile)) {
                unlink($oldFile);
            }

            $info      = pathinfo($this->tempFilename);
            $cacheFile = $this->getUploadRootDir()
                . $info['dirname'] . DIRECTORY_SEPARATOR
                . 'cache' . DIRECTORY_SEPARATOR . $info['filename'];
            if (is_dir($cacheFile)) {
                $this->delTree($cacheFile);
            }
        }
    }

    /**
     * @return string
     */
    public function getUploadRootDir()
    {
        return realpath(__DIR__ . '/../../../../web/') . '/';
    }

    /**
     * @return string
     */
    public function getWebPath()
    {
        return $this->getUrl();
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        $type = '';

        switch ($this->type) {
            case "application/vnd.ms-excel":
            case "application/x-xls":
            case "application/xls":
            case "application/x-dos_ms_excel":
            case "application/x-excel":
            case "application/x-ms-excel":
            case "application/msexcel":
            case "application/x-msexcel":
            case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
                $type = 'csv';
                break;
            case "application/pdf":
                $type = 'pdf';
                break;
            case "image/png":
            case "image/gif":
            case "image/jpeg":
                $type = 'image';
                break;
            case "audio/x-aiff":
            case "audio/mpeg":
                $type = 'audio';
                break;
            case "video/mp4":
                $type = 'video';
                break;
            case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
            case "application/msword":
                $type = 'word';
                break;
            case "application/vnd.openxmlformats-officedocument.presentationml.presentation":
            case "application/vnd.ms-powerpoint":
                $type = 'powerpoint';
                break;
            default:
                $this->type;
                break;
        }

        return $type;
    }


    /**
     * @return string
     */
    public function getIconPath()
    {
        $url = $this->getUrl();

        switch ($this->getMimeType()) {
            case "csv":
                $url = '/bundles/core/images/csv_icon.png';
                break;
            case "pdf":
                $url = '/bundles/core/images/pdf.jpg';
                break;
            case "audio":
                $url = '/bundles/core/images/audio.png';
                break;
            case "video":
                $url = '/bundles/core/images/video.png';
                break;
            case "word":
                $url = '/bundles/core/images/word.png';
                break;
            case "powerpoint":
                return '/bundles/core/images/powerpoint.png';
                break;
            case "image":
                $url = $this->url;
                break;
            default:
                # $url = '/bundles/core/images/file.png';
                break;
        }

        return $url;
    }

    /**
     * @param $dir
     *
     * @return bool
     */
    private function delTree($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }

    /**
     * GET  CreatedAt
     *
     * @return \Datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * SET CreatedAt
     *
     * @param \Datetime $createdAt
     *
     * @return File
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * GET  UpdatedAt
     *
     * @return \Datetime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * SET UpdatedAt
     *
     * @param \Datetime $updatedAt
     *
     * @return File
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

}
