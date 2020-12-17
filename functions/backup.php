<?php
/** Function list:
*  - run_backup()
*  - human_filesize()
*  - getFileList()
*
* backup function call arround line 162:
* run_backup($folder_to_backup, $backup_file_name, $not_to_backup);
*/


/** for debugging only
*ini_set('display_errors', 1);
*ini_set('display_startup_errors', 1);
*error_reporting(E_ALL);
*/


/**   Zip-the-Space
* just a copy&forget backup script
* with default settings all files and folder arround this script
* will be instandly archived in a easy recognizable .zip file
* and saved in the folder "backups", which is not archived with.
* the backup folder will be created in the first runs
* after the run, a list with all archives will be shown to download the backup
*
* feel free to change or extend the script and make it public
*/


// path to the folder to be archived
// leave empty '' for the root folder or point without circumnavigating slashes 'content/images'
$folder_to_backup = './';

// path to the folder, where the backups are inside, without circumnavigating slashes too
$backup_folder_name = 'backups';
$backup_folder_path = 'functions/backups';

// it is not a goof idea to backup the backups, so lets exclude the backup_folder
// or if you want to protect your privacy:  array($backup_folder, 'privat', 'config');
$not_to_backup = array($backup_folder_name);

// name of the script for filenamed
$script_folder_name = 'picoDocs';


/**
hopefully nothing else to change from here :-)
**/


// string "magic" for the name of the backup file
// no "/" in the filename for the case, that the "folder_to_backup" contains a path like "content/images"
if (!empty($folder_to_backup)) $line = str_replace('/', '_', '/' . $folder_to_backup); else $line = '';

// just the folder where this script runs and from where we look out
//$script_folder_name = str_replace("/", "", substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/") + 1));

// the path and name of the backup file, loos like this: "backups/1982_02_10_23_45_picowiki.zip" or "backups/1982_02_10_23_45_picowiki_content_images.zip"
$backup_file_name   = $backup_folder_path . '/' . $script_folder_name . '_' . date("Y_m_d_H_i_s") .  '.zip';


// create the backup_folder if it not exist
if (!file_exists($backup_folder_path)) {mkdir($backup_folder_path, 0777, true);}


// Backup function
function run_backup($folder_to_backup, $backup_file_name, $not_to_backup)
{
    // Remove existing archive
    if (file_exists($backup_file_name)){unlink($backup_file_name);}

    $zip = new ZipArchive();
    if (!$zip->open($backup_file_name, ZIPARCHIVE::CREATE)){return false;}

    $folder_to_backup  = str_replace('\\', '/', realpath($folder_to_backup));
    if (is_dir($folder_to_backup) === true)
    {
      $files = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
          new RecursiveDirectoryIterator(
            $folder_to_backup,RecursiveDirectoryIterator::SKIP_DOTS),
            function ($fileInfo, $key, $iterator) use ($not_to_backup) {
              return $fileInfo->isFile() || !in_array($fileInfo->getBaseName(), $not_to_backup);
              } ) );

            foreach ($files as $file)
            {
            // run through the filelist and add to zip
            $file = realpath($file);
            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($folder_to_backup . '/', '', $file . '/'));
            }
            elseif (is_file($file) === true)
            {
                $zip->addFromString(str_replace($folder_to_backup . '/', '', $file) , file_get_contents($file));
            }
        }
    }
    elseif (is_file($folder_to_backup) === true)
    {
        $zip->addFromString(basename($folder_to_backup) , file_get_contents($folder_to_backup));
    }
    return $zip->close();
} // Backup function



// get filesize of the archives for the list
function human_filesize($bytes, $decimals = 2)
{
    $factor   = floor((strlen($bytes) - 1) / 3);
    if ($factor > 0) $sz       = 'KMGT';
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor - 1] . 'B';
} // get filesize



// make list of all archives
function getFileList($dir, $recurse = false)
{
    $retval  = []; // array to hold return value
    if (substr($dir, -1) != "/"){$dir .= "/";} // add trailing slash if missing

    // open pointer to directory and read list of files
    $dir_list = @dir($dir) or die("getFileList: Failed opening directory {$dir} for reading");
    while (false !== ($entry  = $dir_list->read()))
    {
        // skip hidden files
        if ($entry{0} == ".") continue;
        if (is_dir("{$dir}{$entry}"))
        {
            if ($recurse && is_readable("{$dir}{$entry}/"))
            {
                if (is_array(getFileList("{$dir}{$entry}/", true)))
                {
                    $retval = array_merge($retval, getFileList("{$dir}{$entry}/", true));
                }
                else{continue;}
            }
        }
        elseif (is_readable("{$dir}{$entry}"))
        {
            $retval[] = [
              'name'    => "<a href=\" " . str_replace("./", "", "{$dir}{$entry}") . " \">" . str_replace("./", "", "{$entry}") . "</a>",
              'type'    => mime_content_type("{$dir}{$entry}") ,
              'size'    => human_filesize(filesize("{$dir}{$entry}") , 2) ,
              'lastmod' => date("d. F Y H:i", filemtime("{$dir}{$entry}")) ];
        }
    }
    $dir_list->close();
    //print_r($retval);

    foreach ($retval as $key => $row) // sort array
    {
        $name[$key] = $row['name'];
    }
    if (isset($name) and is_array($name))
    {
        array_multisort($name, SORT_DESC, SORT_STRING, $retval);
    }
    // print_r($retval);
    return $retval;
}// make list of all archives


// get size of the lastest backup file
$files          = scandir($backup_folder_path, SCANDIR_SORT_DESCENDING);
$last_file_size = filesize($backup_folder_path.'/'.$files[0] );


// run di dance
run_backup($folder_to_backup, $backup_file_name, $not_to_backup);


// get size of this backupfile
$files          = scandir($backup_folder_path, SCANDIR_SORT_DESCENDING);
$this_file_name = $files[0];
$this_file_size = filesize($backup_folder_path.'/'.$files[0] );

// delete this backup, if the same size like last backup
if ($last_file_size == $this_file_size){
  // unlink($backup_folder_path.'/'.$files[0]);
  // echo 'no changes';
}



// HTML output of all archives

// make link to go back
if (isset($_SERVER['HTTP_REFERER'])){
    $link = $_SERVER['HTTP_REFERER'];
}else{
    $link = "https://" . $_SERVER['SERVER_NAME'] . substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/") + 1);
}

//echo str_replace("/","",substr($_SERVER['SCRIPT_NAME'],0,strrpos($_SERVER['SCRIPT_NAME'],"/")+1));

// output file list in HTML TABLE format
$dirlist = getFileList($backup_folder_path, 1); // 1 = include subdirectories

// echo "<!DOCTYPE html>\n";
// echo "<style>\n";
// echo "body {font-family: Lucida Console, Monaco, monospace;}\n";
// echo "a:link, a:visited {text-decoration: none; color: #000;}\n";
// echo "a:hover, a:active {text-decoration: none; color: grey;}\n";
// echo "ul {display: table; padding-left: 0;  line-height: 150%;}\n";
// echo "li {display: table-row;}\n";
// echo "li.head {font-weight: bold; display: table-row;}\n";
// echo "li > span {display: table-cell; padding: 0 1em;}\n";
// echo ".right {text-align: right;}\n";
// echo "</style>\n";
// echo "<h1>{$script_folder_name}-Backups</h1>\n";
// echo "<a href={$link}>go back: {$script_folder_name}</a>\n";
// echo "<ul>\n<li class=\"head\"><span>Name</span><span>Size</span><span>Last Modified</span></li>\n";
// foreach ($dirlist as $file)
// {
//     echo "<li><span>{$file['name']}</span><span class=\"right\">{$file['size']}</span><span>{$file['lastmod']}</span></li>\n";
// }
// echo "</ul>\n\n";


// $html  = "<!DOCTYPE html>\n";
// $html .= "<style>\n";
// $html .= "body {font-family: Lucida Console, Monaco, monospace;}\n";
// $html .= "a:link, a:visited {text-decoration: none; color: #000;}\n";
// $html .= "a:hover, a:active {text-decoration: none; color: grey;}\n";
// $html .= "ul {display: table; padding-left: 0;  line-height: 150%;}\n";
// $html .= "li {display: table-row;}\n";
// $html .= "li.head {font-weight: bold; display: table-row;}\n";
// $html .= "li > span {display: table-cell; padding: 0 1em;}\n";
// $html .= ".right {text-align: right;}\n";
// $html .= "</style>\n";
$html .= "<h1>{$script_folder_name}-Backups</h1>\n";
$html .= "<a href={$link}>go back: {$script_folder_name}</a>\n";
$html .= "<ul>\n<li class=\"head\"><span>Name</span><span>Size</span><span>Last Modified</span></li>\n";
foreach ($dirlist as $file)
{
    $html .= "<li><span>{$file['name']}</span><span class=\"right\">{$file['size']}</span><span>{$file['lastmod']}</span></li>\n";
}
$html .= "</ul>\n\n";
echo $html;


?>
