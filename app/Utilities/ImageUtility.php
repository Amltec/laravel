<?php

namespace App\Utilities;

/*
 * Classe de formatações de imagens
 */
class ImageUtility{

    /**
     * Resize para o padrão Thumbnail.
     * Ex: resize_crop_image(100, 100, "test.jpg", "test.jpg");
     * @param boolean $fit - se false corta a imagem se necessário para manter as dimensões exatas, se true ajusta as dimensões da imagem para para caber dentro do tamanho informado
     * Return [width_new,height_new]  OR  false
     * Obs: se a imagem for menor que o tamanho informado, não irá redimencionar e retorará a false
     */
    public static function resizeThumbnail($max_width, $max_height, $source_file, $dst_dir,$fit=true,$quality=80){
        $imgsize = getimagesize($source_file);
        $width = $imgsize[0];
        $height = $imgsize[1];
        $mime = $imgsize['mime'];

        //A imagem já está dentro das dimensões máximas, portanto não gera miniatura
        if($width<=$max_width && $height<=$max_height)return false;

        switch($mime){
            case 'image/gif':
                $image_create = "imagecreatefromgif";
                $image = "imagegif";
                $quality = null;
                break;

            case 'image/png':
                $image_create = "imagecreatefrompng";
                $image = "imagepng";
                $quality = 7;
                break;

            case 'image/jpeg':
                $image_create = "imagecreatefromjpeg";
                $image = "imagejpeg";
                $quality = 80;
                break;

            default:
                return false;
                break;
        }


        if($fit){//ajusta a nova largura e altura da imagem para caber dentro do tamanho informado
            if($width > $height){
                $thumb_w    =   $max_width;
                $thumb_h    =   $max_width*($height/$width);
            }
            if($width < $height){
                $thumb_w    =   $max_height*($width/$height);
                $thumb_h    =   $max_height;
            }
            if($width == $height){
                $thumb_w    =   $max_width;
                $thumb_h    =   $max_height;
            }
            $max_width = $thumb_w;
            $max_height = $thumb_h;


        }else{
            $thumb_w    = $max_width;
            $thumb_h    = $max_height;
        }
        //dd($image, $thumb_w, $thumb_h);

        $width_tmp = $height * $max_width / $max_height;
        $height_tmp = $width * $max_height / $max_width;


        $w_new = (int)$max_width; $h_new = (int)$max_height;
        $dst_dir = str_replace(['{width_new}','{height_new}'],[$w_new,$h_new],$dst_dir);//altera o nome do arquivo caso contenha as strings abaixo
        //dump($dst_dir);
        $dst_img = imagecreatetruecolor($thumb_w, $thumb_h);
        $src_img = $image_create($source_file);

        if($image=="imagepng"){
            imagealphablending($dst_img, false);
            imagesavealpha($dst_img, true);
            imagecolortransparent($dst_img);
        }

        if($width_tmp > $width){
            $h_point = (($height - $height_tmp) / 2);
            imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $max_width, $max_height, $width, $height_tmp);

        }else{
            $w_point = (($width - $width_tmp) / 2);
            imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $max_width, $max_height, $width_tmp, $height);
        }

        if($quality){
            $image($dst_img, $dst_dir, $quality);
        }else{
            $image($dst_img, $dst_dir);
        }

        if($dst_img)imagedestroy($dst_img);
        if($src_img)imagedestroy($src_img);

        return [$w_new,$h_new];
    }
}
