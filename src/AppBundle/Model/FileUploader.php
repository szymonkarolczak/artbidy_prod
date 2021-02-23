<?php

namespace AppBundle\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    
    public function upload(UploadedFile $file, $targetDir)
    {
        $fileName = md5(uniqid('', true)).'.'.$file->guessExtension();

        $file->move($targetDir, $fileName);

        return $fileName;
    }
}