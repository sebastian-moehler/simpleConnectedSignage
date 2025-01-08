<?php
/**
 * gives a list with the urls of the images in the separate folder.
 * I would like to do that with js, but there seems to be no easy way
 */
    $config = null;

    try {
        $config = json_decode(file_get_contents('list.json'), true);
    } catch (Exception $err) {
        // do nothing
        $config = null;
    }

    if($config == null || ($config['img-folder'] ?? "") == "") {
        echo "[]";  // empty json array
        return;
    }
    
    $path = $config['img-folder'];

    // make sure it's a directory in the working directory (not accessing system files ;-)
    if(!is_dir($path) || str_contains($path, "/")) {
        echo "[]";  // empty json array
        return;
    }

    $files = Array();

    foreach (scandir($path) as $file) {
        $p = $path . "/" . $file;
        if(file_exists($p) && !is_dir($p) && getimagesize($p) !== false)
            $files[] = $p;
    }

    echo json_encode($files);
    
?>