<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class ImageController extends BaseController
{
    public function getImageUrl($image, $path)
    {
        if ($image != null) {
            $image_url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . '/' . 'images/' . $path . $image;
        } else {
            $image_url = null;
        }
        return $image_url;
    }

    public function getImagesUrl($results, $path, $index)
    {
        foreach ($results as $key => $value) {
            $image = $this->verifyImage($value[$index], $path);
            $results[$key]["image_url"] = $this->getImageUrl($image, $path);
        }
        return $results;
    }

    private function verifyImage($image, $path)
    {
        $path_url = 'images/' . $path . $image;
        if ($image == '') {
            $image = null;
        }
        return $image;
    }
}
