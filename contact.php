<?php
// Shared Media Tagger
// Contact

//////////////////////////////////////////////////////////////////
$init = __DIR__.'/admin/src/smt.php'; // Shared Media Tagger Class
if( !is_readable($init) ) { exit('Site down for maintenance'); }
require_once($init);
$smt = new smt(); // Shared Media Tagger Object
//////////////////////////////////////////////////////////////////

$smt->title = 'Contact - ' . $smt->site_name;
$smt->include_header();
$smt->include_medium_menu();

print '<div class="box white">';

if( isset($_POST['c']) ) {
    $comment = urldecode($_POST['c']);
    $ip = @$_SERVER['REMOTE_ADDR'];
    $i = $smt->query_as_bool(
        'INSERT INTO contact (comment, datetime, ip) VALUES (:comment, CURRENT_TIMESTAMP, :ip)'
        , array(':comment'=>$comment, ':ip'=>$ip)
    );
    if( $i ) {
        print '<p>Thank you for your message.</p>';
        print '<p>You sent the following:</p>';
        print '<pre style="background-color:lightsalmon;">' . htmlentities($comment) . '</pre>';
        print '</div>';
        $smt->include_footer();
        exit;
    }
    print '<p>Error accessing database.  Try again later.</p>';
}



$headline = '<p>Contact us today!</p>';
$innertext = '* My Question or Comment:



';
$footer = '
* My Contact information:


';

if( isset($_GET['r']) && $smt->is_positive_number($_GET['r']) ) {
    $pageid = (int)$_GET['r'];

    $media = $smt->get_media($pageid);
    if( !$media || !isset($media[0]) ) {
        $smt->notice('ERROR: no media ID #' . $pageid . ' found.');
        goto form;
    }
    $media = $media[0];

    $headline = '<p>REPORT file #' . $pageid . '<br />'
    . $smt->display_thumbnail($media) . '</p>';
    $innertext = '
* REPORT file #' . $pageid . ':
* Reason:


';
}


form:

print $headline . '<form method="POST">
<textarea name="c" rows="12" cols="60">' . $innertext . $footer . '</textarea>
<p><input type="submit" value="           Send message          "></p>
</form><br /></div>';
$smt->include_footer();
