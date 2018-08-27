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

/**
 * @file 
 * 
 * This file contains the script for actions affecting closure curves: currenlty create, delete, download, 
 * but eventually this will involve generatign graphs direclty in R for display!
 * 
 * Most wokr is actually done by the clcourve.inc.php librayr funcs; this file just interprets the fomr & calls it.
 */
 

require('../lib/environment.inc.php');


/* include all function files */
require('../lib/user-lib.inc.php');
require('../lib/admin-lib.inc.php');
require('../lib/cqp.inc.php');
require('../lib/exiterror.inc.php');
// require('../lib/freqtable.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/library.inc.php');
require('../lib/metadata.inc.php');
require('../lib/clocurve.inc.php');
// require('../lib/templates.inc.php');
// require('../lib/xml.inc.php');


cqpweb_startup_environment( CQPWEB_STARTUP_DONT_CONNECT_CQP , RUN_LOCATION_CORPUS );




/* set a default "next" location..." */
$next_location = "index.php?thisQ=clocurve&uT=y";
/* cases are allowed to change this */

$script_action = isset($_GET['ccAction']) ? $_GET['ccAction'] : false; 


switch ($script_action)
{
	/*
	 * ================
	 * CLOCURVE ACTIONS
	 * ================
	 */


case 'generate':
	
	if (! isset($_GET['annotation'], $_GET['intervalWidth']))
		exiterror("Cannot generate closure curve data: one or more critical parameters are missing.");
	$interval_width = (int) $_GET['intervalWidth'];
	
	if ( 'word' == $_GET['annotation'] || array_key_exists($_GET['annotation'], get_corpus_annotations($Corpus->name)) )
		$annotation = $_GET['annotation'];
	else
		exiterror("Cannot generate closure curve data for the annotaiton specified, as it does not exist.");
	
	/* for this function, we DO need a CQP connection! (which we DON'T have with the usual startuo, see above. */
	connect_global_cqp();
	
	create_clocurve($Corpus->name, $annotation, $interval_width);	
	
	break;
	
	
case 'download':

	if (isset($_GET['clocurve']))
	{
		$cc = get_clocurve_info((int)$_GET['clocurve']);
		if (empty($cc))
			exiterror("Cannot download a nonexistent set of datapoints!");
		if ($Corpus->name != $cc->corpus)
			exiterror("Cannot download a closure curve from a different corpus from this interface!");
		
		download_clocurve($cc->id);
		
		/* and as this is a file download, we don't want a next location. */
		unset($next_location);
	}
	else
		exiterror('No closure curve data specified: cannot download.');	
	break;


case 'delete':

	if (isset($_GET['ccToDelete']))
	{
		$cc = get_clocurve_info((int)$_GET['ccToDelete']);
		if (empty($cc))
			exiterror("Cannot delete a nonexistent set of datapoints!");
		if ($Corpus->name != $cc->corpus)
			exiterror("Cannot delete a closure curve from a different corpuis from this interface!");
		
		delete_clocurve($cc->id);
	}
	else
		exiterror('No closure curve data specified to delete!');	
	break;


default:

	exiterror("No valid action specified for closure curve access.");
	break;


} /* end the main switch */



if (isset($next_location))
	set_next_absolute_location($next_location);

cqpweb_shutdown_environment();

exit(0);


/* ------------- *
 * END OF SCRIPT *
 * ------------- */
	