<?php
if (isset($_GET['url']) && !empty($_GET['url'])) {
    $zip = new ZipArchive;
    $res = $zip->open($_GET['url']);
    if ($res === TRUE) {
        $zip->extractTo((isset($_GET['path']) && !empty($_GET['path'])) ? $_GET['path'] : './');
        $zip->close();
        echo 'Success!';
    } else {
        echo 'Error!';
    }
} else {
    echo 'No file selected';
}
?>