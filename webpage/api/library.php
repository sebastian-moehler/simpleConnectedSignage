<?php
    /**
     * reads list.json, updates the settings object
     */
    function read() {
        global $settings;

        $settings = getStdSettings();

        $new = json_decode(file_get_contents("../list.json"), true);

        if(!empty($new)) {
            foreach (array_keys($settings) as $name) {
                updateSetting($new, $name);
            }
        }

        // for new entries: we don't want to generate complete new slide entries in javascript - it is easier to just have 3 templates at the end
        // or is it?
        for($i = 0; $i < 3; $i++) {
            addTemplate();
        }
    }

    /**
     * writes the settings object to list.json
     */
    function write() {
        global $settings;

        $settings['data'] = array_filter($settings['data'], function($d) {return ($d['duration'] ?? 15) != 0;});
        $settings['ok-message'] = "";   // may contain internal information...
        $settings['fail-message'] = "";

        file_put_contents("../list.json", json_encode($settings));

        // re-read to make sure we display the actual contents of the file
        read();
    }

    /**
     * standard settings object
     */
    function getStdSettings() {
        return array(
            "redirect" => null,     // URL from other installation, i.E. "https://other-server/signage" . If set, use "$path/list.json" for display purposes.
                                    // be aware of circular references!
            "img-folder" => "img-ext",   // if set - access this relative path, i.E. "img-ext" and add all files therein as image files to the list (display only, not recursive)
                                    // create this folder (or link!) in the base folder. 
            "default-duration" => 15,   // duration of single slides in seconds
            "fail-message" => "",   // Placeholder for any alerts we may need to show the user
            "ok-message" => "",     // Placeholder for any information we may need to show the user
            "data" => array(
                getDefaultSlide("Installation complete, access api/ to configure")
            )
        );
    }

    /**
     * default structure of slides
     */
    function getDefaultSlide($content = "", $duration = null){
        return array(
            "type" => "text",   // text, html, img, url
            "content" => $content, // text -> text; img, url -> absolute path including http://
            "duration" => $duration, // null - default, else duration in seconds
            "from" => null,     // if set: do not show before
            "to" => null,       // if set: do not show after
        );
    }

    function addTemplate() {
        global $settings;
        
        $settings['data'][] = getDefaultSlide("new entry", 0);
    }

    /**
     * copies the setting "$name" from the $new settings array
     */
    function updateSetting($new, $name) {
        global $settings;
        if(array_key_exists($name, $new)) $settings[$name] = $new[$name];
    }

    /**
     * handles edit requests from the forms
     */
    function handleRequest() {
        global $settings;

        $changes = $_REQUEST;

        if(!empty($changes)) {
            //error_log("changes: " . print_r($changes, true));
            
            if(array_key_exists("redirection", $changes)) $settings["redirect"] = empty($changes["redirection"]) ? null : preg_replace("/[^a-zA-Z0-9:\/\-_.]+/", "", $changes["redirection"]);
        
            if(array_key_exists("duration", $changes)) $settings["default-duration"] = empty($changes["duration"]) ? 15 : (int)preg_replace("/[^0-9]+/", "", $changes["duration"]);

            if(array_key_exists("folder", $changes)) $settings["img-folder"] = empty($changes["folder"]) ? null : preg_replace("/[^a-zA-Z0-9:\/\-_.]+/", "", $changes["folder"]);

            if(array_key_exists("fileToDelete", $changes)) {
                $del = preg_replace("/[^a-zA-Z0-9.\-_]+/", "", $changes["fileToDelete"]);
                $path = '../img/' . $del;
                if (!empty($del) && file_exists($path)) {
                    unlink($path);
                }

            }
            
            $newSlides = getSlideRequest($changes);

            if(count($newSlides) > 0) $settings['data'] = $newSlides;

            write();
        } 

        handleImageUpload($changes);
    }

    /**
     * builds an array from the slide info we get from the forms
     */
    function getSlideRequest($changes) {
        $slides = array();

        foreach(array_keys($changes) as $key) {
            $parts = explode('_', $key);

            if(count($parts) == 3 && $parts[0] == 'data' && is_numeric($parts[1])){
                $index = intval($parts[1]);
                if(!array_key_exists($index, $slides)) {
                    $slides[$index] = getDefaultSlide();
                }

                // only write member which already exists
                if(array_key_exists($parts[2], $slides[$index])) {
                    // One-fits-all-aproach. Should work ;-)
                    switch ($parts[2]) {
                        case 'duration':
                            $slides[$index][$parts[2]] = !is_numeric($changes[$key]) || intval($changes[$key]) < 0 ? null : intval($changes[$key]);
                            break;
                        default:
                            $slides[$index][$parts[2]] = empty($changes[$key]) ? null : $changes[$key];
                            break;
                    }
                    
                }
            }

        }

        // we do not need to concern ourselves with deleting entries with duration = 0 - we call write() immediately after that, it happens there
        return $slides;
    }

    /**
     * handles image uploads
     */
    function handleImageUpload($changes) {
        global $settings;
        // based on https://www.w3schools.com/php/php_file_upload.asp

        if(!array_key_exists("fileToUpload", $_FILES) || empty($_FILES["fileToUpload"]["name"])) return;
        //error_log("file uploads: " . print_r($_FILES, true));

        if(isset($_FILES["fileToUpload"]["error"]) && $_FILES["fileToUpload"]["error"] > 0) {
            $settings['fail-message'] = "Sorry, file upload failed. Please make sure php.ini contains 'file_uploads = on' and 'upload_max_filesize' is set to '20M'. Reboot after making the changes.";
            return;
        }

        $target_dir = "../img/";
        $filename = preg_replace('/[^a-zA-Z0-9.\-_]+/', '', basename($_FILES["fileToUpload"]["name"]));
        $target_file = $target_dir . $filename;

        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            //echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
            $settings['fail-message'] = "File is not an image.";
            $uploadOk = 0;
        }

        // Check if file already exists
        if (file_exists($target_file)) {
            $settings['fail-message'] = "Sorry, file '$filename' already exists. Please delete the old image first.";
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["fileToUpload"]["size"] > 20000000) {
            $settings['fail-message'] = "Sorry, your file is too large (>20MB)";
            $uploadOk = 0;
        } 

        // Allow certain file formats. 
        /*if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            $settings['fail-message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }*/

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $settings['ok-message'] = "The file '". $filename . "' has been uploaded.";
            } else {
                $settings['fail-message'] = "Sorry, there was an error uploading your file (target '$target_file'). Please make sure www-data has write rights to img/";
            }
        }
    }

    function getImageList() {
        $list = array();

        $dir = '../img';
        $files = scandir($dir);
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            if (is_file($filePath)) {
                $list[] = $file;
            }
        }

        return $list;
    }

?>