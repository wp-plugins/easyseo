<?php
/*
Plugin Name: easySEO
Plugin URI: http://suchmaschinenoptimierung.10010.de
Description: Einfaches SEO Tool f&uuml;r die Bereitstellung der wichtigsten Anpassungen: Manipulation des title-Tag, Description bearbeiten, Sitemaps, noindex-Tags f&uuml;r Archive und Kategorie
Version: 1.2
Author: Jens Bekersch
Author URI: http://www.bekersch.com
License: GPL 2
    Copyright 2010  PLUGIN_Jens Bekersch

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


define('WPDOMAIN', $_SERVER['DOCUMENT_ROOT'] . '/');
define('PLUGIDIR', dirname(__FILE__) . '/');

function theTitle() {
/*
* Variablen Deklaration
*/
 $boolGetSite   = is_single();
 $boolFrontPage = is_front_page();
 $boolCategory  = is_category();
 $boolPage      = is_page();

 $strBlogName   = get_bloginfo();
/*
* Dynamische title-tags, description meta-tag, nofollow-Anweisungen
*/
 if ($boolGetSite == true) {                                       //Artikel
  foreach ($GLOBALS['posts'] as $strTitle) {
   $newStrTitle = $strTitle->post_title;
   $strDescript = $strTitle->post_excerpt;
  }
  echo '
       <title>'.$newStrTitle.'</title>
       ';
  if (!empty($strDescript)) {
  echo '
       <meta name="description" content="'.$strDescript.'">
       ';
  }
 } elseif ($boolFrontPage == true) {                               //Startseite
  echo '
       <title>'.$strBlogName.'</title>
       ';
 } elseif ($boolCategory == true) {                                //Kategorie
  $intCatId     = intval(get_query_var('cat'));
  $objCategory  = get_category($intCatId);
  echo '
       <title>'.$objCategory->name.'</title>
       <meta name="robots" content="noindex, follow">
       ';
 } elseif ($boolPage == true) {                                     //Seite
  $intPageId    = intval(get_query_var('page_id'));
  $objPage      = get_page($intPageId);
  echo '
       <title>'.$objPage->post_name.'</title>';
 } else {
  echo '
       <title>'.$strBlogName.'</title>
       <meta name="robots" content="noindex, follow">
       ';
 }
}
/*
* Noindex für Feed
*/
  function TheFeedNoIndex() {
    echo '<xhtml:meta xmlns:xhtml="http://www.w3.org/1999/xhtml" name="robots" content="noindex" />' . "\n";
  }
/*
* Urllist + XML Sitemap
*/

//auf Existenz prüfen

if (!file_exists(WPDOMAIN.'urllist.txt')) {

        $fp = @fopen(WPDOMAIN.'urllist.txt','w');
        if (isset($fp)) {

         try {

              @fclose($fp);
              throw new Exception('E1');
              
             } catch (Exception $e) {

                function checkUrllist(){
                echo '<div style="background-color: red; width: 800px; font-weight: bold">Warnung (EasySEO): urllist.txt konnte nicht erzeugt werden! Bitte erstellen Sie diese Datei manuell!</div>';
                }
                add_action('admin_notices', 'checkUrllist', 1);

             }
             
                if(!is_writable($fp)) {
                function checkUrllist2(){
                echo '<div style="background-color: red; width: 800px; font-weight: bold">Warnung (EasySEO): urllist.txt ist nicht beschreibbar! Bitte &auml;ndern Sie die Dateirechte.</div>';
                }
                add_action('admin_notices', 'checkUrllist2', 1);
                }
        }
}
if (!file_exists(WPDOMAIN.'sitemap.xml')) {

        $fp = @fopen(WPDOMAIN.'sitemap.xml','w');
        if (isset($fp)) {

         try {

              @fclose($fp);
              throw new Exception('E1');

             } catch (Exception $e) {

                function checkUrllist(){
                echo '<div style="background-color: red; width: 800px; font-weight: bold">Warnung (EasySEO): sitemap.xml konnte nicht erzeugt werden! Bitte erstellen Sie diese Datei manuell!</div>';
                }
                add_action('admin_notices', 'checkUrllist', 1);

             }

                if(!is_writable($fp)) {
                function checkUrllist2(){
                echo '<div style="background-color: red; width: 800px; font-weight: bold">Warnung (EasySEO): sitemap.xml ist nicht beschreibbar! Bitte &auml;ndern Sie die Dateirechte.</div>';
                }
                add_action('admin_notices', 'checkUrllist2', 1);
                }
        }
}

/*
* Urllist prüfen, erstellen und bei neuen Posts updaten
*/
function newUrllistXMLEntry() {
 //Urllist auf Vollständigkeit prüfen
 //Anzahl der publizierten posts in der Datenbank
 global $wpdb;
 $post_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish';"));
 //Anzahl der Urls in der urllist (posts + 1[Startseite])
 $intUC = count(file(WPDOMAIN.'urllist.txt'));
 if ($post_count != $intUC || $post_count != ($intUC+1) || $post_count > ($intUC+1)) {
    $strDomain      = get_option('siteurl');
    $arrPermaUrl = array($strDomain);
    if (get_option('permalink_structure') != '') {
     $objPostUris = $wpdb->get_results("SELECT ID, post_date, post_name FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY ID;");
     foreach ($objPostUris as $varPosts) {
      //Datumbestandteile extrahieren
      $arrDateTime        = explode(' ',$varPosts->post_date);
      $arrDate            = explode('-',$arrDateTime[0]);
      //Permalink Struktur
      $strPStruct         = get_option('permalink_structure');
      //Daten ersetzen
      $arrFind            = array('%year%','%monthnum%','%day%','%postname%','%post_id%');
      $arrReplace         = array($arrDate[0],$arrDate[1],$arrDate[2],$varPosts->post_name,$varPosts->ID);
      $arrPermaUrl[]      = $strDomain.str_replace($arrFind,$arrReplace,$strPStruct);
      }
     } else {
     $objPostUris = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY ID;");
     foreach ($objPostUris as $strUri)  {
      $arrPermaUrl[]      = $strUri;
     }
     }
      //Urllist erstellen
      $fpu = fopen(WPDOMAIN.'urllist.txt',"w+");
      if(isset($fpu)) {
       foreach ($arrPermaUrl as $strZeile) {
       fputs($fpu,"$strZeile\n");
       }
       fclose($fpu);
      }
      //xml Sitemap erstellen
      $fpg = fopen(WPDOMAIN.'sitemap.xml','w');
      if(isset($fpg)) {
       $strXMLHeader     = file_get_contents(PLUGIDIR.'gs-header.xml');
       $arrXML           = array($strXMLHeader);
       foreach ($arrPermaUrl as $strXZeile) {
       $arrXML[]         =  '<url>
<loc>'.$strXZeile.'</loc>
</url>';
       }
       $arrXML[]         = '</urlset>';
       foreach ($arrXML as $strXZFin) {
       fputs($fpg,"$strXZFin\n");
       }
       fclose($fpg);
      }
 } else {
     //Neuen Post in Urllist und XML-Datei einfügen
     if (get_option('permalink_structure') != '') {
       $objPostData = $wpdb->get_results("SELECT ID, post_date, post_name FROM $wpdb->posts WHERE ID = '$post_ID'");
       $arrDateTime        = explode(' ',$varPosts->post_date);
       $arrDate            = explode('-',$arrDateTime[0]);
       //Permalink Struktur
       $strPStruct         = get_option('permalink_structure');
       //Daten ersetzen
       $arrFind            = array('%year%','%monthnum%','%day%','%postname%','%post_id%');
       $arrReplace         = array($arrDate[0],$arrDate[1],$arrDate[2],$varPosts->post_name,$varPosts->ID);
       $strPermaUrl        = $strDomain.str_replace($arrFind,$arrReplace,$strPStruct);
       
       $fpu = fopen(WPDOMAIN.'urllist.txt',"a");
        if(isset($fpu)) {
         fputs($fpu,"$strPermaUrl\n");
        }
        fclose($fpu);
        
       $strXMLData         = file_get_contents(WPDOMAIN.'sitemap.xml');
       $strNewXMLData       = str_replace('</urlset>',$strPermaUrl.'\n</urlset>',$strXMLData);
       $fpd = fopen(WPDOMAIN.'sitemap.xml','w');
       if (isset($fpd)) {
       fputs($fpg,"$strNewXMLData\n");
       }
       fclose($fpg);
      } else {
       $objPostData = $wpdb->get_results("SELECT ID,guid FROM $wpdb->posts WHERE ID = '$post_ID'");
       $strUrl = $objPostData->guid;
       $fpu = fopen(WPDOMAIN.'urllist.txt',"a");
        if(isset($fpu)) {
         fputs($fpu,"$strUrl\n");
        }
        fclose($fpu);

       $strXMLData         = file_get_contents(WPDOMAIN.'sitemap.xml');
       $strNewXMLData       = str_replace('</urlset>',$strUrl.'\n</urlset>',$strXMLData);
       $fpd = fopen(WPDOMAIN.'sitemap.xml','w');
       if (isset($fpd)) {
       fputs($fpg,"$strNewXMLData\n");
       }
       fclose($fpg);
      }
 }
}
/*
* Funktionen aufrufen
*/
add_action('wp_head', 'theTitle');
add_action ( 'publish_post', 'newUrllistXMLEntry' );
add_action('commentsrss2_head', 'TheFeedNoIndex');
add_action('rss2_head', 'TheFeedNoIndex');
remove_action('wp_head', 'rsd_link'); // Really Simple Discovery Eintrag entfernen
remove_action('wp_head', 'wlwmanifest_link');  // Windows Live Writer Link entfernen
remove_action('wp_head', 'wp_generator');  // Versionsnummern-Ausgabe entfernen
?>
