<?php
    // Standard htpasswd: signage / editor
    require_once "library.php";

    $settings = getStdSettings();

    // first read config, before we integrate form input (because we need to write the complete object to the file)
    read();

    handleRequest();
?>

<html lang="en" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">
        <title>
            Signage configuration
        </title>
        <link rel="stylesheet" href="../bootstrap.min.css">
        <style>
            /* stolen from https://getbootstrap.com/docs/4.0/examples/checkout/ */
            .custom-select {
                display: inline-block;
                width: 100%;
                height: calc(2.25rem + 2px);
                padding: .375rem 1.75rem .375rem .75rem;
                line-height: 1.5;
                vertical-align: middle;
                background: var(--bs-body-bg) url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23343a40' d='M2 0L0 2h3zm0 5L0 3h3z'/%3E%3C/svg%3E") no-repeat right .75rem center;
                background-size: 8px 10px;
                border-radius: .25rem;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
            }

            .thumbnail {
                max-width: 300px;
                max-height: 300px;
            }

            option {
                background-color: var(--bs-body-bg);
            }

            .slide {
                background-color: var(--bs-gray-800);
            }

            
        </style>
    </head>
    <body>
        <?php if(!empty($settings['fail-message'])): ?>
            <div class="alert alert-danger"><?= $settings['fail-message'] ?></div>
        <?php endif; ?>
        <?php if(!empty($settings['ok-message'])): ?>
            <div class="alert alert-success"><?= $settings['ok-message'] ?></div>
        <?php endif; ?>

        <div class="container">
            <div class="py-5 text-center">
                <h2>Signage configuration</h2>
            </div>

            <h3 class="mb-3">master / slave settings</h3>

            <form action="index.php" method="post">
                <div class="mb-3">
                    <label for="redirection">Config-Redirect <span class="text-muted">(URL to Signage-Master. If set and valid, local GUI will ignore local settings. Be aware of circular references!)</span></label>
                    <input type="url" class="form-control" id="redirection" name="redirection" placeholder="i.e. https://other-server/signage" value="<?= $settings['redirect'] ?>">
                </div>

                <button class="btn btn-primary btn-lg btn-block" type="submit">Save</button>
            </form>
            
            <hr class="mb-5">
            <h3 class="mb-3">local settings</h3>
            <?php if($settings['redirect'] != null) : ?>
                <div class="alert alert-danger">fallback only!</div>
            <?php endif; ?>

            <form action="index.php" method="post">
                <div class="mb-3">
                    <label for="duration">deafult slide duration <span class="text-muted">[seconds]</span></label>
                    <input type="number" min="5" class="form-control" id="duration" name="duration" value="<?= $settings['default-duration'] ?>">
                </div>

                <div class="mb-3">
                    <label for="folder">image folder name <span class="text-muted">(folder in the base directory, which images should be appended to the list below)</span></label>
                    <input type="text" class="form-control" id="folder" name="folder" placeholder="i.e. img-ext" value="<?= $settings['img-folder'] ?>" pattern="^[a-zA-Z0-9\-_]+$">
                </div>

                <button class="btn btn-primary btn-lg btn-block" type="submit">Save</button>
            </form>

            <hr class="mb-5">
            <h3 class="mb-3">slides</h3>
            <?php if($settings['redirect'] != null) : ?>
                <div class="alert alert-danger">fallback only!</div>
            <?php endif; ?>

            <form action="index.php" method="post">
                <?php foreach($settings['data'] as $nr => $slide): ?>
                    <div class="mb-3 p-2 slide">
                        <div class="row"> 
                            <div class="col-md-2 mb-1">
                                <label for="data_<?= $nr ?>_typ">slide Type</label>
                                <select id="data_<?= $nr ?>_type" name="data_<?= $nr ?>_type" class="custom-select d-block w-100">
                                    <option value="text" <?= $slide['type'] == 'text' ? "selected" : "" ?>>text</option>
                                    <option value="html" <?= $slide['type'] == 'html' ? "selected" : "" ?>>HTML</option>
                                    <option value="url" <?= $slide['type'] == 'url' ? "selected" : "" ?>>Website</option>
                                    <option value="img" <?= $slide['type'] == 'img' ? "selected" : "" ?>>image</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-1">
                                <label for="data_<?= $nr ?>_duration">duration [s] <span class="text-muted">(-1 for default; 0 for delete)</span></label>
                                <input type="number" min="-1" class="form-control" id="data_<?= $nr ?>_duration" name="data_<?= $nr ?>_duration" placeholder="-1" value="<?= $slide['duration'] ?? -1 ?>">
                            </div>
                            <div class="col-md-3 mb-1">
                                <label for="data_<?= $nr ?>_from">show from <span class="text-muted">(empty for no restriction)</span></label>
                                <input type="date" class="form-control" id="data_<?= $nr ?>_from" name="data_<?= $nr ?>_from" value="<?= $slide['from'] ?? "" ?>">
                            </div>
                            <div class="col-md-3 mb-1">
                                <label for="data_<?= $nr ?>_to">show until <span class="text-muted">(empty for no restriction)</span></label>
                                <input type="date" class="form-control" id="data_<?= $nr ?>_to" name="data_<?= $nr ?>_to" value="<?= $slide['to'] ?? "" ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3" id="data_<?= $nr ?>_content_container">
                            <label for="data_<?= $nr ?>_content">content <span class="text-muted">(specific for type)</span></label>
                                <textarea class="form-control" id="data_<?= $nr ?>_content" name="data_<?= $nr ?>_content"><?= $slide['content']?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
                
            
                <button class="btn btn-primary btn-lg btn-block" type="submit">Save</button>
            </form>

            <hr class="mb-5">
            <h3 class="mb-3">uploaded images</h3>
            <?php if(!empty($settings['fail-message'])): ?>
                <div class="alert alert-danger"><?= $settings['fail-message'] ?></div>
            <?php endif; ?>

            <form action="index.php" method="post" enctype="multipart/form-data">
                <div class="row"> 
                    <div class="col-md-12 mb-1">
                        Select image to upload: 
                        <input type="file" name="fileToUpload" id="fileToUpload" accept="image/*" required>
                        <button class="btn btn-primary btn-lg btn-block" type="submit">Upload</button>
                    </div>
                </div>
            </form>

            <div class="col-md-12 mb-1">
                <div class="d-flex flex-row flex-wrap gap-2">
                    <?php foreach (getImageList() as $nr => $file): ?>
                        <form action="index.php" method="post" onsubmit="return window.confirm('delete <?= $file ?>?')">
                            <div class="d-flex flex-column">
                                <div><?= $file ?></div>
                                <img src="../img/<?= $file ?>" class="thumbnail img-thumbnail">
                                <input type="text" id="fileToDelete<?= $nr ?>" name="fileToDelete" value="<?= $file ?>" hidden>
                                <input type="submit" value="delete" >
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        
    </body>
</html>