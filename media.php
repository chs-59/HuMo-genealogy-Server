<?php
session_start();
$url = urldecode($_SERVER['QUERY_STRING']);
//echo('QS ' . $url); exit;
// skip test for logo and favicon
if (in_array($url, array('media/logo.png', 'media/logo.jpg', 'media/favicon.ico'))) { 
    print_mediafile(__DIR__ . '/' . $url) ; 
    }
// session expired / no filename
if (!$_SESSION['tree_id'] || empty($url)) { print_mediafile(__DIR__ . '/images/missing-image.jpg'); }

include_once(__DIR__ . "/include/db_login.php"); //Inloggen database.
include_once(__DIR__ . '/include/show_tree_text.php');
include_once(__DIR__ . "/include/db_functions_cls.php");
include_once(__DIR__ . "/include/person_cls.php");

$tree_prefix   = $_SESSION['tree_prefix'];
$tree_id       = $_SESSION['tree_id'];
$user_group_id = $_SESSION['user_group_id'];

$db_functions = new db_functions($dbh);
$db_functions->set_tree_id($tree_id);
$groupsql = $dbh->query("SELECT * FROM humo_groups WHERE group_id='" . $user_group_id . "'");
$groupDb = $groupsql->fetch(PDO::FETCH_OBJ);

// free access to admin
if (isset($_SESSION['group_id_admin']) && $groupDb->group_admin === 'j') {
    print_mediafile(__DIR__ . '/' . $url);  
}

// no access to media files
if ($groupDb->group_pictures == 'n')  { 
    print_mediafile(__DIR__ . '/images/missing-image.jpg'); 
}

$datasql = $dbh->query("SELECT * FROM humo_trees WHERE tree_prefix='" . $tree_prefix . "'");
$dataDb = $datasql->fetch(PDO::FETCH_OBJ);
$tree_pict_path = $dataDb->tree_pict_path;
if (substr($tree_pict_path, 0, 1) === '|') { $tree_pict_path = 'media/'; }
$picture_dbname = str_replace( $tree_pict_path, '', $url ); //delete pic-path for db lookup
$picture_dbname = preg_replace( '/thumb_(.+\.\w{3})\.jpg/', '$1', $picture_dbname ); //delete thumb extensions for lookup (new style)
$picture_dbname = str_replace('thumb_', '', $picture_dbname ); //delete thumb extension for lookup (old style)
$qry = "SELECT * FROM humo_events WHERE event_tree_id='" . $tree_id . "' "
        . "AND (event_connect_kind='person' OR event_connect_kind='family' OR event_connect_kind='source') "
        . "AND event_connect_id NOT LIKE '' AND event_event='" . $picture_dbname . "'";

$media_qry = $dbh->query($qry);

while ($media_qryDb = $media_qry->fetch(PDO::FETCH_OBJ)) {    

    if (    $media_qryDb ) {   // pic in db
        $media_filename = __DIR__ . '/' . $url;
        // person
        if ($media_qryDb && $media_qryDb->event_connect_kind === 'person') {
            $person_cls = new person_cls;
            $personDb = $db_functions->get_person( $media_qryDb->event_connect_id );
            $privacy = $person_cls->set_privacy($personDb);
            if ($personDb && !$privacy) { print_mediafile($media_filename); }
        // family
        } elseif ($media_qryDb && $media_qryDb->event_connect_kind === 'family') {
            $qry2 = "SELECT * FROM humo_families WHERE fam_gedcomnumber='" . $media_qryDb->event_connect_id . "'";
            $family_qry = $dbh->query($qry2);
            $family_qryDb2 = $family_qry->fetch(PDO::FETCH_OBJ);
            @$personmnDb2 = $db_functions->get_person($family_qryDb2->fam_man);
            $man_cls2 = new person_cls($personmnDb2);
            @$personmnDb3 = $db_functions->get_person($family_qryDb2->fam_woman);
            $woman_cls = new person_cls($personmnDb3);
            // *** Only use this picture if both man and woman have disabled privacy options ***
            if ($man_cls2->privacy == '' && $woman_cls->privacy == '') { print_mediafile($media_filename);} 
        // source
        } elseif ($media_qryDb && $media_qryDb->event_connect_kind === 'source') {
            $sourceDb = $db_functions->get_source($media_qryDb->event_connect_id);
            if ( $groupDb->group_sources == 'j' && $sourceDb->source_status == 'publish' ) { print_mediafile($media_filename);  } 
            if ( $groupDb->group_show_restricted_source == 'y' ) { print_mediafile($media_filename); }
        } 
    }
}
print_mediafile(__DIR__ . '/images/missing-image.jpg');
    
function print_mediafile ($filename) {
    if (!file_exists( $filename)) {
        $filename = __DIR__ . '/images/missing-image.jpg';        
    }
    $content_type_header = mime_content_type($filename);
    $filesize = filesize($filename);
    header('Content-Type: ' . $content_type_header);
    header('Content-Disposition: inline; filename="' . $url . '"');
    header('Cache-Control: private, max-age=3600');
    header('Content-Length: '. filesize($filename));
    header('Pragma:');
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (3600))); // 3600s cache
    readfile($filename);
    exit;
}
