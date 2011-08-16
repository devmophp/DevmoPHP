<?php
/**
 * Primary tool used for image file uploads,
 * manipulations, and renamings.
 *
 * @category Utility
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
class Image {
  private $images;

 



  /**
   * method description
   *
   * @param
   * @return
   */
  public function __construct () {
    $this->images = array();
  }




  /**
   * method description
   *
   * @param
   * @return
   */
  public function image ($index=NULL, $name=NULL, $dir=NULL, $size=NULL, $set=FALSE) {
    return ($index && ($name || $size || $set))
      ? $this->images[$index] = array('name'=>$name, 'size'=>$size, 'dir'=>$dir)
      : $index ? getVal($index,$this->images) : $this->images;
  }




  /**
   * method description
   *
   * @param
   * @return
   */
  public function upload ($field) {
    //  load dependencies
    $info = Factory::getLib('info');
    $file = Request::getFile($field);
    
    // data checks level 1
    if (!$field) { $err=1; $info->set('err','File field name is missing'); return FALSE; }
    if (!$file || !$file['size']) return TRUE;

     
    //  data initializations
    $imgMode  = 0666;
    $imgMax   = '2000000';
    $fileName = getVal('name',$file);
    $fileTmp  = getVal('tmp_name',$file);
    $fileSize = getVal('size',$file);
    $fileType = getVal('type',$file);

    // data checks level 3
    switch (getVal('error',$file)) {
      case UPLOAD_ERR_OK:
        if (!is_uploaded_file($file['tmp_name']))
          throw new Error("File {$file['name']} was not uploaded with HTTP");
        else if (!$file['size'])
          throw new Error("File {$file['name']} is empty");
        break;
      case UPLOAD_ERR_INI_SIZE: case UPLOAD_ERR_FORM_SIZE:
        throw new Error("File {$file['name']} exceeds the max file size");
        break;
      case UPLOAD_ERR_PARTIAL:
        throw new Error("File {$file['name']} was partially uploaded");
        break;
      case UPLOAD_ERR_NO_FILE:
        throw new Error("File {$file['name']} was not uploaded");
        break;
    }

    if (!stristr($fileType,'jpeg')) {
      $info->set('err',"Only JPEG images are accepted");  

    } else if (!$fileSize) {
      $info->set('err',"File was not uploaded");  

    } else {
      //  upload file
      $image = $fileTmp;

      //  determine image size
      $sizes = getimagesize($image);
      $w  = ceil($sizes[0]);
      $h  = ceil($sizes[1]);
      $r = $sizes[1] / $sizes[0];

      foreach ($this->images as $img) {
        //  initialize padding and sizes
        $padH = $padV = 0;
        
        //  calculate dimensions
        if ($w < $img['size'] && $y < $img['size']) {
          $padH = ceil(($img['size']-$h)/2);
          $padV = ceil(($img['size']-$w)/2);
        
        //  wider
        } else if ($r < 1) {
          $padH = ceil(($w - $h) / 2);
        
        //  taller
        } else if ($r > 1) {
          $padV = ceil(($h - $w) / 2);
        
        //  square
        }
        
        //  determine upload directory
        $dir = Path::getDir('img',$img['dir']);
        if (!is_dir($dir))
          throw new Error("File path [{$dir}] DNE");

        //  create image
        $params = '-background "#ffffff" '
                . "-gravity north -splice 0x{$padH} "
                . "-gravity south -splice 0x{$padH} "
                #. "-gravity northwest -splice {$padV}x0 "
                #. "-gravity northeast -splice {$padV}x0 "
                . "-resize {$img['size']}x{$img['size']}";
        system(MGKDIR."/convert {$image} {$params} {$dir}{$img['name']}",$rtn);
        if ($rtn > 0) $info->set('err',"The image [{$file['name']}] was not resized to {$w}x{$h}");
      }
      
      if (file_exists($image))
        unlink($image) or $info->set('err',"The temporary file [{$file['name']}] was not removed");
   }

    return $info->have('err') ? FALSE : TRUE;
  }

} //  EOC
?>
