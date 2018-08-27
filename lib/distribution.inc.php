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
 * This file contains the code for calculating and showing distribution of hits across text cats.
 */


/* ------------ *
 * BEGIN SCRIPT *
 * ------------ */



/* initialise variables from settings files  */
require('../lib/environment.inc.php');


/* include function library files */
require("../lib/library.inc.php");
require("../lib/html-lib.inc.php");
require("../lib/concordance-lib.inc.php");
require("../lib/concordance-post.inc.php");
require("../lib/exiterror.inc.php");
require("../lib/user-lib.inc.php");
require("../lib/metadata.inc.php");
require("../lib/subcorpus.inc.php");
require("../lib/cache.inc.php");
require("../lib/xml.inc.php");
require("../lib/db.inc.php");
require("../lib/cqp.inc.php");
require("../lib/rface.inc.php");

cqpweb_startup_environment(CQPWEB_STARTUP_DONT_CONNECT_CQP);


/* ------------------------------- *
 * initialise variables from $_GET *
 * and perform initial fiddling    *
 * ------------------------------- */



/* this script takes all of the GET parameters from concordance.php,
 * but only qname is absolutely critical, the rest just get passed */

$qname = safe_qname_from_get();

/* all scripts that pass on $_GET['theData'] have to do this, to stop arg passing adding slashes */
if (isset($_GET['theData']))
// 	$_GET['theData'] = prepare_query_string($_GET['theData']);
	unset($_GET['theData']);
// TODO. The above is a BNCweb holdover. Commented out experimentally. Check for use of theData in files other than "concordance.inc.php"






/* parameters unique to this script */


/* workout the type of distribution we want .... text-based or xml-based? Get the attribute name. */ 
if(isset($_GET['distXml']))
	$distribution_att = cqpweb_handle_enforce($_GET['distXml']);
else
	$distribution_att = '--text';
/* nb.  "--text" is the standard code used for "this is special, use the text metadata table used throughout the system" */


/* specific classifuication? Or just show 'em all? */

if (isset($_GET['classification']))
	$class_scheme_to_show = $_GET['classification'];
else
	$class_scheme_to_show = '~~all';
	
if (isset($_GET['crosstabsClass']))
	$class_scheme_for_crosstabs = $_GET['crosstabsClass'];
else
	$class_scheme_for_crosstabs = '~~none';


/* crosstabs only allowed if general information not selected */

// TODO change ~~filefreqs to ~~idfreqs

if ($class_scheme_to_show == '~~all' || $class_scheme_to_show == '~~filefreqs') 
	$class_scheme_for_crosstabs = '~~none';
/* nb crosstabs also overriden if "file frequency" is selected */


if (isset($_GET['showDistAs']) && $_GET['showDistAs'] == 'graph' && $class_scheme_to_show != '~~filefreqs')
{
	$print_function = 'print_distribution_graph';
	$class_scheme_for_crosstabs = '~~none';
}
else
	$print_function = 'print_distribution_table';

/* as you can see above, if graph is selected, then crosstabs is overridden */

/* do we want a nice HTML table or a downloadable table? */
$download_mode = ( isset($_GET['tableDownloadMode']) && 1 == $_GET['tableDownloadMode'] );





/* work out bits of SQL (e.g. the join-table) for the distribution analysis. 
 * By default, this is the corpus's text-metadata table. But it can also be an XML idlink table. */

if ('--text' == $distribution_att)
{
	$join_table = "text_metadata_for_{$Corpus->name}";
	$join_field = "text_id";
	$join_ntoks = "words";

	$db_idfield = "text_id";
	
	
	// TODO the alias "files" should become n_regions -- use this everywhere. Print "texts" or the XML description + "regions".
	// and "fiels" shopuld be scrubbed completely from this file.  
}
else 
{
	/* check is real xml element. */
	if (false === ($xml_info = get_xml_info($Corpus->name, $distribution_att)))
		exiterror("Vous avez essayé de faire une analyse de distribution sur un type de région XML inexistant.");
		
	/* check it has idlink datatype. */
	if (METADATA_TYPE_IDLINK != $xml_info->datatype)
	{
		// nonurgent TODO what should happen if a distribution across something that doesn'#t have an idlink is requested?
		// worry about this later. Should rpob be a distribution across actual values (from the att).
		// for now, just error-out.
		
		exiterror("Il n'y a pas de métadonnées liées à cette région XML.");
	}
	

	// TODO
		
		// get the needed xml/idlink metadata
		
		// work out the columns etc.
	
	/* set the join_* variables appropriately. */
	$join_table = get_idlink_table_name($Corpus->name, $distribution_att);
	$join_field = "__ID";
	$join_ntoks = "n_tokens";

	$db_idfield = $distribution_att; 
		// TODO. check this. I think this is how it will work. e.g. u_who as well as text_id in the dist table.
}






/* does a db for the distribution exist? */

/* search the db list for a db whose parameters match those of the query named as qname;
 * if it doesn't exist, create one */

$query_record = QueryRecord::new_from_qname($qname);
if (false === $query_record)
	exiterror_general("La requête spécifiée $qname n'a pas été trouvée dans le cache!");

$db_record = check_dblist_parameters(new DbType(DB_TYPE_DIST), $query_record->cqp_query, $query_record->query_scope, $query_record->postprocess);


if (false === $db_record)
{
	$dbname = create_db(new DbType(DB_TYPE_DIST), $qname, $query_record->cqp_query, $query_record->query_scope, $query_record->postprocess);
	$db_record = check_dblist_dbname($dbname);
}
else
{
	$dbname = $db_record['dbname'];
	touch_db($dbname);
}
/* this dbname & its db_record can be globalled by the various print-me functions */





/* 
 * Set up global-able variables for thinned-query-extrapolation, and populate from the query record.
 * 
 * If the query has been thinned at any point, add extrapolation columns to the output.
 */
$extrapolate_from_thinned = $query_record->postprocess_includes_thin();
$extrapolation_factor     = $query_record->get_thin_extrapolation_factor();
// $extra_category_filters   = $query_record->get_extra_category_filters();
$display_colspan          = ' colspan="5" ';
if ($extrapolate_from_thinned) 
{
	$extrapolation_render = number_format(1.0/$extrapolation_factor, 4);
	$display_colspan = ' colspan="7" ';
}





if ($download_mode)
{
	/* ----------------------------------------------------------------- *
	 * Here is how we do the plaintext download of all file frequencies. *
	 * ----------------------------------------------------------------- */
	
	$sql = "SELECT db.$db_idfield as text, md.$join_ntoks as n_tokens, count(*) as hits 
		FROM $dbname as db 
		LEFT JOIN $join_table as md ON db.$db_idfield = md.$join_field
		GROUP BY db.$db_idfield
		ORDER BY db.$db_idfield";
	$result = do_mysql_query($sql);
	/* TODO (non-urgent) this seems to be quite a slow query to run.... check optimisation possible? */	

	$eol = get_user_linefeed($User->username);
	
	$description = distribution_header_line($query_record, $db_record, false);
	
	$extradesc = 
				( $extrapolate_from_thinned
				? "{$eol}WARNING: your query has been thinned (factor $extrapolation_render)."
					. "{$eol}Therefore, these frequencies will underestimate the real figures. The lower the factor, the worse the underestimation."
				: ''
				);
	
	header("Content-Type: text/plain; charset=utf-8");
	header("Content-disposition: attachment; filename=text_frequency_data.txt");
	echo $description, $extradesc, $eol;
	echo "__________________$eol$eol";

	echo "Texte\t\tNo. de mots dans le texte\tNo. d'occurrences dans le texte\tFréq. par million de mots$eol$eol";

	while (false !== ($r = mysql_fetch_object($result)))
		echo $r->text, "\t", $r->n_tokens, "\t", $r->hits, "\t", round(($r->hits / $r->n_tokens) * 1000000, 2), $eol;
	
	/* end of code for plaintext download. */
}
else
{
	/* begin HTML output */
	echo print_html_header($Corpus->title . " -- distribution de solutions de requête", 
	                       $Config->css_path, 
	                       array('cword', 'distTableSort'));

	echo print_sermo_header ();
	/* -------------------------------- *
	 * print upper table - control form * 
	 * -------------------------------- */
	
	/* get a list of handles and descriptions for classificatory metadata fields in this corpus */
	$class_scheme_list = metadata_list_classifications();
	
	
	?>
	<div class="container">
<div class="container-wrapper">	
<table class="concordtable" width="100%">
	<tr>
		<th colspan="4" class="concordtable">
				<?php echo distribution_header_line($query_record, $db_record, true); ?>
			</th>
	</tr>
	
	<?php 
	echo print_distribution_extrapolation_header(
			$class_scheme_to_show == '~~filefreqs' ? false :  ($print_function == 'print_distribution_table' ? 'column' : 'tip') 
			); 
	?> 
	
	<form action="redirect.php" method="get">
		<tr>
			<td class="concordgrey">Catégories:</td>
			<td class="concordgrey">
				<select name="classification">
					<?php
					
					$selected_done = false;
					$class_desc_to_pass = "";
					
					foreach($class_scheme_list as $c)
					{
						echo "\n\t\t\t\t\t<option value=\"" . ($c['handle']) . '"';
						if ($c['handle'] == $class_scheme_to_show)
						{
							$class_desc_to_pass = $c['description'];
							echo ' selected="selected"';
							$selected_done = true;
						}
						echo '>' . ($c['description']) . '</option>';
					}
					
					if ($selected_done)
						$ff_selected = $all_selected = '';
					else
					{
						$ff_selected  = ($class_scheme_to_show == '~~filefreqs' ? ' selected="selected"' : '');
						$all_selected = ($class_scheme_to_show == '~~all'       ? ' selected="selected"' : '');
					}
					echo "\n\t\t\t\t\t<option value=\"~~all\"$all_selected>Tout</option>";
					echo "\n\t\t\t\t\t<option value=\"~~filefreqs\"$ff_selected>Fréquence par sermon</option>\n";

					?>
				</select>
			</td>
			<td class="concordgrey">Afficher:</td>
			<td class="concordgrey">
				<select name="showDistAs">
					<option value="table"
						<?php 
							echo ($print_function != 'print_distribution_graph' ? ' selected="selected"' : '');
						?>>Tableau de distribution</option>
					<option value="graph"
						<?php
							echo ($print_function == 'print_distribution_graph' ? ' selected="selected"' : '');
						?>>Diagramme</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="concordgrey">Catégorie pour tableaux croisés:</td>
			<td class="concordgrey">
				<select name="crosstabsClass">
						<?php
						
						$selected_done = false;
						$class_desc_to_pass_for_crosstabs = "";
						
						foreach($class_scheme_list as $c)
						{
							echo '
								<option value="' . ($c['handle']) . '"';
							if ($c['handle'] == $class_scheme_for_crosstabs)
							{
								$class_desc_to_pass_for_crosstabs = $c['description'];
								echo ' selected="selected"';
								$selected_done = true;
							}
							echo '>' . ($c['description']) . '</option>';
						}
						if ($selected_done)
							echo '
								<option value="~~none">Pas de tableaux croisés</option>';
						else
							echo '
								<option value="~~none" selected="selected">Pas de tableaux croisés</option>';
						?>
						
				</select>
			</td>
			<td class="concordgrey">
				<!-- This cell kept empty to add more controls later --> 
				&nbsp;
			</td>
			<td class="concordgrey">
				<select name="redirect">
					<option value="refreshDistribution" selected="selected">Afficher la distribution</option>
					<option value="distributionDownload">Télécharger les fréquences par texte</option>
					<option value="newQuery">Nouvelle requête</option>
					<option value="backFromDistribution">Retour au résultat de la requête</option>
				</select> 
				<input type="submit" value="Valider" /></td>
				<input type="hidden" name="qname" value="<?php echo $qname; ?>" />
				
				<?php
				
				/* iff we have a per-page / page no passed in, pass it back, so we can return to
				 * the right place using the back-from-distribution option */
				
				if (isset($_GET['pageNo']))
				{
					$_GET['pageNo'] = (int)$_GET['pageNo'];
					echo "<input type=\"hidden\" name=\"pageNo\" value=\"{$_GET['pageNo']}\" />";
				}
				if (isset($_GET['pp']))
				{
					$_GET['pp'] = (int)$_GET['pp'];
					echo "<input type=\"hidden\" name=\"pp\" value=\"{$_GET['pp']}\" />";
				}
				
				?>	
				<input type="hidden" name="uT" value="y" />
			</td>
		</tr>
	</form>
	
	<?php 
	if (count($class_scheme_list) == 0  && $class_scheme_to_show != '~~filefreqs')
	{
		?>
		<tr>
			<th class="concordtable" colspan="4">
				Ce corpus n'a pas de métadonnées de classification de texte, de sorte que la distribution ne peut pas être affichée.
				Vous pouvez toujours sélectionner l'information & ldquo; <em> Fréquence par sermon </em> & rdquo; commande du menu ci-dessus.
			</th>
		</tr>
		<?php
	}
	?>
	
</table>

	<?php
	
	echo '<table class="concordtable" width="100%">';
	
	
	if ($class_scheme_for_crosstabs == '~~none')
	{
		switch ($class_scheme_to_show)
		{
		case '~~all':
			/* show all schemes, one after another */
			foreach ($class_scheme_list as $c)
				$print_function($c['handle'], $c['description'], $qname);
			break;
		
		case '~~filefreqs':
			print_distribution_filefreqs($qname);
			break;
			
		default:
			/* print lower table - one classification has been specified */
			$print_function($class_scheme_to_show, $class_desc_to_pass, $qname);
		}
	
	}
	else
	{
		/* do crosstabs */
		print_distribution_crosstabs($class_scheme_to_show, $class_desc_to_pass, $class_scheme_for_crosstabs, $class_desc_to_pass_for_crosstabs, $qname);
	}
	
	
	
	echo '</table>';
	echo '</div>';
	echo '</div>';
	
	echo print_html_footer('dist');

} /* end of "else" for "if download_mode" */


cqpweb_shutdown_environment();

exit(0);


/* ------------- *
 * END OF SCRIPT *
 * ------------- */








function file_freq_comp_asc($a, $b)
{
    if ($a['per_mill'] == $b['per_mill'])
        return 0;

    return ($a['per_mill'] < $b['per_mill']) ? -1 : 1;
}

function file_freq_comp_desc($a, $b)
{
    if ($a['per_mill'] == $b['per_mill'])
        return 0;

    return ($a['per_mill'] < $b['per_mill']) ? 1 : -1;
}



function print_distribution_filefreqs($qname_for_link)
{
	global $Corpus;
	global $Config;
	
	global $dbname;
	global $db_record;
	
	global $join_table;
	global $join_field;
	global $join_ntoks;
	
	global $db_idfield;


	
	$sql = "SELECT db.$db_idfield as $db_idfield, md.$join_ntoks as n_tokens, count(*) as hits 
		FROM `$dbname` as db 
		LEFT JOIN $join_table as md 
		ON db.$db_idfield = md.$join_field
		GROUP BY db.$db_idfield"
		;

	$result = do_mysql_query($sql);
	
	$master_array = array();
	$i = 0;
	while ( false !== ($t = mysql_fetch_assoc($result)))
	{
		$master_array[$i] = $t;
		$master_array[$i]['per_mill'] = round(($t['hits'] / $t['n_tokens']) * 1000000, 2);
		$i++;
	}
	// TODO would it be quicker to do the above in MySQL and ONLY get top 15 / bottom 15?
	// because on a slow machine, this code seems to have caused a 15-sec-or-so runtime, 
	// so it may be inefficient to do it in PHP.

	?>
	
	<tr>
		<th colspan="4" class="concordtable">Votre requête était <i> le plus </i> fréquemment trouvée dans les fichiers suivants:</th>
	</tr>
	<tr>
		<th class="concordtable">Texte</th>
		<th class="concordtable">Nombre de mots</th>
		<th class="concordtable">Nombre de résultats</th>
		<th class="concordtable">Fréquence <br/> par million de mots
		</th>
	</tr>
	
	<?php

	usort($master_array, "file_freq_comp_desc");

	
	for ( $i = 0 ; $i < $Config->dist_num_files_to_list && isset($master_array[$i]) ; $i++ )
	{
		$textlink = "concordance.php?qname=$qname_for_link&newPostP=text&newPostP_textTargetId={$master_array[$i][$db_idfield]}&uT=y";
		?>
		<tr>
			<td align="center" class="concordgeneral"><a
				href="textmeta.php?text=<?php echo $master_array[$i][$db_idfield]; ?>&uT=y">
							<?php echo $master_array[$i][$db_idfield]; ?> 
						</a></td>
			<td align="center" class="concordgeneral">
						<?php echo number_format((float)$master_array[$i]['n_tokens']); ?> 
					</td>
			<!-- note - link to restricted query (to just that text) needed here -->
			<td align="center" class="concordgeneral"><a
				href="<?php echo $textlink; ?>">
							<?php echo number_format((float)$master_array[$i]['hits']); ?> 
						</a></td>
			<td align="center" class="concordgeneral">
						<?php echo $master_array[$i]['per_mill']; ?> 
					</td>
		</tr>
		<?php
	}	


	?>
	<tr>
		<th colspan="4" class="concordtable">
			Votre requête était <i> le moins </ i> fréquemment trouvée dans les fichiers suivants
(seuls les fichiers avec au moins 1 résultat sont inclus):
		</th>
	</tr>
	<tr>
		<th class="concordtable">Texte</th>
		<th class="concordtable">Nombre de mots</th>
		<th class="concordtable">Nombre de résultats</th>
		<th class="concordtable">Fréquence <br/> par million de mots</th>
	</tr>
	<?php

	usort($master_array, "file_freq_comp_asc");

	
	for ( $i = 0 ; $i < $Config->dist_num_files_to_list && isset($master_array[$i]) ; $i++ )
	{
		// TODO better variable name!
		$textlink = "concordance.php?qname=$qname_for_link&newPostP=text&newPostP_textTargetId={$master_array[$i][$db_idfield]}&uT=y";
		
		// TODO the textmeta link below needs to be conditionalised.
		?>
		
		<tr>
			<td align="center" class="concordgeneral">
				<a href="textmeta.php?text=<?php echo $master_array[$i][$db_idfield]; ?>&uT=y"><?php echo $master_array[$i][$db_idfield]; ?></a>
			</td>
			<td align="center" class="concordgeneral">
				<?php echo number_format((float)$master_array[$i]['n_tokens']); ?>
			</td>
			<!-- note - link to restricted query (to just that text) needed here -->
			<td align="center" class="concordgeneral">
				<a href="<?php echo $textlink; ?>"><?php echo number_format((float)$master_array[$i]['hits']); ?></a>
			</td>
			<td align="center" class="concordgeneral">
				<?php echo $master_array[$i]['per_mill']; ?>
			</td>
		</tr>
		
		<?php
	}
}






function print_distribution_graph($classification_handle, $classification_desc, $qname_for_link)
{
	global $Corpus;
	global $Config;
	
	global $dbname;
	global $db_record;
	
	global $join_table;
	global $join_field;
	global $join_ntoks;
	
	global $db_idfield;
	
	global $query_record;
	
	global $extrapolate_from_thinned;
	global $extrapolation_factor;
	
	/* just in case! this var is always used within HTML display in this func. */
	$classification_desc = escape_html($classification_desc);

	/* a list of category descriptions, for later accessing */
	$desclist = metadata_category_listdescs($classification_handle); // TODO, make this "if...."

	/* the main query that gets table data */
	$sql = "SELECT md.$classification_handle as handle,
		count(db.$db_idfield) as hits
		FROM $join_table  as md 
		LEFT JOIN $dbname as db 
		ON md.$join_field = db.$db_idfield
		GROUP BY md.$classification_handle";

	
	$result = do_mysql_query($sql);


	/* compile the info */
	
	$max_per_mill = 0;
	$master_array = array();
	
	/* for each category: */
	for ($i = 0 ; false !== ($c = mysql_fetch_assoc($result)) ; $i++)
	{
		/* skip the category of "null" ie no category in this classification */
		if ($c['handle'] == '')
		{
			$i--;
			continue;
		}
		$master_array[$i] = $c;
		
		/*
		TEMP: we can go back to this code later, once we are confident we aren't going to be getting "false" from the method.
		list ($words_in_cat, $files_in_cat)
			= $query_record->get_search_scope_with_extra_text_restrictions(array("$classification_handle~{$c['handle']}"));
		 */
// begin temp replacement 
		$tempvar = $query_record->get_search_scope_with_extra_text_restrictions(array("$classification_handle~{$c['handle']}"));
		if (false === $tempvar)
			exiterror("Drawing this graph requires calculation of a subsection intersect that is not yet possible in this version of CQPweb.");
		else 
			list ($words_in_cat, $files_in_cat) = $tempvar;
// end temp replacement */
		if (is_null($words_in_cat))
			$words_in_cat = 0;
		if (is_null($files_in_cat))
			$files_in_cat = 0;
		
		$master_array[$i]['words_in_cat'] = $words_in_cat;
		$master_array[$i]['per_mill'] = (
			0 == $master_array[$i]['words_in_cat'] 
			? 0
			: round(($master_array[$i]['hits'] / $master_array[$i]['words_in_cat']) * 1000000, 2)
			);
		
		if ($master_array[$i]['per_mill'] > $max_per_mill)
			$max_per_mill = $master_array[$i]['per_mill'];
	}
	
	if ($max_per_mill == 0)
	{
		/* no category in this classification has any hits */
		echo "<tr><th class=\"concordtable\">No category within the classification scheme 
			\"$classification_desc\" has any hits in it.</th></tr></table>
			<table class=\"concordtable\" width=\"100%\">";
		return;
	}
	
	$n = count($master_array);
	$num_columns = $n + 1;
   
	/* header row */
	
	?>
	<col width="60%">
    <col width="40%">
	<tr class="status"><td>
	<table class="concordtable">
	<tr >
		<th colspan="4" class="concordtable">
			<?php echo " Distribution basé sur: <i>$classification_desc</i>"; ?>
		</th>
	</tr>
<!-- labels -->
	<tr>
		<td class="concordgrey"><b>Catégorie</b></td>
		<td class="concordgrey"><b>N° de résultats</b></td>
		<td class="concordgrey"><b>Taille de la catégorie</b></td>
		<td class="concordgrey"><b>Fréq. par M mots</b></td>
	</tr>
	
		<?php
		
		/* line per hit for each label */
	
		for($i = 0; $i < $n; $i++)
		{
			echo '<tr>';
			//category
			echo '<td class="concordgrey" align="center"><b>' . $master_array[$i]['handle'] . '</b></td>';
			//
			if (empty($desclist[$master_array[$i]['handle']])){
				//$this_label = $master_array[$i]['handle'];
				$r_label[$i] = "'".$master_array[$i]['handle']."'";
			}else{
				//$this_label = escape_html($desclist[$master_array[$i]['handle']]);
				$r_label[$i]= "'".escape_html($desclist[$master_array[$i]['handle']])."'";
			}

		
		   $r_data[$i]=$master_array[$i]['per_mill'];
		
		
		/*hits*/
		
		   echo "\n\t\t\t", '<td class="concordgrey" align="center">' , $master_array[$i]['hits'] , '</td>';
		/*words_in_category*/   

		echo '<td class="concordgrey" align="center">'
		, $master_array[$i]['words_in_cat'] 
		, '</td>'
		;
		
		/*per million*/
		echo '<td class="concordgrey" align="center">'
 		, $master_array[$i]['per_mill']
 		, '</td>'
		;
		}
		
		/*R make corresponding barplot*/
		$tmp_graph_path="tmp/graph/";
		
		$filename_R=$qname_for_link."_distribution_$classification_desc.png";
		
		$lables="c(".join(",",$r_label).")";
		$data = "c(".join(",",$r_data).")";
		$chart_command="par(mar=c(10,6,4,2)+.1);";
		$class_html=str_replace("é","\\u{e9}",$classification_desc);
		$chart_command.="barplot($data, names.arg=$lables, col=\"royalblue\", las=2, cex.names =0.7, col.lab= \"gray\", cex=0.5,ylim=c(0,$max_per_mill*1.1) ,main=\"Distribution bas\\u{e9} sur:\\n $class_html\",ylab=\"Fr\\u{e9}q. par M mots\")";
		//$chart_command="barplot($r_data, names.arg=$r_label, col=\"royalblue\", las=2, cex.names =0.7, col.lab= \"gray\", cex=0.5 ,main=\"Distribution basé sur:$classification_desc\")";
		$r = new RFace($Config->path_to_r);
		$r->make_chart($tmp_graph_path.$filename_R, $chart_command);
		unset($r);
		?>
	</tr>
	</table>
	</td>
	<?php
			echo '<td  class="concordgeneral" align="right"><a href="'
					,$tmp_graph_path.$filename_R
					,'" download><img src="'
 					,$tmp_graph_path.$filename_R
 					,'"  alt="Distribution basé sur: $classification_desc" /></a></td>';
 					
			?>
	

	</tr>	
</table>

<table class="concordtable" width="100%">

<?php
}









function print_distribution_table($classification_handle, $classification_desc, $qname_for_link)
{
	global $Corpus;
	
	global $dbname;
	global $db_record;
	
	global $join_table;
	global $join_field;
	
	global $db_idfield;
	
	global $query_record;
	
	global $display_colspan;
	
	global $extrapolate_from_thinned;
	global $extrapolation_factor;


	/* just in case! this var is always used within HTML display in this func. */
	$classification_desc = escape_html($classification_desc);

	/* print header row for this table */

	?>

	<tr>
		<th <?php echo $display_colspan; ?> class="concordtable">
			Basé sur: 
			<i><?php echo $classification_desc; ?></i>
		</th>
	</tr>
	<tr>
		<td class="concordgrey">
			Catégorie
			<a class="menuItem" onClick="distTableSort(this, 'cat')" onMouseOver="return escape('Trier par catégorie')">[&darr;]</a>
		</td>
		<td class="concordgrey" align="center">Mots dans la categorie</td>
		<td class="concordgrey" align="center">Résultat dans la catégorie</td>
		<td class="concordgrey" align="center">Dispersion<br />(n° de fichier avec 1+ résultats)
		</td>
		<td class="concordgrey" align="center">
			Fréquence <a class="menuItem" onClick="distTableSort(this, 'freq')" onMouseOver="return escape('Trier par fréquence par million')">[&darr;]</a>
			<br />
			par million de mots dans la catégorie
		</td>
			
		<?php
		
		if ($extrapolate_from_thinned)
		{
			?>

			<td class="concordgrey" align="center">Hits in category<br />(extrapolated)</td>
			<td class="concordgrey" align="center">
				Frequency 
				<a class="menuItem" onClick="distTableSort(this, 'extr')" 
					onMouseOver="return escape('Sort by extrapolated frequency per million')">[&darr;]</a>
				<br />
				per million words in category
				<br />(extrapolated)
			</td>

			<?php
		}
		?>
			
	</tr>

	<?php



	/* variables for keeping track of totals */
	$total_words_in_all_cats = 0;
	$total_hits_in_all_cats = 0;
	$total_hit_files_in_all_cats = 0;
	$total_files_in_all_cats = 0;

	/* a list of category descriptions, for later accessing */
	$desclist = metadata_category_listdescs($classification_handle);
	foreach ($desclist as $k=>$v)
		$desclist[$k] = escape_html($v);
	
	/* the main query that gets table data */
	$sql = "SELECT md.$classification_handle as handle,
		count(db.$db_idfield) as hits,
		count(distinct db.$db_idfield) as files
		FROM $join_table  as md 
		LEFT JOIN $dbname as db 
		ON md.$join_field = db.$db_idfield
		GROUP BY md.$classification_handle";

	$result = do_mysql_query($sql);

	/* for each category: */
	while (false !== ($c = mysql_fetch_assoc($result)))
	{
		/* skip the category of "null" ie no category in this classification */
		if ($c['handle'] == '')
			continue;
		
		$hits_in_cat = $c['hits'];
		$hit_files_in_cat = $c['files'];

		/*
		TODO: we can go back to this code later, once we are confident we aren't going to be getting "false" from the method.
		list ($words_in_cat, $files_in_cat)
			= $query_record->get_search_scope_with_extra_text_restrictions(array("$classification_handle~{$c['handle']}"));
		 */
// begin temp replacement 
		$tempvar = $query_record->get_search_scope_with_extra_text_restrictions(array("$classification_handle~{$c['handle']}"));
		// TODO for idlink we will need to use something other than "extra text restrictions".
		if (false === $tempvar)
			exiterror("Drawing this distribution table requires calculation of a subsection intersect that is not yet possible in this version of CQPweb.");
		else 
			list ($words_in_cat, $files_in_cat) = $tempvar;
// end temp replacement */
		if (is_null($words_in_cat))
			$words_in_cat = 0;
		if (is_null($files_in_cat))
			$files_in_cat = 0;
		
		$link = "concordance.php?qname=$qname_for_link&newPostP=dist&newPostP_distCateg=$classification_handle&newPostP_distClass={$c['handle']}&uT=y";

		/* print a data row */
		?>

		<tr>
			<td class="concordgeneral" id="<?php echo $c['handle'];?>">
				<?php 
				if (empty($desclist[$c['handle']]))
					echo $c['handle'], "\n";
				else
					echo $desclist[$c['handle']], "\n";
				?>
			</td>
			<td class="concordgeneral" align="center">
				<?php echo $words_in_cat;?> 
			</td>
			<td class="concordgeneral" align="center">
				<a href="<?php echo $link; ?>"><?php echo $hits_in_cat; ?></a>
			</td>
			<td class="concordgeneral" align="center">
				<?php echo "$hit_files_in_cat de $files_in_cat"; ?> 
			</td>
			<td class="concordgeneral" align="center">
				<?php echo(0 == $words_in_cat ? '0' :  round(($hits_in_cat / $words_in_cat) * 1000000, 2) ); ?> 
			</td>
				
			<?php
			
			if ($extrapolate_from_thinned)
			{
				?>
	
				<td class="concordgeneral" align="center">
					<?php echo round($hits_in_cat * $extrapolation_factor, 0); ?>	
				</td>
				<td class="concordgeneral" align="center">
					<?php echo(0 == $words_in_cat ? '0' :  round(($hits_in_cat / $words_in_cat) * 1000000 * $extrapolation_factor, 2) ); ?>  
				</td>
	
				<?php
			}
			
			?>

		</tr>

		<?php
		
		/* add to running totals */
		$total_words_in_all_cats     += $words_in_cat;
		$total_hits_in_all_cats      += $hits_in_cat;
		$total_hit_files_in_all_cats += $hit_files_in_cat;
		$total_files_in_all_cats     += $files_in_cat;
	}


	/* print total row of table */
	?>
	<tr>

		<td class="concordgrey">Total:</td>
		
		<td class="concordgrey" align="center">
			<?php echo $total_words_in_all_cats; ?> 
		</td>
		<td class="concordgrey" align="center">
			<?php echo $total_hits_in_all_cats; ?> 
		</td>
		<td class="concordgrey" align="center">
			<?php echo $total_hit_files_in_all_cats; ?> de <?php echo $total_files_in_all_cats; ?> 
		</td>
		<td class="concordgrey" align="center">
			<?php echo round(($total_hits_in_all_cats / $total_words_in_all_cats) * 1000000, 2); ?> 
		</td>
			
		<?php
		if ($extrapolate_from_thinned)
		{
			?>
			
			<td class="concordgrey" align="center">
				<?php echo round($total_hits_in_all_cats * $extrapolation_factor, 0); ?> 
			</td>
			<td class="concordgrey" align="center">
				<?php echo round(($total_hits_in_all_cats / $total_words_in_all_cats) * 1000000 * $extrapolation_factor, 2); ?> 
			</td>
			
			<?php
		}
		?>

	</tr>

	<?php
}






function print_distribution_crosstabs($class_scheme_to_show, $class_desc_to_pass, 
	$class_scheme_for_crosstabs, $class_desc_to_pass_for_crosstabs, $qname_for_link)
{
	$class_desc_to_pass = escape_html($class_desc_to_pass);
	$class_desc_to_pass_for_crosstabs = escape_html($class_desc_to_pass_for_crosstabs);

	/* get a list of categories for the category specified in $class_scheme_to_show */
	$desclist = metadata_category_listdescs($class_scheme_to_show);
	
	/* for each category */
	foreach ($desclist as $h => $d)
	{
		if (empty($d))
			$d = $h;
		else 
			$d = escape_html($d) ;
		$table_heading = "$class_desc_to_pass_for_crosstabs / où <i>$class_desc_to_pass</i> est <i>$d</i>";
	
		print_distribution_crosstabs_once($class_scheme_for_crosstabs, $table_heading, $class_scheme_to_show, $h, $qname_for_link);
	}
}




/* big waste of code having all this twice - but it does no harm, and there ARE small changes */
function print_distribution_crosstabs_once($classification_handle, $table_heading,
	$condition_classification, $condition_category, $qname_for_link)
{
	global $Corpus;
	
	global $dbname;
	global $db_record;
	
	global $join_table;
	global $join_field;
	
	global $db_idfield;
	
	global $query_record;
	
	global $display_colspan;
	
	global $extrapolate_from_thinned;
	global $extrapolation_factor;

	
	/* NB. here would the be point to escape the HTML for the table heading. But no need - it was assembled as HTML. */


	/* print header row for this table */
	?>
	<tr>
		<th <?php echo $display_colspan; ?> class="concordtable">
			<?php echo $table_heading; ?> 
		</th>
	</tr>
	<tr>
		<td class="concordgrey">Catégorie</td>
		<td class="concordgrey" align="center">Mots dans la catégorie</td>
		<td class="concordgrey" align="center">Résultats dans la catégorie</td>
		<td class="concordgrey" align="center">Dispersion<br />(n° de fichier avec 1+ résultats)</td>
		<td class="concordgrey" align="center">Fréquence<br />par million de mots dans la catégorie</td>
			
		<?php
		if ($extrapolate_from_thinned)
		{
			?>

			<td class="concordgrey" align="center">Hits in category<br />(extrapolated)</td>
			<td class="concordgrey" align="center">Frequency 
				<a class="menuItem"
				onClick="distTableSort(this, 'freq')" onMouseOver="return escape('Sort by frequency per million')"
				>[&darr;]</a>
				<br />
				per million words in category <br />(extrapolated)
			</td>

			<?php
		}
		?>
			
	</tr>
	<?php


	/* variables for keeping track of totals */
	$total_words_in_all_cats = 0;
	$total_hits_in_all_cats = 0;
	$total_hit_files_in_all_cats = 0;
	$total_files_in_all_cats = 0;

	/* a list of category descriptions, for later use/ Always pritned direct to HTML. */
	$desclist = metadata_category_listdescs($classification_handle);
	foreach ($desclist as $k=>$v)
		$desclist[$k] = escape_html($v);

	/* the main query that gets table data */
	$sql = "SELECT md.$classification_handle as handle,
		count(db.$db_idfield) as hits,
		count(distinct db.$db_idfield) as files
		FROM $join_table  as md 
		LEFT JOIN $dbname as db 
		ON md.$join_field = db.$db_idfield
		WHERE $condition_classification = '$condition_category'
		GROUP BY md.$classification_handle"
 		;

	$result = do_mysql_query($sql);

	/* for each category: */
	while (false !== ($c = mysql_fetch_assoc($result)))
	{
		/* skip the category of "null" ie no category in this classification */
		if ($c['handle'] == '')
			continue;
			
		$hits_in_cat = $c['hits'];
		$hit_files_in_cat = $c['files']	;
		/*
		TEMP: we can go back to this code later, once we are confident we aren't going to be getting "false" from the method.
		list ($words_in_cat, $files_in_cat) 
			= $query_record->get_search_scope_with_extra_text_restrictions(
				array("$classification_handle~{$c['handle']}", "$condition_classification~$condition_category"));
		 */
// begin temp replacement 
		$tempvar = $query_record->get_search_scope_with_extra_text_restrictions(
										array("$classification_handle~{$c['handle']}", "$condition_classification~$condition_category"));
		if (false === $tempvar)
			exiterror("Drawing this crosstabs table requires calculation of a subsection intersect that is not yet possible in this version of CQPweb.");
		else 
			list ($words_in_cat, $files_in_cat) = $tempvar;
// end temp replacement */
// 		list ($words_in_cat, $files_in_cat) 
// 			= $query_record->get_search_scope_with_extra_text_restrictions(
// 				array("$classification_handle~{$c['handle']}", "$condition_classification~$condition_category"));

		/* print a data row */
		?>
		
		<tr>
			<td class="concordgeneral">
				<?php 
				if (empty($desclist[$c['handle']]))
					echo $c['handle'], "\n";
				else
					echo $desclist[$c['handle']], "\n";
				?>
			</td>
			<td class="concordgeneral" align="center">
				<?php echo $words_in_cat;?> 
			</td>
			<td class="concordgeneral" align="center">
				<?php 
				/* TODO (non-high-priority) 
				make this a link to JUST the hits in the cross-tabbed pair of categories in question 
				*/ 
				echo $hits_in_cat; 
				?> 
			</td>
			<td class="concordgeneral" align="center">
				<?php echo "$hit_files_in_cat de $files_in_cat"; ?> 
			</td>
			<td class="concordgeneral" align="center">
				<?php echo ($words_in_cat > 0 ? round(($hits_in_cat / $words_in_cat) * 1000000, 2) : 0) ;?> 
			</td>
			
			<?php
			
			if ($extrapolate_from_thinned)
			{
				?>

				<td class="concordgeneral" align="center">
					<?php echo round($hits_in_cat * $extrapolation_factor, 0); ?>	
				</td>
				<td class="concordgeneral" align="center">
					<?php echo(0 == $words_in_cat ? '0' :  round(($hits_in_cat / $words_in_cat) * 1000000 * $extrapolation_factor, 2) ); ?>  
				</td>

				<?php
			}
			
			?>

		</tr>
		<?php
		
		/* add to running totals */
		$total_words_in_all_cats     += $words_in_cat;
		$total_hits_in_all_cats      += $hits_in_cat;
		$total_hit_files_in_all_cats += $hit_files_in_cat;
		$total_files_in_all_cats     += $files_in_cat;
	}


	/* print total row of table */
	?>
	
	<tr>

		<td class="concordgrey">Total:</td>
		<td class="concordgrey" align="center">
			<?php echo $total_words_in_all_cats; ?> 
		</td>
		<td class="concordgrey" align="center">
			<?php echo $total_hits_in_all_cats; ?> 
		</td>
		<td class="concordgrey" align="center">
			<?php echo $total_hit_files_in_all_cats; ?> de <?php echo $total_files_in_all_cats; ?> 
		</td>
		<td class="concordgrey" align="center">
			<?php echo ($words_in_cat > 0 ? round(($total_hits_in_all_cats / $total_words_in_all_cats) * 1000000, 2) : 0);?> 
		</td>
		
		<?php
		if ($extrapolate_from_thinned)
		{
			?>
			<td class="concordgrey" align="center">
				<?php echo round($total_hits_in_all_cats * $extrapolation_factor, 0); ?> 
			</td>
			<td class="concordgrey" align="center">
				<?php echo round(($total_hits_in_all_cats / $total_words_in_all_cats) * 1000000 * $extrapolation_factor, 2); ?> 
			</td>
			<?php
		}
		?>

	</tr>
	
	<?php
}






/**
 * Gets the header line for this interface.
 * 
 * @param QueryRecord $query_record  The query.
 * @param unknown $db_record         I do not know why this is here or what it is. 
 * @param bool html                  Whether to create HTML. dEfaults to true. 
 * @return string                    HTML string to print.
 */
function distribution_header_line($query_record, $db_record, $html = true)
{
	$solution_header = $query_record->print_solution_heading(false, $html);
	
	/* split and reunite */
	//list($temp1, $temp2) = explode(' returned', $solution_header, 2);
	//return rtrim(str_replace('Votre', 'Répartition de la distribution pour la', $temp1), ',')
	//	. ': cette requête a renvoyé '
	//	. $temp2
	
	return str_replace('Requête', 'Répartition de la distribution pour la requête', $solution_header);
}



/**
 * Creats and returns a message about extrapolation data in the distribution display. 
 *  
 * Extrapolation type can be "column" (message for when data is in col 6, 7); or "tip" (message for when data is in a hover-tip);
 * or anything else - default expected is an empty value - in which case, the assumption is that the extrapolation needs to be warned
 * about, but does not actually show up anywhere.
 * 
 * @param string $extrapolation_type  If supplied, the message says that extrapolated data IS present in the display; if not, not. 
 */
function print_distribution_extrapolation_header($extrapolation_type = NULL)
{
	global $extrapolate_from_thinned;
	global $extrapolation_factor;
	global $extrapolation_render;

	if ($extrapolate_from_thinned)
	{
		$shown = true;

		switch ($extrapolation_type)
		{
		case 'column':
			$col_or_tip = 'are given in columns 6 and 7 ';
			break;
		case 'tip':
			$col_or_tip = 'appear in the pop-up if you move your mouse over one of the bars ';
			break;
		default:
			/* Incl. any empty values. In this case, we show the message for when the extrapolated figures aren't actually visible */
			$shown = false;	
			break;
		}

		$msg = ( 
			$shown
				? "

					Extrapolated hit counts for the whole result set, and the corresponding frequencies per million words,
					$col_or_tip
					(thin-factor: $extrapolation_render). 
					The smaller the thin-factor, the less reliable extrapolated figures will be.

				"
 				: "

					(The thin-factor is $extrapolation_render.)
					Therefore, these frequencies will underestimate the real figures. 
					The lower the factor, the worse the underestimation.

				"
				);
		return <<<ENDHTML
			
			<tr>
				<td colspan="4" class="concordgeneral">
				
					Your query result has been thinned.
					 
					$msg
					
				</td>
			</tr>
			
ENDHTML;
	}
	else
		return '';
}


