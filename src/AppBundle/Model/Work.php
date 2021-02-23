<?php

namespace AppBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use AppBundle\Model\FileUploader;

class Work 
{
    
    private $uploader;
    private $directory;
    
    public function __construct(FileUploader $uploader, $directory)
    {
        $this->uploader = $uploader;
        $this->directory = $directory;
    }
    
    public function parse(\AppBundle\Entity\Work $work)
    {
        $gallery = $work->getGallery();
        $galleries = new ArrayCollection();
        if($gallery)
        {
            foreach($gallery as $file)
            {
                if($file instanceof UploadedFile)
                {
                    $fileName = $this->uploader->upload($file, $this->directory);
                    $galleries->add($fileName);
                }
            }
        }
        $work->setGallery($galleries->toArray());

        $file = $work->getImage();
        if($file instanceof UploadedFile)
        {
            $fileName = $this->uploader->upload($file, $this->directory);
            $work->setImage($fileName);
        }
        return $work;
    }
}
