<?php
DEFINE('UPLOADED_IMAGE_DESTINATION', './MES_IMAGES/');
DEFINE('THUMBNAIL_IMAGE_DESTINATION', './AVATARS/');
DEFINE('avatarMaxSize', 150); // pixels
ini_set('post_max_size', '1025M');
ini_set('upload_max_filesize', '1024M');
$id = $_POST['id'];
$allAvatars = glob("./AVATARS/$id.*");


if (count($allAvatars) == 0) {
    echo "Il n'existe pas encore de fichier dont le nom est '$id' : <br>";
    //echo "<a href='./AVATARS/unknown.png'>unknown.png</a><br />";
    echo "<img src='./AVATARS/unknown.png'>";
}
else {
    echo "Liste des fichiers avant sauvegarde dont le nom est '$id' : <br>";
    foreach ($allAvatars as $filename) {
        if($fp = fopen($filename,"rb", false)) //ouvre en read only (b est pour des fichiers non texte)
        {
            $image = fread($fp,filesize($filename));
            fclose($fp);
            //base64 encode l'image en binaire, puis je la casse en morceaux (chunks) en respectant la semantique RFC 2045
            $base64 = chunk_split(base64_encode($image));
            $url = "<img src='data:image/gif;base64," . $base64 . "'";
            echo $url;
            echo "<br>";
        }
        //echo "<a href='$filename'>$filename</a><br />";
        //echo "<img src='$filename'>";
    }
}

$file = $_FILES['datafile']['name'];
$dossier ='./AVATARS/';
$uploadIsOk = true;
$typeImage = pathinfo($dossier.$file,PATHINFO_EXTENSION);
$typeImage = strtolower($typeImage);

if($typeImage != "jpg" && $typeImage != "png" && $typeImage != "jpeg" && $typeImage != "gif") {
    echo "<br>Seul les formats JPG, JPEG, PNG et GIF sont supportés";
    $uploadIsOk = false;
}

if ($uploadIsOk) {
    $result = process_image_upload('datafile');
    if ($result === false) {
        echo "<br>Attaque potentielle par téléchargement de fichiers.";
    } else {
        echo "<br>Le fichier est valide, et a été téléchargé avec succès.";
        echo '<br>Image sauvegardée en tant que ' . $result[0];
        echo '<br>Avatar sauvegardé en tant que ' . $result[1];
        //$img1 = "<img src='$result[0]'>"; //image in real size
        $img2 = "<img src='$result[1]'>"; // image in avatar size
        //$link1 = "<a href='$result[0]'>$file</a><br />";
        //$link2 = "<a href='$result[1]'>'thumbnail'</a><br />";
        echo '<br>';
        //echo $img1;
        echo '<br>';
        echo $img2;
        echo '<br>';
    }
    if (move_uploaded_file($_FILES['datafile']['tmp_name'], "$dossier/$file")) {
        echo "Le fichier est valide, et a été téléchargé avec succès.\n";
        $link = "<a href='$dossier/$file'>$file</a><br />";
        echo $link;

    }
}

function generateAvatar($source , $destination) {
    list($source_image_width, $source_image_height, $source_image_type) = getimagesize($source);
    switch ($source_image_type) {
        case IMAGETYPE_GIF:
            $source_gd_image = imagecreatefromgif($source);
            break;
        case IMAGETYPE_JPEG:
            $source_gd_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $source_gd_image = imagecreatefrompng($source);
            break;
        default:
            $source_gd_image = imagecreatefromjpeg($source);
            break;
    }
    if ($source_gd_image === false) {
        return false;
    }
    $source_aspect_ratio = $source_image_width / $source_image_height;
    $thumbnail_aspect_ratio = avatarMaxSize / avatarMaxSize;
    if ($source_image_width <= avatarMaxSize && $source_image_height <= avatarMaxSize) {
        $thumbnail_image_width = $source_image_width;
        $thumbnail_image_height = $source_image_height;
    } elseif ($thumbnail_aspect_ratio > $source_aspect_ratio) {
        $thumbnail_image_width = (int) (avatarMaxSize * $source_aspect_ratio);
        $thumbnail_image_height = avatarMaxSize;
    } else {
        $thumbnail_image_width = avatarMaxSize;
        $thumbnail_image_height = (int) (avatarMaxSize / $source_aspect_ratio);
    }
    $thumbnail_gd_image = imagecreatetruecolor($thumbnail_image_width, $thumbnail_image_height);
    imagecopyresampled($thumbnail_gd_image, $source_gd_image, 0, 0, 0, 0, $thumbnail_image_width, $thumbnail_image_height, $source_image_width, $source_image_height);
    imagejpeg($thumbnail_gd_image, $destination, 90);
    imagedestroy($source_gd_image);
    imagedestroy($thumbnail_gd_image);
    return true;
}

/*
 * Uploaded file processing function
 */

function process_image_upload($field)
{
    $temp_image_path = $_FILES[$field]['tmp_name'];
    $temp_image_name = $_FILES[$field]['name'];
    $image_extension = $type=substr($temp_image_name,strrpos($temp_image_name,'.')+0);
    $temp_avatar_id_name = $_POST['id'].$image_extension;
    list(, , $temp_image_type) = getimagesize($temp_image_path);
    if ($temp_image_type === NULL) {
        return false;
    }
    switch ($temp_image_type) {
        case IMAGETYPE_GIF:
            break;
        case IMAGETYPE_JPEG:
            break;
        case IMAGETYPE_PNG:
            break;
        default:
            return false;
    }
    $uploaded_image_path = UPLOADED_IMAGE_DESTINATION . $temp_image_name;
    move_uploaded_file($temp_image_path, $uploaded_image_path);
    $thumbnail_image_path = THUMBNAIL_IMAGE_DESTINATION . $temp_avatar_id_name;
        //preg_replace('{\\.[^\\.]+$}', '.jpg', $temp_avatar_id_name);
    $result = generateAvatar($uploaded_image_path, $thumbnail_image_path);
    return $result ? array($uploaded_image_path, $thumbnail_image_path) : false;
}