<?php

/*** PHP Drop2Browse v0.2.10a (14/07/2017) by Szilárd Barta (Free Software, GNU GPLv3 License) - bartaszili (at) gmail (dot) com ***/
/*** License: https://www.gnu.org/licenses/gpl.txt ***/
/*** Includes: jQuery v3.2.1, Bootstrap v3.3.7  ***/
/*** Description: Single file - drop in - webserver's Document Root browser with responsive design. ***/
/*** Project home: https://github.com/bartaszili/PHP-Drop2Browse ***/

$debug = array(); // collects varibles values
$protection = 0; // initialize protection from outside hacks
$version = 'v0.2.10a (14/07/2017)';

/* change this to display variables for troubleshooting */
$troubleshooting = false;

/* edit here to customize Useful Links menu */
$links = array(
	array(
		'url' => 'info.php',
		'name' => 'PhpInfo'
	),
	array(
		'url' => 'phpliteadmin.php',
		'name' => 'PhpLiteAdmin'
	),
	array(
		'url' => 'https://github.com/bartaszili/PHP-Drop2Browse',
		'name' => 'Check new version'
	)
);

/* edit here to customize initial premissions and ownership */
$basic_dpr = 755; // Directory/Folder premission: 755 (drwx-r-xr-x)
$basic_fpr = 644; // File premission: 644 (-rw-r--r--)
$basic_uid = 33; // User ID: 33 (www-data)
$basic_gid = 33; // Group ID: 33 (www-data)

$divider = '?=|=?';
$information = '
<p>PHP Drop2Browse is a single file tool built for one special purpose,
to view files and folders in webserver\'s Document Root in your browser.
Check permissions and ownership for troubleshooting.
It is possible to drop the file anywhere under the Document Root
and simply call it in browser.
Responsive designed allows mobile phone friendly viewing.</p>
<h5>Features</h5>
<ul>
<li>Lightweight</li>
<li>Secure - browsing stays within the server\'s Document Root</li>
<li>Highlighted index files for quick identification</li>
<li>Simple smart filters to find premission or ownership issues/differences</li>
<li>Responsible design</li>
<li>Sortable columns</li>
<li>Name, size, date, permissions, user, group information displayed</li>
<li>Fully hackable</li>
<li>Manually editable menu to link your favourite little tools</li>
</ul>
<h5>Filters</h5>
<p>If a filter value is empty, will be ignored during scan. That gives flexibility to this function.</p>
';

$alert = false; // initialize premission/ownership problem feedback
$alert_msg = '<li>Premmission and/or ownership problems in highlighted row(s)</li>';
$alert_msg .= '<li>Some configuration files can have more strict permissions (like 0444)</li>';
$alert_msg .= '<li>Adjust the filtering criteria in the top menu form as necessary</li>';

// initialize error handling on filters form inputs
$error = false;
$errors = 0;
$inputError1 = false;
$inputError2 = false;
$inputError3 = false;
$inputError4 = false;

/********************************/
/*** 01: Self locating basics ***/
/********************************/

/***  name of this file ***/
$thisFile = basename(__FILE__);
$debug['thisFile'] = $thisFile;

/***  relative path to this file ***/
$e_phpSelf = explode(DIRECTORY_SEPARATOR, $_SERVER['PHP_SELF']);
unset($e_phpSelf[count($e_phpSelf) - 1]);
$thisFolder = implode('/', $e_phpSelf);
$debug['thisFolder'] = $thisFolder;

/***  absolute path to document root ***/
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$debug['docRoot'] = $docRoot;

/*********************/
/*** 02: Functions ***/
/*********************/

/*** dynamic file size view ***/
function fileSizeFormat($size) {
	$sizes = array('YiB', 'ZiB', 'EiB', 'PiB', 'TiB', 'GiB', 'MiB', 'KiB', 'B');
	$total = count($sizes);
	while($total-- && $size > 1024) $size /= 1024;
	$return = round($size, 2).$sizes[$total];
	return $return;
}

/*** to get a quick auto highligth on index files in folder view table ***/
function is_indexFile($fileName, $fileExtension) {
	$a = array('default', 'index', 'start');
	$b = array('htm', 'html', 'phtml', 'shtml', 'xhtml', 'php', 'php3', 'php4', 'php5', 'php7');
	$match1 = 0;
	$match2 = 0;
	for($i = 0; $i < count($a); $i++) {
		if($a[$i] == strtolower($fileName)) {
			$match1++;
		}
	}
	if($match1 == 1) {
		for($i = 0; $i < count($b); $i++) {
			if($b[$i] == strtolower($fileExtension)) {
				$match2++;
			}
		}
		if($match2 == 1) {
			$match = true;
		}
		else {
			$match = false;
		}
	}
	else {
		$match = false;
	}
	return $match;
}

/*** highligth row if permission/ownership are different ***/
function is_premIssue($dpr, $fpr, $uid, $gid) {
	global $form_dpr;
	global $form_fpr;
	global $form_uid;
	global $form_gid;
	$return = false;
	if($dpr != '') {
		$dpr = (integer) $dpr;
		if($form_dpr != '' && $dpr != $form_dpr) { $return = true; }
	}
	if($fpr != '') {
		$fpr = (integer) $fpr;
		if($fpr != $form_fpr) { $return = true; }
	} 
	$uid = (integer) $uid;
	$gid = (integer) $gid;
	if($form_uid != '' && $uid != $form_uid) { $return = true; }
	if($form_gid != '' && $gid != $form_gid) { $return = true; }
	return $return;
} 

/*** to sort the folder view table when clicking on table header columns ***/
function priority4colSort($data, $sort) {
	$sort_option = array('a' => SORT_ASC, 'd' => SORT_DESC);
	foreach ($data as $key => $row) {
		$name[$key]  = $row['name'];
		$ext[$key] = $row['ext'];
		$size[$key] = $row['size'];
		$date[$key] = $row['date'];
	}
	switch($sort[4]) {
		case 0:
			array_multisort($name, $sort_option[$sort[0]], $ext, $sort_option[$sort[1]], $size, $sort_option[$sort[2]], $date, $sort_option[$sort[3]], $data);
			break;
		case 1:
			array_multisort($ext, $sort_option[$sort[1]], $name, $sort_option[$sort[0]], $size, $sort_option[$sort[2]], $date, $sort_option[$sort[3]], $data);
			break;
		case 2:
			array_multisort($size, $sort_option[$sort[2]], $name, $sort_option[$sort[0]], $ext, $sort_option[$sort[1]], $date, $sort_option[$sort[3]], $data);
			break;
		case 3:
			array_multisort($date, $sort_option[$sort[3]], $name, $sort_option[$sort[0]], $ext, $sort_option[$sort[1]], $size, $sort_option[$sort[2]], $data);
			break;
	}
	return $data;
}

/*** to validate form input numbers ***/
function validateInputNumber ($input, $type="float", $range="ALL") {
// type = integer,(float)
// range = (ALL),NOT_ZERO,FROM_NOT_ZERO,FROM_ZERO
	$type = strtolower($type);
	$range = strtoupper($range);
	if (!isset($input)) {
		return false;
	}
	else {
		preg_match("/^(-|\+|)(\d+|)(\.|,|)(\d+|)$/",$input,$is_number);
		if ($is_number == false) {
			return false;
		}
		else {
			if ($is_number[1] == "+") {
				$is_number[1] = "";
			}
			if ($is_number[2] == "") {
				$is_number[2] = 0;
			}
			if ($is_number[4] == "") {
				$is_number[4] = 0;
			}
			$n1 = $is_number[2];
			$n2 = $is_number[4];
			$input = $is_number[1].$is_number[2].".".$is_number[4];
			$input = (float) $input;
			$n = "float";
			if ($n2 == 0) {
				$n = "integer";
			}
			if ($type == "integer" && $n != $type) {
				return false;
			}
			else {
				switch ($range) {
					case "NOT_ZERO":
						if ($input != 0) {
							return true;
						}
						else {
							return false;
						}
						break;
					case "FROM_NOT_ZERO":
						if ($input > 0) {
							return true;
						}
						else {
							return false;
						}
						break;
					case "FROM_ZERO":
						if ($input >= 0) {
							return true;
						}
						else {
							return false;
						}
						break;
					case "ALL":
						return true;
						break;
				}
			}
		}
	}
}

/*************************************/
/*** 03: Get the data sent by form ***/
/*************************************/

// get filters
if(isset($_REQUEST['form_filters_'])) {
	$form_filters_ = $_REQUEST['form_filters_'];

	if(isset($_REQUEST['form_filters_dpr']) && $_REQUEST['form_filters_dpr'] != '') {
		$form_filters_dpr = $_REQUEST['form_filters_dpr'];
		if (!validateInputNumber($form_filters_dpr,'integer','FROM_ZERO')) {
			$inputError1 = true;
			$errors++;
			$error_msg .= '<li>Has to be a pozitive round number<br /><span class ="btn btn-xs btn-danger">Dir premissions</span></li>';
		}
	}
	else { $form_filters_dpr = ''; }
	
	if(isset($_REQUEST['form_filters_fpr']) && $_REQUEST['form_filters_fpr'] != '') {
		$form_filters_fpr = $_REQUEST['form_filters_fpr'];
		if (!validateInputNumber($form_filters_fpr,'integer','FROM_ZERO')) {
			$inputError2 = true;
			$errors++;
			$error_msg .= '<li>Has to be a pozitive round number<br /><span class ="btn btn-xs btn-danger">File premissions</span></li>';
		}
	}
	else { $form_filters_fpr = ''; }
	
	if(isset($_REQUEST['form_filters_uid']) && $_REQUEST['form_filters_uid'] != '') {
		$form_filters_uid = $_REQUEST['form_filters_uid'];
		if (!validateInputNumber($form_filters_uid,'integer','FROM_ZERO')) {
			$inputError3 = true;
			$errors++;
			$error_msg .= '<li>Has to be a pozitive round number<br /><span class ="btn btn-xs btn-danger">User ID</span></li>';
		}
	}
	else { $form_filters_uid = ''; }
	
	if(isset($_REQUEST['form_filters_gid']) && $_REQUEST['form_filters_gid'] != '') {
		$form_filters_gid = $_REQUEST['form_filters_gid'];
		if (!validateInputNumber($form_filters_gid,'integer','FROM_ZERO')) {
			$inputError4 = true;
			$errors++;
			$error_msg .= '<li>Has to be a pozitive round number<br /><span class ="btn btn-xs btn-danger">Group ID</span></li>';
		}
	}
	else { $form_filters_gid = ''; }
	
	if ($errors > 0) {
		$error = true;
		$error_msg .= '<li>Filtering suspended until you apply valid data in highlighted input field(s)</li>';
	}
	
	$form_filters_ .= $divider.$form_filters_dpr.$divider.$form_filters_fpr.$divider.$form_filters_uid.$divider.$form_filters_gid;
}
else { $form_filters_ = 0; }
settype($form_filters_, 'string');
$debug['errors'] = $errors;
$debug['form_filters_'] = $form_filters_;

if(isset($_REQUEST['form_navigation'])) { $form_navigation = $_REQUEST['form_navigation']; }
else { $form_navigation = 0; }
settype($form_navigation, 'string');
$debug['form_navigation'] = $form_navigation;

if(isset($_REQUEST['form_sort'])) { $form_sort = $_REQUEST['form_sort']; }
else { $form_sort = 0; }
settype($form_sort, 'string');
$debug['form_sort'] = $form_sort;

if(isset($_REQUEST['form_folder'])) { $form_folder = $_REQUEST['form_folder']; }
else { $form_folder = 0; }
settype($form_folder, 'string');
$debug['form_folder'] = $form_folder;

/*********************************/
/*** 04: Process recieved data ***/
/*********************************/

if ($form_navigation != '0') {
	$e_form_navigation = explode($divider, $form_navigation);
	$lst = $e_form_navigation[0];
	$srt = $e_form_navigation[1];
	$form_dpr = $e_form_navigation[2];
	$form_fpr = $e_form_navigation[3];
	$form_uid = $e_form_navigation[4];
	$form_gid = $e_form_navigation[5];
}
elseif ($form_sort != '0') {
	$e_form_sort = explode($divider, $form_sort);
	$lst = $e_form_sort[0];
	$srt = $e_form_sort[1];
	$form_dpr = $e_form_sort[2];
	$form_fpr = $e_form_sort[3];
	$form_uid = $e_form_sort[4];
	$form_gid = $e_form_sort[5];
}
elseif ($form_folder != '0') {
	$e_form_folder = explode($divider, $form_folder);
	$lst = $e_form_folder[0];
	$srt = $e_form_folder[1];
	$form_dpr = $e_form_folder[2];
	$form_fpr = $e_form_folder[3];
	$form_uid = $e_form_folder[4];
	$form_gid = $e_form_folder[5];
}
elseif ($form_filters_ != '0') {
	$e_form_filters_ = explode($divider, $form_filters_);
	$lst = $e_form_filters_[0];
	$srt = $e_form_filters_[1];
	$form_dpr = $e_form_filters_[2];
	$form_fpr = $e_form_filters_[3];
	$form_uid = $e_form_filters_[4];
	$form_gid = $e_form_filters_[5];
}
else {
	$lst = $thisFolder;
	$srt = 'aaaa0';
	$form_dpr = $basic_dpr;
	$form_fpr = $basic_fpr;
	$form_uid = $basic_uid;
	$form_gid = $basic_gid;
}
$debug['divider'] = $divider;
$debug['lst'] = $lst;
$debug['srt'] = $srt;
$debug['form_dpr'] = $form_dpr;
$debug['form_fpr'] = $form_fpr;
$debug['form_uid'] = $form_uid;
$debug['form_gid'] = $form_gid;

/*****************************/
/*** 05: Prepare variables ***/
/*****************************/

// hard reset incoming inputs
protection:
if ($protection == 1) {
	$lst = $thisFolder;
	$srt = 'aaaa0';
	$form_dpr = $basic_dpr;
	$form_fpr = $basic_fpr;
	$form_uid = $basic_uid;
	$form_gid = $basic_gid;
	$protection = 0; // to brake infinite loop
}

// explode recieved path
$e_lst = explode('/', $lst);
$c_lst = count($e_lst);
$debug['c_lst'] = $c_lst;

// create navigation links
$nav_lst = '';
$e_nav = array();
$i = 0;
foreach($e_lst as $value) {
	$nav_lst .= $value.'/';
	$record = array('lst' => $nav_lst, 'name' => $i.' ['.$value.']');
	array_push($e_nav, $record);
	$i++;
}
$e_nav[0]['lst'] = '';
$e_nav[0]['name'] = '0 [Document Root]';
$debug['e_nav']=$e_nav;

// folder shows the actual position info
// file is to create links of files listed
// parent folder is a spacial link back
if($c_lst == 1) { // working in Document Root
	$folder = '[Document Root]';
	$file = $lst;
	$parentFolder = $lst;
}
elseif($c_lst > 1) { // working deeper than Document Root
	$folder = '[Document Root]'.$lst;
	$file = substr($lst,1);
	unset($e_lst[$c_lst - 1]);
	$parentFolder = implode('/', $e_lst);
}
else { // if someone tries to hack the input form from outside, this will reset everything
	$protection = 1;
	goto protection;
}
$folder = str_replace('/',' | ',$folder);
$debug['folder'] = $folder;
$debug['file'] = $file;
$debug['parentFolder'] = $parentFolder;

// change to and scan the folder recieved
chdir($docRoot.$lst); // safe if absolute path used
$scan = scandir(getcwd());

// sorting logics for table header
$sort = str_split(substr($srt, 0, 5));
for($i = 0; $i < count($sort) - 1; $i++) {
	if($sort[$i] != 'a' && $sort[$i] != 'd') { $sort[$i] = 'a'; }
}
if($sort[4] != '0' && $sort[4] != '1' && $sort[4] != '2' && $sort[4] != '3') { $sort[4] = '0'; }
$sort_class = array('', '', '', '');
$sort_class[$sort[4]] = ' sort_active';
$sort_sign = array('a' => '+', 'd' => '-');
$sort_oposite = array('a' => 'd', 'd' => 'a');
$sort_matrix = array(
	array($sort[0], $sort[1], $sort[2], $sort[3]),
	array($sort[0], $sort[1], $sort[2], $sort[3]),
	array($sort[0], $sort[1], $sort[2], $sort[3]),
	array($sort[0], $sort[1], $sort[2], $sort[3]));
$sort_matrix[$sort[4]][$sort[4]] = $sort_oposite[$sort[$sort[4]]];
$srt_0 = $sort_matrix[0][0].$sort_matrix[0][1].$sort_matrix[0][2].$sort_matrix[0][3].'0';
$srt_1 = $sort_matrix[1][0].$sort_matrix[1][1].$sort_matrix[1][2].$sort_matrix[1][3].'1';
$srt_2 = $sort_matrix[2][0].$sort_matrix[2][1].$sort_matrix[2][2].$sort_matrix[2][3].'2';
$srt_3 = $sort_matrix[3][0].$sort_matrix[3][1].$sort_matrix[3][2].$sort_matrix[3][3].'3';
$debug['srt_0'] = $srt_0;
$debug['srt_1'] = $srt_1;
$debug['srt_2'] = $srt_2;
$debug['srt_3'] = $srt_3;

/********************************/
/*** 06: Process scaned items ***/
/********************************/

$folderCont = array();
$fileCont = array();
$totalSize = 0;

foreach($scan as $item) {
	$infStat = stat($item);
	if(is_dir($item) && $item != '.' && $item != '..') {
		$folderCont[] = array(
			'fullname' => $item,
			'name' => $item,
			'ext' => '',
			'size' => 0,
			'date' => $infStat['mtime'],
			'index' => '',
			'prems' => substr(sprintf('%o', fileperms($item)), -4),
			'uid' => $infStat['uid'],
			'gid' => $infStat['gid'], 
			'alert' => is_premIssue(substr(sprintf('%o', fileperms($item)), -4),'' , $infStat['uid'], $infStat['gid'])
		);
	}
	elseif(is_file($item)) {
		$fileCont[] = array(
			'fullname' => $item,
			'name' => pathinfo($item, PATHINFO_FILENAME),
			'ext' => pathinfo($item, PATHINFO_EXTENSION),
			'size' => $infStat['size'],
			'date' => $infStat['mtime'],
			'index' => is_indexFile(pathinfo($item, PATHINFO_FILENAME), pathinfo($item, PATHINFO_EXTENSION)),
			'prems' => substr(sprintf('%o', fileperms($item)), -4),
			'uid' => $infStat['uid'],
			'gid' => $infStat['gid'], 
			'alert' => is_premIssue('', substr(sprintf('%o', fileperms($item)), -4), $infStat['uid'], $infStat['gid'])
		);
		$totalSize += $infStat['size'];
	}
}

// apply sorting rules
if(count($folderCont) > 1) {
	$folderCont = priority4colSort($folderCont, $sort);
}
if(count($fileCont) > 1) {
	$fileCont = priority4colSort($fileCont, $sort);
}

// table footer summary
if(count($folderCont) == 1) {
	$totalFolders = count($folderCont).' folder';
}
else {
	$totalFolders = count($folderCont).' folders';
}
if(count($fileCont) == 1) {
	$totalFiles = count($fileCont).' file';
}
else {
	$totalFiles = count($fileCont).' files';
}
$totalSize = fileSizeFormat($totalSize);
$debug['totalFolders'] = $totalFolders;
$debug['totalFiles'] = $totalFiles;
$debug['totalSize'] = $totalSize;

// navigation menu
$html_navigation = '
';
$i = 0;
foreach($e_nav as $data) {
$html_navigation .= '									<li>
										<form class="navbar-form" action="'. $_SERVER['PHP_SELF'].'" method="post">
											<div class="btn-group" data-toggle="buttons">
												<label for="form_navigation_'.$i.'" class="btn btn-link btn-sm">
													<input type="radio" name="form_navigation" id="form_navigation_'.$i.'" class="form-control" value="'.$data['lst'].$divider.$srt.$divider.$form_dpr.$divider.$form_fpr.$divider.$form_uid.$divider.$form_gid.'" onchange="this.form.submit()" />
														'.$data['name'].'
												</label>
											</div>
										</form>
									</li>
';
$i++;
}
$html_navigation .= '								';

// useful links menu
$html_links = '
';
foreach($links as $data) {
$html_links .= '									<li><a href="'.$data['url'].'" target="_blank">'.$data['name'].'</a></li>
';
}
$html_links .= '
								';

// Filters menu
$html_filters = '
									<li class="form_filters_main">
										<form class="navbar-form" action="'.$_SERVER['PHP_SELF'].'" method="post">
											<div class="col-xs-6 col-sm-6">
											<div class="form-group">
												<label for="form_filters_dpr"><abbr title="Directory">Dir</abbr> premissions</label>
												<input type="text" name="form_filters_dpr" id="form_filters_dpr" class="form_filters ';
if($inputError1 == true) { $html_filters .= 'form-control text-right inputError'; } else { $html_filters .= 'form-control text-right'; }
$html_filters .= '" value="'.$form_dpr.'" />
											</div>
											<div class="form-group">
												<label for="form_filters_fpr">File premissions</label>
												<input type="text" name="form_filters_fpr" id="form_filters_fpr" class="form_filters ';
if($inputError2 == true) { $html_filters .= 'form-control text-right inputError'; } else { $html_filters .= 'form-control text-right'; }
$html_filters .= '" value="'.$form_fpr.'" />
											</div></div>
											<div class="col-xs-6 col-sm-6">
											<div class="form-group">
												<label for="form_filters_uid">User ID</label>
												<input type="text" name="form_filters_uid" id="form_filters_uid" class="form_filters ';
if($inputError3 == true) { $html_filters .= 'form-control text-right inputError'; } else { $html_filters .= 'form-control text-right'; }
$html_filters .= '" value="'.$form_uid.'" />
											</div>
											<div class="form-group">
												<label for="form_filters_gid">Group ID</label>
												<input type="text" name="form_filters_gid" id="form_filters_gid" class="form_filters ';
if($inputError4 == true) { $html_filters .= 'form-control text-right inputError'; } else { $html_filters .= 'form-control text-right'; }
$html_filters .= '" value="'.$form_gid.'" />
											</div></div>
											<div class="col-xs-12 col-sm-12">
											<div class="form-group">
											<input type="hidden" name="form_filters_" value="'.$lst.$divider.$srt.'" />
											<button type="submit" class="btn btn-primary btn-block form_filters_submit">Apply</button>
											</div></div>
										</form>
									</li>
								';

// table view of listed folder
// table header
$html_main = '<div class="table-responsive">
					<table class="table table-hover table-condensed">
						<thead>
							<tr>
								<th>
									<form action="'.$_SERVER['PHP_SELF'].'" method="post">
										<div class="btn-group" data-toggle="buttons">
											<label for="form_sort_0" class="btn btn-link btn-xs'.$sort_class[0].'">
												<input type="radio" name="form_sort" id="form_sort_0" class="form-control" value="'.$lst.$divider.$srt_0.$divider.$form_dpr.$divider.$form_fpr.$divider.$form_uid.$divider.$form_gid.'" onchange="this.form.submit()" />
													<strong>Name '.$sort_sign[$sort[0]].'</strong>
											</label>
										</div>
									</form>
								</th>
								<th class="text-right">
									<form action="'.$_SERVER['PHP_SELF'].'" method="post">
										<div class="btn-group" data-toggle="buttons">
											<label for="form_sort_1" class="btn btn-link btn-xs'.$sort_class[1].'">
												<input type="radio" name="form_sort" id="form_sort_1" class="form-control" value="'.$lst.$divider.$srt_1.$divider.$form_dpr.$divider.$form_fpr.$divider.$form_uid.$divider.$form_gid.'" onchange="this.form.submit()" />
													<strong>Extension '.$sort_sign[$sort[1]].'</strong>
											</label>
										</div>
									</form>
								</th>
								<th class="text-right">
									<form action="'.$_SERVER['PHP_SELF'].'" method="post">
										<div class="btn-group" data-toggle="buttons">
											<label for="form_sort_2" class="btn btn-link btn-xs'.$sort_class[2].'">
												<input type="radio" name="form_sort" id="form_sort_2" class="form-control" value="'.$lst.$divider.$srt_2.$divider.$form_dpr.$divider.$form_fpr.$divider.$form_uid.$divider.$form_gid.'" onchange="this.form.submit()" />
													<strong>Size '.$sort_sign[$sort[2]].'</strong>
											</label>
										</div>
									</form>
								</th>
								<th class="text-center">
									<form action="'.$_SERVER['PHP_SELF'].'" method="post">
										<div class="btn-group" data-toggle="buttons">
											<label for="form_sort_3" class="btn btn-link btn-xs'.$sort_class[3].'">
												<input type="radio" name="form_sort" id="form_sort_3" class="form-control" value="'.$lst.$divider.$srt_3.$divider.$form_dpr.$divider.$form_fpr.$divider.$form_uid.$divider.$form_gid.'" onchange="this.form.submit()" />
													<strong>Date '.$sort_sign[$sort[3]].'</strong>
											</label>
										</div>
									</form>
								</th>
								<th class="text-center">
									<div class="btn btn-link btn-xs disabled">
										<strong>Prems - User/Group</strong>
									</div>
								</th>
							</tr>
						</thead>
						<tbody>
';

// link to go back (parent folder)
if ($c_lst > 1) {
	$html_main .= '							<tr class="text-muted">
								<td colspan="2">
									<form action="'.$_SERVER['PHP_SELF'].'" method="post">
										<div class="btn-group" data-toggle="buttons">
											<label for="form_folder_back" class="btn btn-link btn-xs">
												<input type="radio" name="form_folder" id="form_folder_back" class="form-control" value="'.$parentFolder.$divider.$srt.$divider.$form_dpr.$divider.$form_fpr.$divider.$form_uid.$divider.$form_gid.'" onchange="this.form.submit()" />
													<< BACK
											</label>
										</div>
									</form>
								</td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
';
}

// list folders
$i = 0;
foreach ($folderCont as $data) {
	$html_main .= '							<tr class="text-muted';
	if ($data['alert'] == 1 && $error == false) {
		$html_main .= ' warning';
		$alert = true;
	}
	$html_main .= '">
								<td colspan="2">
									<form action="'.$_SERVER['PHP_SELF'].'" method="post">
										<div class="btn-group" data-toggle="buttons">
											<label for="form_folder_'.$i.'" class="btn btn-link btn-xs">
												<input type="radio" name="form_folder" id="form_folder_'.$i.'" class="form-control" value="'.$lst.'/'.$data['fullname'].$divider.$srt.$divider.$form_dpr.$divider.$form_fpr.$divider.$form_uid.$divider.$form_gid.'" onchange="this.form.submit()" />
													['.$data['fullname'].']
											</label>
										</div>
									</form>
								</td>
								<td></td>
								<td class="text-right small"><div class="vcenter">'.date('d/m/y H:i:s', $data['date']).'</div></td>
								<td class="text-right small"><div class="vcenter">'.$data['prems'].' - '.$data['uid'].'/'.$data['gid'].'</div></td>
							</tr>
';
	$i++;
}

// list files
foreach ($fileCont as $data) {
	$html_main .= '							<tr';
	if ($data['alert'] == 1 && $error == false) {
		$html_main .= ' class="warning text-warning"';
		$alert = true;
	}
	elseif ($data['index'] == 1) {
		$html_main .= ' class="info"';
	}
	$html_main .= '>
								<td colspan="2"><a target="_blank" class="btn btn-link btn-xs" href="'.$file.'/'.$data['fullname'].'">'.$data['fullname'].'</a></td>
								<td class="text-right small"><div class="vcenter">'.fileSizeFormat($data['size']).'</div></td>
								<td class="text-right small"><div class="vcenter">'.date('d/m/y H:i:s', $data['date']).'</div></td>
								<td class="text-right small"><div class="vcenter">'.$data['prems'].' - '.$data['uid'].'/'.$data['gid'].'</div></td>
							</tr>
';
}

// table footer
$html_main .= '						</tbody>
						<tfoot>
							<tr class="text-muted">
								<td colspan="5"><small>&nbsp;Total: '.$totalFolders.', '.$totalFiles.', '.$totalSize.'</small></td>
							</tr>
						</tfoot>
					</table>
				</div>
';

$debug['alert'] = $alert;
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="description" content="Single file - drop in - webserver's Document Root browser with responsive design." />
		<meta name="keywords" content="folder, file, webserver, docroot, browser" />
		<meta name="author" content="Szilárd Barta, bartaszili@gmail.com" />
		<meta name="copyright" content="Free software" />
		<meta name="version" content="<?php echo $version; ?>" />
		<meta name="robots" content="noindex, nofollow" />
		<style type="text/css" id="bootstrap_min_css">
</style><link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<style type="text/css" id="bootstrap-theme_min_css">
</style><link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
		<style type="text/css">
			body {
				padding-top: 70px;
			}
			footer p {
				margin: 15px 0px;
			}
			@-moz-document url-prefix() {
    			fieldset {
					display: table-cell;
				}
			}
			.sort_active {
				color: #666;
			}
			thead > tr {
				background: #ddd;
			}
			tfoot > tr {
				background: #ddd;
			}
			.vcenter {
				display: inline-block;
				vertical-align: middle;
				afloat: none;
			}
			li > .navbar-form {
				clear: both;
				padding: 0 15px;
				margin: 0;
				border: 0;
			}
			.dropdown-menu > li > a {
				color: #777;
				font-size: 12px;
				line-height: 18px;
			}
			li > form.navbar-form > div > label:focus, li > form.navbar-form > div > label:hover {
				color: #262626;
				text-decoration: none;
			}
			li > form.navbar-form:focus, li > form.navbar-form:hover {
				background-color: #e8e8e8;
				background-image: linear-gradient(to bottom,#f5f5f5 0,#e8e8e8 100%);
				background-repeat: repeat-x;
			}
			.form_filters_main {
				margin: 0px 15px 0px 0px;
				width: 300px;
			}
			.form_filters {
				max-width: 120px;
			}
			.form_filters_submit {
				margin: 10px 0px;
			}
			.inputError {
				border: 1px solid #ebccd1 !important;
				border-color: #ebccd1 !important;
				background-color: #f2dede !important;
				color: #a94442 !important;
			}
			.position_footer {
				margin: 10px 0px;
			}
			.popover-content > p {
				text-align: justify;
			}
		</style>
		<title>PHP Drop2Browse</title>
	</head>
	<body>
		<header>
			<nav class="navbar navbar-default navbar-fixed-top">
				<div class="container">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">Menu</button>
						<a class="navbar-brand" href="<?php echo $_SERVER['PHP_SELF']; ?>">PHP Drop2Browse</a>
					</div>
					<div style="height: 1px;" id="navbar" class="navbar-collapse collapse">
						<ul class="nav navbar-nav navbar-right">
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown">Navigation <span class="caret"></span></a>
								<ul class="dropdown-menu"><?php echo $html_navigation; ?></ul>
							</li>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown">Useful links <span class="caret"></span></a>
								<ul class="dropdown-menu"><?php echo $html_links; ?></ul>
							</li>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown">Filters <span class="caret"></span></a>
								<ul class="dropdown-menu"><?php echo $html_filters; ?></ul>
							</li>
						</ul>
					</div><!--/.nav-collapse -->
				</div>
			</nav>
		</header>
		<main>
			<div class="container theme-showcase">
				<?php

if ($error == true) {
	echo '				<div class="alert alert-danger">
					<span class="lead">Error</span>
					<hr />
					<ul>
						'.$error_msg.'
					</ul>
				</div>
';
}
if ($alert == true) {
	echo '				<div class="alert alert-warning">
					<span class="lead">Warning</span>
					<hr />
					<ul>
						'.$alert_msg.'
					</ul>
				</div>
';
}
				?>
				<?php if ($troubleshooting == true) { echo "<pre class=\"pre-scrollable\">\n"; print_r($debug); echo "\t\t\t\t</pre>\n\t\t\t\t"; } ?><div class="alert alert-info">
					<div class="media">
						<div class="media-body">Listed directory: <strong><small><?php echo $folder; ?></small></strong></div>
						<div class="media-right"><button type="button" class="btn btn-info btn-xs" tabindex="0" data-toggle="popover" data-trigger="focus" data-placement="left" data-html="true" data-container="body" data-title="Information" data-content="<?php echo $information; ?>">Info</button></div>
					</div>
				</div>
				<?php echo $html_main; ?>
			</div>
		</main>
		<footer>
			<div class="container theme-showcase">
				<nav class="navbar navbar-default navbar-static-bottom">
					<div class="container position_footer">
						<small><a href="https://github.com/bartaszili/PHP-Drop2Browse" target="_blank">PHP Drop2Browse</a><span tabindex="0" data-toggle="popover" data-trigger="focus" data-placement="top" data-html="true" data-container="body" data-content="<a href='mailto:bartaszili@gmail.com'>bartaszili@gmail.com</a>"> &nbsp; <small class="text-muted"><?php echo $version; ?></small> &nbsp; by&nbsp;<u>Szil&aacute;rd&nbsp;Barta</u> &nbsp; <small class="text-muted">(Free&nbsp;software, </span><a href="https://www.gnu.org/licenses/gpl.txt" target="_blank">License</a>)</small></small>
					</div>
				</nav>
			</div>
		</footer>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js" id="jquery_min_js">
</script>
		<script id="bootstrap_min_js">
</script><script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		<script>
			$(document).ready(function() {
				// BOOTSTRAP: CALL POPOVER FUNCTION
				$(function () {
					$('[data-toggle="popover"]').popover()
				});
			});
		</script>
	</body>
</html>
