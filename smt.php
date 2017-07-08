<?php
// Shared Media Tagger (SMT)

define('__SMT__', '0.4.3');

$f = __DIR__.'/_setup.php'; 
if(file_exists($f) && is_readable($f)){ include_once($f); }

//////////////////////////////////////////////////////////
// SMT Utils
class smt_utils {

    var $links; // array of [page_name] = page_url
    var $debug; // debug mode TRUE / FALSE;
    
    //////////////////////////////////////////////////////////
    function url( $link='' ) {
        if( !$link || !isset($this->links[$link]) ) { 
            $this->error("::url: Link Not Found: $link");
            return FALSE;
        } 
        return $this->links[$link];
    } 

    //////////////////////////////////////////////////////////
    function log_message( $message, $type ) {
        switch( $type ) {
            case 'debug':  $class = 'debug';  $head = 'DEBUG'; break;
            case 'notice': $class = 'notice'; $head = 'NOTICE'; break;
            case 'error':  $class = 'error';  $head = 'ERROR'; break;
            case 'fail':   $class = 'fail';   $head = 'GURU MEDITATION FAILURE'; break;
            default: return;
        }
        print '<div class="message ' . $class . '"><b>' . $head . '</b>: ' . print_r($message,1) . '</div>';
    }

    //////////////////////////////////////////////////////////
    function debug( $message='' ) {
        if( !$this->debug ) { return; }
        $this->log_message( $message, 'debug' );
    }

    //////////////////////////////////////////////////////////
    function notice( $message='' ) {
        $this->log_message( $message, 'notice' );
    }
    
    //////////////////////////////////////////////////////////
    function error( $message='' ) {
        $this->log_message( $message, 'error' );
    }

    //////////////////////////////////////////////////////////
    function fail( $message='' ) {
        $this->log_message( $message, 'fail' );
        exit;
    }

    //////////////////////////////////////////////////////////
    function fail404 ( $message='' ) {
        header('HTTP/1.0 404 Not Found');
        $this->include_header();
        $this->include_menu();
        if( !$message || !is_string($message) ) {
            $message = '404 Not Found';
        }
        print '<div class="box white center" style="padding:50px 0px 50px 0px;"><h1>' . $message . '</h1></div>';
        $this->include_footer();
        exit;        
    }

    //////////////////////////////////////////////////////////
    function is_positive_number($n='') { 
        if ( preg_match('/^[0-9]*$/', $n )) { return TRUE; }
        return FALSE;
    }

    /////////////////////////////////////////////////////////
    function truncate( $string, $length=50 ) {
        if( strlen($string) <= $length ) {
            return $string;
        }
        $last = substr( $string, -8);
        $r = substr( $string, 0, $length-11 ) . '...' . $last;
        return $r;
    }

    //////////////////////////////////////////////////////////
    function centerpad( $string, $length ) {
        if( !$length ) {
            return $string;
        }
        if( strlen($string) >= $length ) {
            return $string;
        }
        return str_pad($string, $length, ' ', STR_PAD_BOTH);        
    }
    
    //////////////////////////////////////////////////////////
    function include_header() {
        print '<!doctype html>
<html><head><title>' . $this->title . '</title>
<meta charset="utf-8" />
<meta name="viewport" content="initial-scale=1" />
<link rel="stylesheet" type="text/css" href="' . $this->url('css') . '">
</head><body>';
        if( is_readable(__DIR__.'/header.php') ) {
            include( __DIR__.'/header.php');
        }
    }
    
    //////////////////////////////////////////////////////////
    function include_footer() {
        $this->include_menu();
        print '<footer>'
        . '<div class="menu">'
        . '<span class="nobr">Powered by <b>'
        . '<a target="commons" href="https://github.com/attogram/shared-media-tagger">'
        . 'Shared Media Tagger v' . __SMT__ . '</a></b></span>'
        . '<br />'
        . '<span class="nobr">Hosted by <b><a href="//'
        . @$_SERVER['SERVER_NAME'] 
        . '/">'
        . @$_SERVER['SERVER_NAME'] 
        . '</a>'
        . '</b></span>'
        . '</div>'
        . '</footer>';
		
		if( $this->is_admin() ) {
			print '<div class="menu">'
			. '<a href="' . $this->url('admin') . '">ADMIN MENU</a>'
			. ' &nbsp; &nbsp; <a href="' . $this->url('home') . '?logoff">logoff</a>'
			. '</div>';
		}

        if( is_readable(__DIR__.'/footer.php') ) {
            include( __DIR__.'/footer.php');
        }
        print '</body></html>';
    }

    //////////////////////////////////////////////////////////
    function include_menu() {

        $space = ' &nbsp; &nbsp; &nbsp; &nbsp; ';
?><div class="menu">
<span class="nobr"><b><a href="<?php print $this->url('about'); ?>"><?php 
    print $this->site_name; ?></a></b></span>
<?php print $space; ?>
<span class="nobr menu_highlight"><b><a href="<?php print $this->url('home'); ?>">Rate a file</a></b></span>
<?php print $space; ?>
<span class="nobr"><b><a href="<?php print $this->url('categories'); ?>"><?php 
    print $this->get_categories_count(); ?> Categories</a></b></span>
<?php print $space; ?>
<span class="nobr"><b><a href="<?php print $this->url('reviews'); ?>"><?php 
    print $this->get_total_review_count(); ?> Reviews</a></b> 
<?php print $space; ?>
<?php print $this->get_image_count(); ?> Files</span>
<?php print $space; ?>
<b><a href="<?php print $this->url('contact'); ?>">Contact</a></b>
</div>
<?php
    }  // end function include_menu()
        
    function include_small_menu() {
        print ''
        . '<div class="menu" style="line-height:1;padding:14px 0px 6px 0px;margin:0px;font-weight:bold;">'
          . '<a href="' . $this->url('home') . '">' . $this->site_name . '</a>'
          . '<div style="float:right; margin-right:10px; font-size:80%;">'
            . '<a href="' . $this->url('about') . '">About</a>'
            . ' &nbsp;-&nbsp; '
            . '<a href="' . $this->url('categories') . '">Categories</a>'
          . '</div>'
        . '</div>'
        ;
    }
} //end class smt_utils

//////////////////////////////////////////////////////////
// SMT Database Utils
class smt_database_utils EXTENDS smt_utils {
    var $database_name;
    var $db;
    var $sql_count;
    var $last_insert_id;
    
    //////////////////////////////////////////////////////////
    function init_database() {
        $this->debug('::init_database()');
        if( !in_array('sqlite', PDO::getAvailableDrivers() ) ) {
            $this->error('::init_database: ERROR: no sqlite Driver');
            return $this->db = FALSE;
        }
        try {
            return $this->db = new PDO('sqlite:'. $this->database_name);
        } catch(PDOException $e) {
            $this->error('::init_database: ' . $this->database_name . '  ERROR: '. $e->getMessage());
            return $this->db = FALSE;
        }
    }

    //////////////////////////////////////////////////////////
    function query_as_array( $sql, $bind=array() ) {
        $this->debug("::query_as_array() sql: $sql #bind:" . sizeof($bind));
        if( !$this->db ) { $this->init_database(); }
        $statement = $this->db->prepare($sql);
        if( !$statement ) {
            $this->debug('::query_as_array(): ERROR PREPARE'); // '. $this->db->errorInfo()[2]);
            return array();
        }
        while( $x = each($bind) ) {
            $this->debug('::query_as_array(): bindParam '. $x[0] .' = ' . $x[1]);
            $statement->bindParam( $x[0], $x[1]);
        }    
        if( !$statement->execute() ) {
            $this->error('::query_as_array(): ERROR EXECUTE: '
                //. $sql 
                . ' == '.print_r($this->db->errorInfo(),1));
            return array();
        }
        $this->sql_count++;
        $r = $statement->fetchAll(PDO::FETCH_ASSOC);
        if( !$r && $this->db->errorCode() != '00000') { 
            $this->error('::query_as_array(): ERROR FETCH: '.print_r($this->db->errorInfo(),1));
            $r = array();
        }
        $this->debug('::query_as_array(): OK. rowcount=' . count($r) );
        $this->debug('query_as_array: result: ' . print_r($r,1) );
        return $r;
    }

    //////////////////////////////////////////////////////////
    function query_as_bool( $sql, $bind=array() ) {
        $this->debug("::query_as_bool() sql: $sql #bind:" . sizeof($bind));
        if( !$this->db ) { $this->init_database(); }
        $statement = $this->db->prepare($sql);
        if( !$statement ) {
            $this->error('::query_as_bool(): ERROR PREPARE'); // : '.$this->db->errorInfo()[2]);
            return FALSE;
        }
        $this->debug('::query_as_bool(): bind: '.print_r($bind,1));

        while( $x = each($bind) ) {
            $this->debug('::query_as_bool: bindParam: ' . $x[0] . ' = ' . htmlentities($x[1]));
            $statement->bindParam( $x[0], $x[1] );
        }    
        
        if( !$statement->execute() ) {
            $this->error('::query_as_bool: EXECUTE FAILED: ' . $sql 
            //. '<br />errorinfo:'.print_r($this->db->errorInfo(),1)
            );
            return FALSE;
        }
        $this->sql_count++;
        $this->last_insert_id = $this->db->lastInsertId();
        $this->debug('::query_as_bool(): OK');
        return TRUE;
    }

    //////////////////////////////////////////////////////////
    function vacuum() {
        if( $this->query_as_bool('VACUUM') ) {
            return TRUE;
        }
        $this->notice('ERROR vacumming database');
        return FALSE;
    }

    //////////////////////////////////////////////////////////
    function begin_transaction() {
        if( $this->query_as_bool('BEGIN TRANSACTION') ) {
            return TRUE;
        }
        $this->notice('ERROR begining transaction');
        return FALSE;
    }

    //////////////////////////////////////////////////////////
    function commit() {
        if( $this->query_as_bool('COMMIT') ) {
            return TRUE;
        }
        $this->notice('ERROR commiting transaction');
        return FALSE;
    }

} // end class smt_database_utils

//////////////////////////////////////////////////////////
// SMT Database
class smt_database EXTENDS smt_database_utils {

    var $site_name;
    var $image_count;
    var $category_count; 

    //////////////////////////////////////////////////////////
    function set_site_name( $site_id=1 ) {
        $r = $this->query_as_array('SELECT name FROM site WHERE id = 1');
        if( !$r || !isset($r[0]['name']) ) {
            $this->site_name = 'My Site';
            return FALSE;
        }
        $this->site_name = $r[0]['name'];
        return TRUE;
    }

    //////////////////////////////////////////////////////////
    function get_image_from_db($pageid) {
        $this->debug("smt-db:get_image_from_db($pageid)");
        if( !$pageid || !$this->is_positive_number($pageid) ) { 
            $this->error('get_image_from_db: ERROR no id'); 
            return FALSE;
        }
        $sql = 'SELECT * FROM media WHERE pageid = :pageid';
        return $this->query_as_array( $sql , $bind = array(':pageid'=>$pageid) );
    }

    //////////////////////////////////////////////////////////
    function get_random_unreviewed_media($limit=1) {
        $sql = '
            SELECT m.*
            FROM media AS m
            LEFT JOIN tagging AS t ON t.media_pageid = m.pageid
            WHERE t.media_pageid IS NULL
            ORDER BY RANDOM() 
            LIMIT :limit';
        return $this->query_as_array( $sql , $bind = array( 'limit'=>$limit ) );
    }
    
    //////////////////////////////////////////////////////////
    function get_random_media($limit=1) {
        $unreviewed = $this->get_random_unreviewed_media($limit);
        if( $unreviewed ) {
            if( mt_rand(1,5) != 1 ) { 
                return $unreviewed;
            }
        }
        $sql = 'SELECT * FROM media ORDER BY RANDOM() LIMIT :limit';
        return $this->query_as_array( $sql , $bind = array( 'limit'=>$limit ) );
    }
    
    //////////////////////////////////////////////////////////
    function get_image_count( $redo=FALSE ) {
        if( isset($this->image_count) && !$redo ) {
            return $this->image_count;
        }
        $x = $this->query_as_array('SELECT count(pageid) AS count FROM media');
        if( !$x ) { 
            $this->debug('::get_image_count() ERROR query failed.'); 
            return 0; 
        }
        return $this->image_count = $x[0]['count'];
    }
    
    //////////////////////////////////////////////////////////
    function get_category( $name ) {
        $c = $this->query_as_array(
            'SELECT id, name, pageid, files, subcats FROM category WHERE name = :name', 
            array(':name'=>$name) 
        );
        if( !isset($c[0]['id']) ) {
            return array();
        }
        return $c[0];
    }
    
    //////////////////////////////////////////////////////////
    function get_category_size( $category_name ) {
        $c = $this->query_as_array(
            'SELECT count(c2m.id) AS size 
            FROM category2media AS c2m, category AS c
            WHERE c.name = :name
            AND c2m.category_id = c.id
            ', 
            array(':name'=>$category_name)
        );    
        if( !isset($c[0]['size']) ) {
            return 0;
        }
        return $c[0]['size'];        
    }
    
    //////////////////////////////////////////////////////////
    function get_categories_count( $redo=FALSE ) {
        if( isset($this->category_count) && !$redo ) {
            return $this->category_count;
        }
        $x = $this->query_as_array('
            SELECT count( distinct(category_id) ) AS count 
            FROM category2media');
        if( !$x ) { 
            $this->debug('::get_categories_count() ERROR query failed'); 
            return 0; 
        }
        return $this->category_count = $x[0]['count'];        
    }

    //////////////////////////////////////////////////////////
    function get_category_list() { 
        //$this->debug("::get_category_list()");
        $sql = 'SELECT name FROM category ORDER BY name';
        $x = $this->query_as_array( $sql );
        $r = array();
        if( !$x || !is_array($x) ) { return $r; } 
        while( $y = each($x) ) { 
            $r[] = $y['value']['name'];
        } 
        return $r;
 
    }

    //////////////////////////////////////////////////////////
    function get_image_categories( $pageid ) {
        //$this->notice('::get_image_categories: pageid=' . $pageid);
        $error = array('Category database unavailable');
        if( !$pageid|| !$this->is_positive_number($pageid) ) {
            return $error;
        }
        $c = $this->query_as_array(
            'SELECT category.name
            FROM category, category2media
            WHERE category2media.category_id = category.id
            AND category2media.media_pageid = :pageid',
            array(':pageid'=>$pageid)            
        );
        if( !isset( $c[0]['name'] ) ) {
            $this->error('::get_image_categories: ' . print_r($c,1) );
            return $error;
        }
        $cats = array();
        foreach( $c as $cat ) {
            $cats[] = $cat['name'];
        }
        return $cats;
    }

    //////////////////////////////////////////////////////////
    function get_category_id_from_name( $category_name ) {
        $c = $this->query_as_array(
            'SELECT id FROM category WHERE name = :name', 
            array(':name'=>$category_name)
        );

        if( !isset($c[0]['id']) ) {
            return 0;
        }
        return $c[0]['id'];
    }

    //////////////////////////////////////////////////////////
    function get_media_in_category( $category_name ) {
        $category_id = $this->get_category_id_from_name( $category_name );
        if( !$category_id ) {
            $this->error('::get_media_in_category: No ID found for: ' . $category_name);
            return array();
        }
        $m = $this->query_as_array(
            'SELECT media_pageid 
            FROM category2media
            WHERE category_id = :category_id
            ORDER BY media_pageid',
            array(':category_id'=>$category_id)
        );
        if( $m === FALSE ) {
            $this->notice('ERROR: unable to access categor2media table.');
            return array();
        }
        if( !$m ) {
            //$this->notice('get_media_in_category: No Media Found in ' . $category_name);
            return array();
        }
        $r = array();
        foreach( $m as $mp ) {
            $r[] = $mp['media_pageid'];
        }
        return $r;
    }

    //////////////////////////////////////////////////////////
    function get_tag_id_by_name( $name ) {
        if( isset( $this->tag_id[$name] ) ) {
            return $this->tag_id[$name];
        }
        $tag = $this->query_as_array('SELECT id FROM tag WHERE name = :name LIMIT 1', array(':name'=>$name) );
        if( isset( $tag[0]['id'] ) ) { 
            return $this->tag_id[$name] = $tag[0]['id'];
        }
        return $this->tag_id[$name] = 0;
    }

    
    //////////////////////////////////////////////////////////
    function get_tags() {
        if( isset($this->tags) ) {
            reset($this->tags);
            return $this->tags;
        }
        $tags = $this->query_as_array('
            SELECT * FROM tag ORDER BY position');
        if( !$tags ) {
            $this->notice('Tag database not available');
            return $this->tags = array();
        }
        return $this->tags = $tags;
    }

    //////////////////////////////////////////////////////////
    function get_tagging_count( $tag_id=FALSE ) {
        $sql = 'SELECT SUM(count) AS count FROM tagging';
        $bind = array();
        if( $tag_id ) {
            $sql .= ' WHERE tag_id = :tag_id';
            $bind[':tag_id'] = $tag_id;
        }
        $c = $this->query_as_array($sql, $bind);
        if( !isset($c[0]['count']) ) {
            return '0';
        }
        return $c[0]['count'];
    }
        
    /////////////////////////////////////////////////////////
    function get_reviews( $pageid ) {
        $reviews = $this->query_as_array('
            SELECT t.tag_id, t.count, tag.*
            FROM tagging AS t, tag
            WHERE t.media_pageid = :media_pageid
            AND tag.id = t.tag_id
            ORDER BY tag.position
            ', array(':media_pageid'=>$pageid) );
        return $this->display_reviews( $reviews );
    }

    /////////////////////////////////////////////////////////
    function get_reviews_per_category( $category_id ) {
        return $this->display_reviews( $this->get_db_reviews_per_category($category_id) );
    }

        
    /////////////////////////////////////////////////////////
    function get_db_reviews_per_category( $category_id ) {
        $reviews = $this->query_as_array('
            SELECT SUM(t.count) AS count, tag.*
            FROM tagging AS t, 
                 tag, 
                 category2media AS c2m
            WHERE tag.id = t.tag_id
            AND c2m.media_pageid = t.media_pageid
            AND c2m.category_id = :category_id
            GROUP BY (tag.id)
            ORDER BY tag.position
            ', array(':category_id'=>$category_id) );
        return $reviews;
    }
    
    /////////////////////////////////////////////////////////
    function get_total_files_reviewed_count() {
        if( isset($this->total_files_reviewed_count) ) {
            return $this->total_files_reviewed_count;
        }        
        $x = $this->query_as_array('SELECT COUNT( DISTINCT(media_pageid) ) AS total FROM tagging');
        if( isset($x[0]['total']) ) {
            return $this->total_files_reviewed_count = $x[0]['total'];
        }
        return $this->total_files_reviewed_count = 0;        
    }

    /////////////////////////////////////////////////////////
    function get_total_review_count() {
        if( isset($this->total_review_count) ) {
            return $this->total_review_count;
        }
        $x = $this->query_as_array('SELECT SUM(count) AS total FROM tagging');
        if( isset($x[0]['total']) ) {
            return $this->total_review_count = $x[0]['total'];
        }
        return $this->total_review_count = 0;
    }

} // END class smt_database

//////////////////////////////////////////////////////////
// SMT Site Utils
class smt_site_utils EXTENDS smt_database {
    
    //////////////////////////////////////////////////////////
    function strip_prefix( $string ) {
        if( !$string || !is_string($string) ) {
            return $string;
        }
        return preg_replace(
            array( '/^File:/', '/^Category:/' ), 
            '', 
            $string
        );
    }
    
    //////////////////////////////////////////////////////////
    function category_urldecode($category) {
        return str_replace(
            '_', 
            ' ', 
            urldecode($category)
        );
    }

    //////////////////////////////////////////////////////////
    function category_urlencode($category) {
        return str_replace(
            '+',
            '_', 
            str_replace(
                '%3A',
                ':', 
                urlencode($category)
            )
        );
    }
    
}

//////////////////////////////////////////////////////////
// SMT - Shared Media Tagger
class smt EXTENDS smt_site_utils {

    var $site, $site_url, $title;
    var $size_medium, $size_thumb;

    //////////////////////////////////////////////////////////
    function __construct( $title='' ) {
        
        global $setup;
        
        $this->debug = FALSE;
        $this->database_name = __DIR__ . '/admin/db/media.sqlite';        
        $this->title = $title;
        $this->site = 1; // site.id 
        $this->sql_count = 0;
        $this->size_medium = 325;
        $this->size_thumb = 100;
                
        if( isset($setup['site_url']) ) {
            $this->site_url = $setup['site_url'];
        } else {
            $this->site_url = '//' . $_SERVER['HTTP_HOST'] . '/';
            if( $base = basename(__DIR__) ) {
                $this->site_url .= $base . '/';
            }
            $this->debug('Site URL Not Set.  Using: ' . $this->site_url);
        }

        $this->set_site_name( $this->site );
        
        $this->links = array(
            'home'       => $this->site_url . '',
            'random'     => $this->site_url . '',
            'css'        => $this->site_url . 'css.css',
            'info'       => $this->site_url . 'info.php',
            'categories' => $this->site_url . 'categories.php',
            'category'   => $this->site_url . 'category.php',
            'about'      => $this->site_url . 'about.php',
            'reviews'    => $this->site_url . 'reviews.php',
            'admin'      => $this->site_url . 'admin/',
            'contact'    => $this->site_url . 'contact.php',
            'tag'        => $this->site_url . 'tag.php',
        );
		
		if( isset($_GET['logoff']) ) {
			$this->admin_logoff();
		}
    }

    //////////////////////////////////////////////////////////
    function get_thumbnail( $media='', $thumb_width='' ) {
        
        if( !$thumb_width || !$this->is_positive_number($thumb_width) ) {
            $thumb_width = $this->size_thumb;
        }
                
        $default = array(
            'url' => 'data:image/gif;base64,R0lGOD lhCwAOAMQfAP////7+/vj4+Hh4eHd3d/v'
                    .'7+/Dw8HV1dfLy8ubm5vX19e3t7fr 6+nl5edra2nZ2dnx8fMHBwYODg/b29np6e'
                    . 'ujo6JGRkeHh4eTk5LCwsN3d3dfX 13Jycp2dnevr6////yH5BAEAAB8ALAAAAAA'
                    . 'LAA4AAAVq4NFw1DNAX/o9imAsB tKpxKRd1+YEWUoIiUoiEWEAApIDMLGoRCyWi'
                    . 'KThenkwDgeGMiggDLEXQkDoTh CKNLpQDgjeAsY7MHgECgx8YR8oHwNHfwADBACG'
                    . 'h4EDA4iGAYAEBAcQIg0Dk gcEIQA7',
            'width' => $thumb_width, 
            'height' => $thumb_width);
            
        
        if( !$media || !is_array($media) ) {
            return $default;
        }

        $width = $media['width'];
        if( !$width ) { $width = $this->size_thumb; }
        $height = $media['height'];
        if( !$height ) { $height = $this->size_thumb; }
        
        //$this->notice("::get_thumbnail: new-w:$thumb_width  old-w:$width old-h:$height");
        if( $thumb_width >= $width ) {
            //$this->notice('::get_thumbnail: new-w >= old-w');
            return array('url'=>$media['thumburl'], 'width'=>$width, 'height'=>$height);
        }


        if( $height > $thumb_width ) {
            //$this->notice("WARNING: TALL THUMB");
        }
        
        
        $title = $media['title'];
        $mime = $media['mime'];


        $filename = $this->strip_prefix($media['title']);
        $filename = str_replace(' ','_',$filename);
        
        $md5 = md5($filename);
        $thumb_url = 'https://upload.wikimedia.org/wikipedia/commons/thumb'
        . '/' . $md5[0] 
        . '/' . $md5[0] . $md5[1] 
        . '/' . $filename 
        . '/' . $thumb_width . 'px-' . $filename;

        $ratio = $width / $height;
        $thumb_height = round($thumb_width / $ratio);
        
        switch( $mime ) {
            case 'application/ogg':
                $thumb_url = str_replace('px-','px--',$thumb_url);
                $thumb_url .= '.jpg';
                break;
            case 'video/webm':
                $thumb_url = str_replace('px-','px--',$thumb_url);
                $thumb_url .= '.jpg';
                break;
            case 'image/svg+xml':
                $thumb_url .= '.png';
                break;
        }
        
        $r = array('url'=>$thumb_url, 'width'=>$thumb_width, 'height'=>$thumb_height);

        //$this->notice($r);
        return $r;
    }
    
    //////////////////////////////////////////////////////////
    function display_thumbnail( $media='', $size='' ) {
        
        $thumb = $this->get_thumbnail($media);

        return '<div style="display:inline-block;text-align:center;">'
            . '<a href="' .  $this->url('info') . '?i=' . $media['pageid'] . '">'
            . '<img src="' . $thumb['url'] . '" width="' . $thumb['width'] 
            . '" height="' . $thumb['height'] . '"'
            . ' title="' . htmlentities($media['title']) . '" /></a>'
            . '</div>'
            ;
    }

    //////////////////////////////////////////////////////////
    function display_thumbnail_box( $media='' ) {
        $r = '<div class="thumbnail_box">'
        . $this->display_thumbnail($media)
        . str_replace(
            ' / ', 
            '<br />', 
            $this->display_attribution( $media, /*title truncate*/17, /*artist*/21 ) 
            )
        . $this->display_admin_functions( $media['pageid'] )
        //. '<br />'
        . '<div class="thumbnail_reviews left">'
		. $this->get_reviews( $media['pageid'] ) 
		. '</div>'
        . '</div>';
        return $r;
    }
    
    
    //////////////////////////////////////////////////////////
    function display_video( $media ) {
        $mime = $media['mime'];
        $url = $media['url'];
        $width = $media['thumbwidth'];
        $height = $media['thumbheight'];
        $poster = $media['thumburl'];
        
        if( !$width || $width > $this->size_medium) { 
            $height = $this->get_resized_height( $width, $height, $this->size_medium );
            $width = $this->size_medium; 
        }
        $infourl = $this->url('info') . '?i=' . $media['pageid'];
        $divwidth = $width = $media['thumbwidth'];
        if( $divwidth < $this->size_medium )  {
            $divwidth = $this->size_medium;
        }
        
        $r = '<div style="width:' . $divwidth . 'px; margin:auto;">'
        . '<video width="'. $divwidth . '" height="' . $height . '" poster="' . $poster 
        . '" onclick="this.paused ? this.play() : this.pause();" controls loop>'
        . '<source src="' . $url . '" type="' . $mime . '">'
        . '</video>'
        . $this->display_attribution( $media )
        . $this->display_admin_functions( $media['pageid'] )
        . '</div>';
        return $r;
    }

    //////////////////////////////////////////////////////////
    function display_audio( $media ) {
        $mime = $media['mime'];
        $url = $media['url'];
        $width = $media['thumbwidth'];
        $height = $media['thumbheight'];
        $poster = $media['thumburl'];

        if( !$width || $width > $this->size_medium) { 
            $height = $this->get_resized_height( $width, $height, $this->size_medium );
            $width = $this->size_medium; 
        }
        $infourl = $this->url('info') . '?i=' . $media['pageid'];
        $divwidth = $width = $media['thumbwidth'];
        if( $divwidth < $this->size_medium )  {
            $divwidth = $this->size_medium;
        }
        
        $r = '<div style="width:' . $divwidth . 'px; margin:auto;">'
        . '<audio width="'. $width . '" height="' . $height . '" poster="' . $poster 
        . '" onclick="this.paused ? this.play() : this.pause();" controls loop>'
        . '<source src="' . $url . '" type="' . $mime . '">'
        . '</audio>'
        . $this->display_attribution( $media )
        . $this->display_admin_functions( $media['pageid'] )        
        . '</div>';
        return $r;    
    }
    
    //////////////////////////////////////////////////////////
    function display_image( $media='' ) {
        if( !$media || !is_array($media) ) {
            $this->error('display_image: ERROR: no image array');
            return FALSE;
        }
        
        $mime = @$media['mime'];

        $video = array('application/ogg','video/webm');
        if( in_array( $mime, $video ) ) {
            return $this->display_video($media);
        }

        $audio = array('audio/x-flac');
        if( in_array( $mime, $audio ) ) {
            return $this->display_audio($media);
        }
        
        $url = $media['thumburl'];
        $height = $media['thumbheight'];
        $divwidth = $width = $media['thumbwidth'];
        if( $divwidth < $this->size_medium )  {
            $divwidth = $this->size_medium;
        }
        $infourl =  $this->url('info') . '?i=' . $media['pageid'];
        
        return  '<div style="width:' . $divwidth . 'px; margin:auto;">'
        . '<a href="' . $infourl . '">'
        . '<img src="'. $url .'" height="'. $height .'" width="'. $width . '" alt=""></a>'
        . $this->display_attribution( $media )
        . $this->display_admin_functions( $media['pageid'] )
        . '</div>'
        ;
    }

    //////////////////////////////////////////////////////////
	function is_admin() {
        if( isset($_COOKIE['admin']) && $_COOKIE['admin'] == 1 ) {
            return TRUE;
        }
		return FALSE;
	}

    //////////////////////////////////////////////////////////
	function admin_logoff() {
		if( !$this->is_admin() ) {
			return;
		}
		unset($_COOKIE['admin']);
		setcookie('admin', null, -1, '/');
	}

    //////////////////////////////////////////////////////////
    function display_admin_functions( $media_id ) {
        if( !$this->is_admin() ) {
            return;
        }
        if( !$this->is_positive_number($media_id) ) {
            return;
        }
        return ''
        . '<div class="attribution left" style="float:right;">'
        . '<a style="font-size:170%;" href="' . $this->url('admin') . 'media.php?dm=' . $media_id
        . '" title="Delete" onclick="return confirm(\'Confirm: Delete Media #' . $media_id . ' ?\');"'
        . '>⛔</a>'
        . '</div>';
    }

    //////////////////////////////////////////////////////////
    function display_categories( $media_id ) {
        if( !$media_id || !$this->is_positive_number($media_id) ) {
            return FALSE;
        }
        $cats = $this->get_image_categories( $media_id );
        $r = '<div class="categories" style="width:' . $this->size_medium . 'px;">';
        if( !$cats ) { $r .= '<em>Uncategorized</em>'; }
        foreach($cats as $cat ) {
            $r .= ''
			. 'Category: '
            . '<a href="' . $this->url('category') 
            . '?c=' . $this->category_urlencode( $this->strip_prefix($cat) ) . '">' 
            . $this->strip_prefix($cat) . '</a><br />';
        }
        return $r . '</div>';
    }
    
    //////////////////////////////////////////////////////////
    function display_tags( $media_id ) {
        $tags = $this->get_tags();        
        $r = '<div style="display:block; margin:auto;">';
        foreach( $tags as $tag ) {
            $r .=  ''
            . '<div class="tagbutton tag' . $tag['position'] . '">'
            . '<a href="' . $this->url('tag') . '?m=' . $media_id 
                . '&amp;t=' . $tag['id'] . '" title="' . $tag['name'] . '">' 
            . $tag['display_name']
            . '</a></div>';
        }
        return $r . '</div>'; 
    }

    /////////////////////////////////////////////////////////
    function display_reviews( $reviews ) {
        if( !$reviews ) {
            return 'unreviewed';
        }
        $review_count = 0;
        $r = '';
        foreach( $reviews as $review ) {
            $r .= '<div class="tag' . $review['position'] . '">'
            . '+<b>' . $review['count'] . '</b> ' . $review['name'] . '</div>';
            $review_count += $review['count'];
        }
        $r = '<div style="display:inline-block; text-align:left;">'
        . '<em><b>' . $review_count . '</b> reviews</em>' . $r . '</div>';
        return $r;        
    }

    /////////////////////////////////////////////////////////
    function display_licensing( $media, $artist_truncate=42 ) {
        if( !$media || !is_array($media) ) {
            $this->error('::display_attribution: Media Not Found');
            return FALSE;
        }        
        $artist = @$media['artist'];
        if( !$artist ) {
            $artist = 'Unknown';
            $copyright = '';
        } else {
            $artist = $this->truncate( strip_tags($artist), $artist_truncate );
            $copyright = '&copy; ';
        }
        $licenseshortname = @$media['licenseshortname'];
        switch( $licenseshortname ) {
            case 'No restrictions': 
            case 'Public domain':
                $licenseshortname = 'Public Domain';
                $copyright = ''; break;
        }        
        $r = "$copyright $artist / $licenseshortname";
        return $r;
    }

    //////////////////////////////////////////////////////////
    function display_attribution( $media, $title_truncate=250, $artist_truncate=48 ) {
        $infourl = $this->url('info') . '?i=' . $media['pageid'];
        $title = htmlspecialchars($this->strip_prefix($media['title']));
        return '<div class="mediatitle left">'
        . '<a href="' . $infourl . '" title="' . htmlentities($title) . '">' 
        . $this->truncate($title, $title_truncate) 
        . '</a></div>'
        . '<div class="attribution left">'
        . '<a href="' . $infourl . '">' 
        . $this->display_licensing($media, $artist_truncate) 
        . '</a></div>';
    }

} // END class smt 
