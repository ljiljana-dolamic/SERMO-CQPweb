<?php
/*
 * CQPweb: a user-friendly interface to the IMS Corpus Query Processor
 * Copyright (C) 2008-today Andrew Hardie and contributors
 *
 * See http://cwb.sourceforge.net/cqpweb.php
 *
 * This file is part of CQPweb.
 * 
 * CQPweb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CQPweb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */



/* initialise variables from settings files  */
require('../lib/environment.inc.php');

/* include function library files */
require('../lib/library.inc.php');
require('../lib/subcorpus.inc.php');
require('../lib/xml.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/user-lib.inc.php');




cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP);

if (PRIVILEGE_TYPE_CORPUS_FULL > $Corpus->access_level)
	exiterror("You do not have permission to use this function.");



/* ----------------------------- *
 * create and send the text file *
 * ----------------------------- */


/* 
 * first an EOL check 
 */
if (isset($_GET['downloadLinebreak']))
{
	$eol = preg_replace('/[^da]/', '', $_GET['downloadLinebreak']);
	$eol = strtr($eol, "da", "\r\n");
}
else
	$eol = get_user_linefeed($User->username);



/* 
 * the filename for the output 
 */
$filename = (isset($_GET['exportFilename']) ? preg_replace('/[^\w\-]/', '', $_GET['exportFilename']) : '' );
if (empty($filename))
	$filename = $Corpus->name . '-export.txt';
if (! preg_match('/\.txt$/', $filename))
	$filename .= '.txt';



/* 
 * what to download? 
 */
if (!isset($_GET['exportWhat']) || $_GET['exportWhat'] == '~~corpus')
{
	$use_sc = false;
	$fileflag = '';
}
else
{
	if (! preg_match('/^sc~(\d+)$/', $_GET['exportWhat'], $m))
		exiterror("Section of corpus to export has been badly specified!");

	$sc = Subcorpus::new_from_id($m[1]);
	if (false === $sc)
		exiterror("The subcorpus you specified could not be found on the system.");
	
	$use_sc = true;
	$fileflag = '-f "' .  $sc->get_dumpfile_path() . '"';
}


/* 
 * which format? 
 */
if (!isset($_GET['format']))
	$_GET['format'] = 'standard';
	
switch ($_GET['format'])
{
case 'standard':
	$flags = ($use_sc ? '-C' : '-H');
	$atts = '-P word';
	break;
	
case 'word_annot':
	$flags = ($use_sc ? '-C' : '-H');
	if (empty($Corpus->primary_annotation))
		exiterror("This corpus has no primary annotation, so word-and-annotation export is not available.");
	$atts = '-P word -P ' . $Corpus->primary_annotation;
	break;
	
case 'col':
	$flags = '-Cx';
	$atts = '-ALL';
	break;
	
default:
	exiterror("Invalid export format specified.");
}

$format = $_GET['format'];



/* 
 * AND NOW THE POINTY BIT 
 */


$cmd = "{$Config->path_to_cwb}cwb-decode $flags -r \"{$Config->dir->registry}\" $fileflag {$Corpus->cqp_name} $atts";

// 		ECHO  $cmd; exit;

$proc = popen($cmd, 'r');

// show_var($proc); exit;

/* send the HTTP header */
header("Content-Type: text/plain; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");

$collection = '';
$n = 0;

while (false !== ($line = fgets($proc)))
{
	$collection .= ( $format == 'col' ? $line : (trim($line) . ' ') );
	if ( false !== strpos($line, '</') || 0 == (++$n % 12))
	{
		if ($use_sc && $format == 'word_annot')
			$collection = str_replace("\t", '/', $collection);
		echo $collection;
		if ($format != 'col')
			echo $eol;
		$collection = '';
		$n = 0;
	}
}
echo $eol;

pclose($proc);

/*
 * All done!
 */


cqpweb_shutdown_environment();


