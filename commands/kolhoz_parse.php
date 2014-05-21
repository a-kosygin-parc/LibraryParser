<?php

set_time_limit(0);

ini_set('memory_limit', '512M');
mysql_connect('localhost', 'root', '1') or die(mysql_error());
mysql_select_db('library') or die(mysql_error());

define('DJVUDECODE','L:\app\OCR\djvudecode\djvudecode.exe');
define('TESSERACT','"C:\Program Files (x86)\Tesseract-OCR\tesseract.exe"');

//define('DJVUTMPDIR','c:\temp\parse-djvu');

define('DJVUTMPDIR','C:\temp\parse-djvu');
global $flnm;
$flnm = '';
if (!is_dir(DJVUTMPDIR)) mkdir(DJVUTMPDIR, 0777, true);

$dir = "L:/biblioteka/kolhoz/";

$res = array();
recurse($dir, $res, 'process');

mysql_close();

function process($filename) {
	if (!file_exists($filename)) {
		throw new \Exception('File ' . $filename . ' not found.');
	}

	$tmp_content = file_get_contents($filename);
	$md5 = md5($tmp_content);
	unset($tmp_content);

	$status = getFileParseStatus($filename);

	if ($md5 != getFileHash($filename)) {
		//mysql_query('UPDATE files SET `hash` = "' . mysql_real_escape_string($md5) . '" WHERE filename = "' . mysql_real_escape_string($filename) . '"') or die(mysql_error());
	}

	if (in_array($status, array('recognited'))) {
		return;
	}

	$path_parts = pathinfo($filename);

	global $flnm;
	$flnm = $filename;

	if (!in_array(strtolower($path_parts['extension']), array('djvu', 'djv'))) {
		throw new \Exception($filename . ' not is DJVU expected.');
	}
	var_dump($status);

	if (!$status) {
		mysql_query('INSERT INTO `files` (`filename`,`extension`,`create_dt`,`parse_status`,`hash`) VALUES (
			"' . mysql_real_escape_string($filename) . '",
			"' . mysql_real_escape_string(strtolower($path_parts['extension'])) . '",
			"' . date('Y-m-d H:i:s') . '",
			"none",
			"' . $md5 . '"
		)') or die(mysql_error());

		$id_file = mysql_insert_id();
	}
	else{
		$id_file = getFileId($filename);
	}

	if ($status != 'process') {
		system(DJVUDECODE . ' --output-format=tif --dpi=300 "' . $filename . '" ' . DJVUTMPDIR);
		markProcessed($filename);
	}


	$res = '';

	recurse(DJVUTMPDIR . '/', $res, function($img_file, $params = array()){

		if (!file_exists($img_file)) {
			throw new \Exception('File ' . $filename . ' not found.');
		}

  		$path_parts = pathinfo($img_file);

	  	global $flnm;

		if (!in_array(strtolower($path_parts['extension']), array('tif', 'tiff'))) {
   			return;
		}

		$page = $path_parts['filename'];

		if (isPageRecognited($flnm, $page)) {
			file_exists($img_file) && unlink($img_file);
			file_exists($img_file . '.txt') && unlink($img_file . '.txt');
			echo $flnm . ' Page=' . $page . ' skipped.' . "\n";
			return;
		}

		system(TESSERACT . ' ' . $img_file . ' ' . $img_file . ' -l eng');

		$text_rus = file_get_contents($img_file . '.txt');

		system(TESSERACT . ' ' . $img_file . ' ' . $img_file . ' -l rus');

		$text_eng = file_get_contents($img_file . '.txt');

		mysql_query('
			INSERT INTO recognited (`filename`,`page`,`text_rus`,`text_eng`,`id_file`) VALUES (
				"' . mysql_real_escape_string($flnm) . '",
				"' . mysql_real_escape_string($page) . '",
				"' . mysql_real_escape_string($text_rus) . '",
				"' . mysql_real_escape_string($text_eng) . '",
				' . intval($params['id_file']) . '
			)');
		unlink($img_file);
		unlink($img_file . '.txt');
	}, array('id_file' => $id_file));

	markRecognited($filename);
}



function recurse($dir, &$res, $func, $params = array()) {
  if (is_dir($dir)) {
    if ($dh = opendir($dir)) {

        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file=='..') continue;
            if (is_dir($dir.$file)) {
                recurse($dir.$file.'/',$res, $func);
            } else {
				try {
                	$func($dir.$file, $params);
				}
				catch (\Exception $e) {
					echo $e->getMessage() . "\n";
				}
            }
        }
        closedir($dh);
    }
  }
}

function q($query, $key = false)
{
	$res = mysql_query($query) or die(mysql_error());
	if (!$res) {
		return false;
	}

	if (!preg_match('^SELECT')) {
		return true;
	}
	$rows = array();
	while (($row = mysql_fetch_assoc($res)) !== false) {
		if ($key) {
			$rows[$row[$key]] = $row;
		}
		else {
			$rows[] = $row;
		}
	}
	mysql_free_result($res);
	return $rows;
}

function isFileRecognited($filename)
{
	$res = mysql_query('SELECT count(*) AS cnt FROM `files` WHERE `filename` = "' . mysql_real_escape_string($filename) . '"') or die(mysql_error());
	if ($res && ($row = mysql_fetch_assoc($res)) && $row['cnt'] > 0) {
		return true;
	}
	return false;
}

function isPageRecognited($filename, $page)
{
	$res = mysql_query('SELECT count(*) AS cnt FROM `recognited` WHERE `filename` = "' . mysql_real_escape_string($filename) . '" AND `page` = "' . mysql_real_escape_string($page) . '"') or die(mysql_error());
	if ($res && ($row = mysql_fetch_assoc($res)) && $row['cnt'] > 0) {
		return true;
	}
	return false;
}

function getFileParseStatus($filename)
{
	$res = mysql_query('SELECT parse_status FROM `files` WHERE `filename` = "' . mysql_real_escape_string($filename) . '"') or die(mysql_error());
	if ($res && ($row = mysql_fetch_assoc($res)) && !empty($row['parse_status'])) {
		return $row['parse_status'];
	}
	return false;
}

function getFileId($filename)
{
	$res = mysql_query('SELECT id_file FROM `files` WHERE `filename` = "' . mysql_real_escape_string($filename) . '"') or die(mysql_error());
	if ($res && ($row = mysql_fetch_assoc($res)) && !empty($row['id_file'])) {
		return $row['id_file'];
	}
	return false;
}

function getFileHash($filename)
{
	$res = mysql_query('SELECT hash FROM `files` WHERE `filename` = "' . mysql_real_escape_string($filename) . '"') or die(mysql_error());
	if ($res && ($row = mysql_fetch_assoc($res)) && !empty($row['hash'])) {
		return $row['hash'];
	}
	return false;
}

function markRecognited($filename)
{
	mysql_query('UPDATE files SET parse_status = "recognited" WHERE filename="' . mysql_real_escape_string($filename) . '"') or die(mysql_error());
}

function markProcessed($filename)
{
	mysql_query('UPDATE files SET parse_status = "process" WHERE filename="' . mysql_real_escape_string($filename) . '"') or die(mysql_error());
}
