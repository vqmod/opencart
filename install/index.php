<?php

/**
 *
 * @package Simple vQmod OpenCart install script
 * @author Jay Gilford - http://vqmod.com/
 * @copyright Jay Gilford 2022
 * @version 0.5
 * @access public
 *
 * @information
 * This file will perform all necessary file alterations for the
 * OpenCart index.php files both in the root directory and in the
 * Administration folder. Please note that if you have changed your
 * default folder name from admin to something else, you will need
 * to edit the admin/index.php in this file to install successfully
 *
 * @license
 * Permission is hereby granted, free of charge, to any person to
 * use, copy, modify, distribute, sublicense, and/or sell copies
 * of the Software, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software
 *
 * @warning
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESSED OR IMPLIED.
 *
 */

// CHANGE THIS IF YOU EDIT YOUR ADMIN FOLDER NAME


if (!empty($_POST['admin_name'])) {
	//$admin = 'admin';
	$admin = htmlspecialchars($_POST['admin_name']);
	// Counters
	$changes = 0;
	$writes = 0;
	$files = [];

	// Load class required for installation
	require('ugrsr.class.php');

	// Get directory two above installation directory
	$opencart_path = realpath(dirname(__FILE__) . '/../../') . '/';
	
	// Verify path is correct
	if(empty($opencart_path)) die('ERROR - COULD NOT DETERMINE OPENCART PATH CORRECTLY - ' . dirname(__FILE__));

	$write_errors = array();
	if(!is_writeable($opencart_path . 'index.php')) {
		$write_errors[] = 'index.php not writeable';
	}
	if(!is_writeable($opencart_path . $admin . '/index.php')) {
		$write_errors[] = 'Administrator index.php not writeable';
	}
	if(!is_writeable($opencart_path . 'vqmod/pathReplaces.php')) {
		$write_errors[] = 'vQmod pathReplaces.php not writeable';
	}

	if(!empty($write_errors)) {
		die(implode('<br />', $write_errors));
	}

	// Create new UGRSR class
	$u = new UGRSR($opencart_path);

	// remove the # before this to enable debugging info
	#$u->debug = true;

	// Set file searching to off
	$u->file_search = false;

	// Attempt upgrade if necessary. Otherwise just continue with normal install
	$u->addFile('index.php');
	$u->addFile($admin . '/index.php');

	$u->addPattern('~\$vqmod->~', 'VQMod::');
	$u->addPattern('~\$vqmod = new VQMod\(\);~', 'VQMod::bootup();');

	$result = $u->run();

	if($result['writes'] > 0) {
		if(file_exists('../mods.cache')) {
			unlink('../mods.cache');
		}
		//die('UPGRADE COMPLETE');
	}

	$u->clearPatterns();
	$u->resetFileList();

	// Add catalog index files to files to include
	$u->addFile('index.php');

	// Pattern to add vqmod include
	$u->addPattern('~// Startup~', "// vQmod\nrequire_once('./vqmod/vqmod.php');\nVQMod::bootup();\n\n// VQMODDED Startup");

	$result = $u->run();
	$writes += $result['writes'];
	$changes += $result['changes'];
	$files = array_merge($files, $result['files']);

	$u->clearPatterns();
	$u->resetFileList();

	// Add Admin index file
	$u->addFile($admin . '/index.php');

	// Pattern to add vqmod include
	$u->addPattern('~// Startup~', "//vQmod\nrequire_once('../vqmod/vqmod.php');\nVQMod::bootup();\n\n// VQMODDED Startup");

	$result = $u->run();
	$writes += $result['writes'];
	$changes += $result['changes'];
	$files = array_merge($files, $result['files']);

	$u->addFile('index.php');

	// Pattern to run required files through vqmod
	$u->addPattern('/require_once\(DIR_SYSTEM \. \'([^\']+)\'\);/', "require_once(VQMod::modCheck(DIR_SYSTEM . '$1'));");

	// Get number of changes during run
	$result = $u->run();
	$writes += $result['writes'];
	$changes += $result['changes'];
	$files = array_merge($files, $result['files']);
	
	$u->clearPatterns();
	$u->resetFileList();
	
	// 2022 - Qphoria
	// pathReplaces install
	
	// Add vqmod/pathReplaces.php file
	$u->addFile('vqmod/pathReplaces.php');

	// Pattern to add vqmod include
	$u->addPattern('~// START REPLACES //~', "// VQMODDED START REPLACES //\nif (defined('DIR_CATALOG')) { \$replaces[] = array('~^admin\b~', basename(DIR_APPLICATION)); }");

	$result = $u->run();
	$writes += $result['writes'];
	$changes += $result['changes'];
	$files = array_merge($files, $result['files']);
	$files = array_unique($files);

	if ($writes) {
		echo "The following files have been updated:<br/>";
		foreach($files as $file) {
			echo $file, '<br/>';
		}
	} else {
		die('VQMOD ALREADY INSTALLED!');
	}

	// output result to user
	die('VQMOD HAS BEEN INSTALLED ON YOUR SYSTEM!');
} else {
	echo 'vQmod Installer for OpenCart<br/>';
	echo '<form method="post">
		<span>Admin Folder Name:<input type="text" name="admin_name" value="admin" /></span>
		<input type="submit" value="Go" />
	</form>';
}