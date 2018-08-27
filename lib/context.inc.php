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


/** @file
 *
 * This file contains the code for showing extended context for a single result.
 */




/* initialise variables from settings files  */
require('../lib/environment.inc.php');

/* include function library files */
require('../lib/library.inc.php');
require('../lib/html-lib.inc.php');
require('../lib/user-lib.inc.php');
require('../lib/exiterror.inc.php');
require('../lib/concordance-lib.inc.php');
require('../lib/metadata.inc.php');
require('../lib/cqp.inc.php');
require('../lib/xml.inc.php');


/*
 * NOTE ON A KNOWN ISSUE
 * =====================
 *
 * Links to context.php especially often get put into Excel spreadsheets. However, when a hyperlink is clicked in Excel,
 * the resulting HTTP call is done by a built-in Windows/IE component (Hlink.dll), not by the app
 * that is the default handler for URLs. The handling of the link is only passed off to the browser when the HTML is received.
 * (The reason it does this is because it has to get the document in order to check whether the link is to an editable Office doc.
 * Because of course, the primary use case for HTTP is to edit Office documents on remote servers. What the hell do you
 * schmucks use it for?)
 *
 * Hlink will therefore not send CQPweb's cookie back - even if the user is already logged in on the default browser and that
 * browser is open - because cookies are not shared between Hlink.dll and Chrome /Firefox/whatever.
 * CQPweb will, as a result, send an "access denied" redirect, and the access denied URL gets passed to the browser
 * even though the user is actually logged in on that app.
 *
 * A typical user agent string from a GET request of this kind will pretend to be MSIE 7. For instance:
 * "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/6.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729;
 * Media Center PC 6.0; .NET4.0C; .NET4.0E; ms-office)"
 
 * But this user agent could easily crop up if the user is *really* using MSIE v 7. So, we cannot check for it.
 *
 * (Note, this issue affects *any* link in an MS Office link other than Outlook, which, for whatever reason, is immune.
 * But as noted above it's most critical  for context.php.)
 *
 * There is, fortunately, a workaround: copy-paste the link from Excel to the browser.
 */



cqpweb_startup_environment();


/* ----------------- *
 * Permissions check *
 * ----------------- */

if (PRIVILEGE_TYPE_CORPUS_RESTRICTED >= $Corpus->access_level)
{
	/*
	 * Context view is only available to those with a NORMAL or FULL level privilege for the present corpus.
	 * A user with only RESTRICTED level privilege is only able to view the concordance (fair-use snippets).
	 */
	echo print_html_header("{$Corpus->title} -- le contexte étandue", $Config->css_path);
	echo print_sermo_header();
	?>
<div class="container">
	<div class="container-wrapper">
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Accès restreint: le contexte étandue ne peut pas être affiché
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p class="spacer">&nbsp;</p>
				<p>
					Vous avez le le niveau d'accès <b>restreint</b> à ce corpus. Le contexte étandue ne peut pas être affiché.
					This is usually for copyright or licensing reasons.
				</p>
				<p class="spacer">&nbsp;</p>
			</td>
		</tr>
	</table>
	</div> <!--container-wrapper-->
</div> <!--container-->
	<?php

	echo print_html_footer('hello');
	cqpweb_shutdown_environment();
	exit;
}


/* ------------------------------- * 
 * initialise variables from $_GET * 
 * and perform initial fiddling    * 
 * ------------------------------- */



/* this script takes all of the GET parameters from concrdance.php */
/* but only qname is absolutely critical, the rest just get passed */
$qname = safe_qname_from_get();


/* parameters unique to this script */

if (isset($_GET['batch']))
	$batch = (int)$_GET['batch'];
else
	exiterror('Critical parameter "batch" was not defined!');

/* the show/hide tags button */

if (!isset($_GET['tagshow']))
	$_GET['tagshow'] = 0;

switch ((int) $_GET['tagshow'])
{
case 1:
	$show_tags = true;
	$tagshow_other_value = "Cacher les étiquettes";
	$tagshow_button_text = 'Enlever les étiquettes';
	break;

default:
	$show_tags = false;
	$tagshow_other_value = "1";
	$tagshow_button_text = 'Afficher les étiquettes';
	break;
}


if (isset($_GET['contextSize']))
	$context_size = (int)$_GET['contextSize'];
else
	$context_size = $Corpus->initial_extended_context;

/* restrict possible values */
if ($context_size > $Corpus->max_extended_context)
	$context_size = $Corpus->max_extended_context;
if ($context_size < $Corpus->initial_extended_context)
	$context_size = $Corpus->initial_extended_context;



/* do we need to show the aligned region for each match? */
$show_align = false;
$align_info = check_alignment_permissions(list_corpus_alignments($Corpus->name));
/* again note override: we do not allow BOTH translation viz AND parallel corpus viz */
if (isset($_GET['showAlign'])&& ! $Corpus->visualise_translate_in_context)
{
	if ( isset($align_info[$_GET['showAlign']]) )
	{
		$show_align = true;
		$alignment_att_to_show = $_GET['showAlign'];
		$alignment_corpus_info = get_corpus_info($alignment_att_to_show);
		$aligned_corpus_has_primary_att 
			= array_key_exists($Corpus->primary_annotation, get_corpus_annotations($alignment_att_to_show));
	}
}



/* the alt view parameter */

$use_alt_word_att = false;

if (empty($Corpus->alt_context_word_att))
{
	$alt_word_desc = ''; /* this var not printed in this case, but will be referenced, so give empty value */
	$fullwidth_colspan = 3;
}
else
{
	if (isset($_GET['altview']))
		$use_alt_word_att = (bool)$_GET['altview'];
	/* temp var needed for PHP 5.3 */
	$att_info = get_corpus_annotation_info();
	$alt_word_desc = $att_info[$Corpus->alt_context_word_att]->description;
//	PHP 5.4 +
//	$alt_word_desc = get_corpus_annotation_info()[$Corpus->alt_context_word_att]->description;
 	$fullwidth_colspan = 3;
}
if (!empty($align_info))
	$fullwidth_colspan++;
	

/* the alt view button */

if ($use_alt_word_att)
{
	$altview_other_value = '0';
	$altview_button_text = 'Leave alternative view';
}
else
{
	$altview_other_value = '1';
	$altview_button_text = 'Switch to alternative view (' . $alt_word_desc . ')';
}


/* we can now move on to getting CQP ready for us. */

$cqp->execute("set Context $context_size words");

if ($Corpus->visualise_gloss_in_context)
	$cqp->execute("show +word +{$Corpus->visualise_gloss_annotation} ");
else
	//$cqp->execute("show +word " . (empty($Corpus->primary_annotation) ? '' : "+{$Corpus->primary_annotation} "));
	$cqp->execute("show +word " . (empty($Corpus->primary_annotation) ? '' : "+{$Corpus->primary_annotation} ").(empty($Corpus->secondary_annotation) ? '' : "+{$Corpus->secondary_annotation} ").(empty($Corpus->tertiary_annotation) ? '' : "+{$Corpus->tertiary_annotation} "));

/* what inline s-attributes to show? (xml elements) */
$xml_tags_to_show = xml_visualisation_s_atts_to_show('context');
if ( ! empty($xml_tags_to_show) )
	$cqp->execute('show +' . implode(' +', $xml_tags_to_show));

/* do we need to show an a-attribute? */
if ($show_align)
	$cqp->execute('show +' . $alignment_att_to_show);

$cqp->execute("set PrintStructures \"text_id\""); 
$cqp->execute("set LeftKWICDelim '--%%%--'");
$cqp->execute("set RightKWICDelim '--%%%--'");


/* get an array containing the lines of the query to show this time */
$kwic = $cqp->execute("cat $qname $batch $batch");
/* the only line to show is at index 0; if we asked for alignemnt, it's at index 1. */




if ($use_alt_word_att)
{
	/* get  a second kwic with the alt word  */
	
	/* first, reset the "show" by turning off everything turned on above */
	if ($Corpus->visualise_gloss_in_context)
		$cqp->execute("show -{$Corpus->visualise_gloss_annotation} ");
	if (! empty($Corpus->primary_annotation))
		$cqp->execute("show -{$Corpus->primary_annotation} ");
	if ( ! empty($xml_tags_to_show) )
		$cqp->execute('show -' . implode(' -', $xml_tags_to_show));
	if ($show_align)
		$cqp->execute('show -' . $alignment_att_to_show);
	$cqp->execute("show -word +{$Corpus->alt_context_word_att}");
	
	$alt_kwic = $cqp->execute("cat $qname $batch $batch");

	/* now, put alternative words in arrays that have the same indexes as the main kwic which we will get later on. */
	list ($alt_lc_s, $alt_node_s, $alt_rc_s) = preg_split("/--%%%--/", preg_replace("/\A\s*\d+: <text_id \w+>:/", '', $alt_kwic[0]));
	$alt_lc   = explode(' ', trim($alt_lc_s));
	$alt_rc   = explode(' ', trim($alt_rc_s));
	$alt_node = explode(' ', trim($alt_node_s));
	/* note, no XML in this case! so it's much simpler than the line-breaker regex that gets*/
}



/* process the single result -- code largely filched from print_concordance_line()
 * but has diverged from it in some details. */

/* extract the text_id and delete that first bit of the line */
preg_match("/\A\s*\d+: <text_id (\w+)>:/", $kwic[0], $m);
$text_id = $m[1];
$cqp_line = preg_replace("/\A\s*\d+: <text_id \w+>:/", '', $kwic[0]);

/* divide up the CQP line */
list($kwic_lc, $kwic_node, $kwic_rc) = preg_split("/--%%%--/", $cqp_line);	


/* create some variables for repeated use within lc / rc */


/* tags for Arabic, etc.: */
$bdo_tag1 = ($Corpus->main_script_is_r2l ? '<bdo dir="rtl">' : '');
$bdo_tag2 = ($Corpus->main_script_is_r2l ? '</bdo>' : '');


/* line break (fallback visualisation) is likewise contingent on directionality. */
$line_breaker = ( 
			$Corpus->main_script_is_r2l 
			? "</bdo>\n<br/>&nbsp;<br/>\n<bdo dir=\"rtl\">" 
			: "\n<br/>&nbsp;<br/>\n" 
		);

$line_breaker_regex = '/\A([.?!\x{0964}]|\.\.\.)\Z/u';
// TODO: would the following be a better line-breaker regex? (For each of the three times it is used.)
// testing would be needed before implementation
// '/\A(\p{P}|...)\Z/u'

/* create arrays of words from the incoming variables: split at space, but extract XML for rendering too;
 * remember to trim before using this regex, in case of unwanted spaces (there will deffo be some on the left) ... */
/* if (false) {$word_extract_regex = '|((<\S+?( \S+?)?>)*)([^ <]+)((</\S+?>)*) ?|';}*/
$word_extract_regex = CQP_INTERFACE_WORD_REGEX;
/* above regex puts tokens in $m[4]; xml-tags-before in $m[1]; xml-tags-after in $m[5] . */
// TODO, tidy up the above. No need for the var, as we have the const.

/* we're also going to need the xml visualisation data structure to render boundaries to HTML */
$xml_viz_index = index_xml_visualisation_list(get_all_xml_visualisations($Corpus->name, false, true, false));


/* 
 * OK, we can now do lc, rc and node. For each, use the regex above for splitting; then build up the string. 
 */


/*
 * Build a serach string to connect to SERMO part
 */

$sermo_search_string='';
$pb_search='';

/* left context string */
preg_match_all($word_extract_regex, trim($kwic_lc), $m, PREG_PATTERN_ORDER);
$lc = $m[4];
$xml_before_array = $m[1];
$xml_after_array  = $m[5];
$lcCount = (empty($lc[0]) ? 0 : count($lc));
$lc_string = '';
for ($i = 0; $i < $lcCount; $i++) 
{
	/* apply XML visualisations */
	$xml_before_string = apply_xml_visualisations($xml_before_array[$i], $xml_viz_index) . ' ';
	$xml_after_string  =  ' ' . apply_xml_visualisations($xml_after_array[$i], $xml_viz_index);
	
	list($word, $tag) = extract_cqp_word_and_tag($lc[$i], $Corpus->visualise_gloss_in_context, false);
	
	if ($use_alt_word_att)
		$word = escape_html($alt_lc[$i]);

	/* don't show the first word of left context if it's just punctuation; 
	 * nb not the same as the line-breaker regex! */
	if ($i == 0 && preg_match('/\A[.,;:?\-!"\x{0964}\x{0965}]\Z/u', $word))
		continue;
		
	/*
	 * add 5 words preceding node to SERMO search
	 */	
		if(($lcCount-$i) < 5)	$sermo_search_string .= $word.' ';

	$lc_string .= $xml_before_string . $word . ( $show_tags ? bdo_tags_on_tag($tag) : '' ) . $xml_after_string . ' ';

	/* break line if this word is an end of sentence punctuation */
	if ($Corpus->visualise_break_context_on_punc)
		if (preg_match($line_breaker_regex, $word) || $word == '...'  )
			$lc_string .= $line_breaker;
}

/* node string */
preg_match_all($word_extract_regex, trim($kwic_node), $m, PREG_PATTERN_ORDER);
$node = $m[4];
$xml_before_array = $m[1];
$xml_after_array  = $m[5];
$nodeCount = (empty($node[0]) ? 0 : count($node));
$node_string = '';
for ($i = 0; $i < $nodeCount; $i++) 
{
	/* apply XML visualisations */
	$xml_before_string = apply_xml_visualisations($xml_before_array[$i], $xml_viz_index) . ' ';
	$xml_after_string  =  ' ' . apply_xml_visualisations($xml_after_array[$i], $xml_viz_index);
	
	list($word, $tag, $pb) = extract_cqp_word_and_tag($node[$i], $Corpus->visualise_gloss_in_context, false);
	
	//if($i==0)$pb_search.=trim($pb);
	if('' !=$pb && '' == $pb_search)$pb_search.=trim($pb);
	
	if ($use_alt_word_att)
		$word = escape_html($alt_node[$i]);
	
		/*
		 * add all node tokens to SERMO search
		 */
		$sermo_search_string .= $word.' ';

	$node_string .= $xml_before_string . $word . ( $show_tags ? bdo_tags_on_tag($tag) : '' ) . $xml_after_string . ' ';

	/* break line if this word is an end of sentence punctuation */
	if ($Corpus->visualise_break_context_on_punc)
		if (preg_match($line_breaker_regex, $word))
			$node_string .= $line_breaker;
}

/* rc string */
preg_match_all($word_extract_regex, trim($kwic_rc), $m, PREG_PATTERN_ORDER);
$rc = $m[4];
$xml_before_array = $m[1];
$xml_after_array  = $m[5];
$rcCount = (empty($rc[0]) ? 0 : count($rc));
$rc_string = '';
for ($i = 0; $i < $rcCount; $i++) 
{
	/* apply XML visualisations */
	$xml_before_string = apply_xml_visualisations($xml_before_array[$i], $xml_viz_index) . ' ';
	$xml_after_string  =  ' ' . apply_xml_visualisations($xml_after_array[$i], $xml_viz_index);
	
	list($word, $tag) = extract_cqp_word_and_tag($rc[$i], $Corpus->visualise_gloss_in_context, false);

	if ($use_alt_word_att)
		$word = escape_html($alt_rc[$i]);
	
	/*
	 * add 5 words of right context to the SERMO search
	 */	
	
		if($i < 5)	$sermo_search_string .= $word.' ';
		
		
	$rc_string .= $xml_before_string . $word . ( $show_tags ? bdo_tags_on_tag($tag) : '' ) . $xml_after_string . ' ';

	/* break line if this word is an end of sentence punctuation (And if the punctuation workaround is enabled) */
	if ($Corpus->visualise_break_context_on_punc)
		if (preg_match($line_breaker_regex, $word))
			$rc_string .= $line_breaker;
}

/* get the aligned-parallel-data string ready */
if ($show_align)
{
	/* step specific to show-align mode... */
	$kwic[1] = preg_replace("/^-->$alignment_att_to_show:\s/", '', $kwic[1]);
	
	preg_match_all($word_extract_regex, trim($kwic[1]), $m, PREG_PATTERN_ORDER);
	$alx = $m[4];
	$xml_before_array = $m[1];
	$xml_after_array  = $m[5];
	$alxCount = (empty($alx[0]) ? 0 : count($alx));
	$alx_string = '';
	for ($i = 0; $i < $alxCount; $i++) 
	{
		/* apply XML visualisations */
		$xml_before_string = apply_xml_visualisations($xml_before_array[$i], $xml_viz_index) . ' ';
		$xml_after_string  =  ' ' . apply_xml_visualisations($xml_after_array[$i], $xml_viz_index);
		
		list($word, $tag) = extract_cqp_word_and_tag($alx[$i], $Corpus->visualise_gloss_in_context, !$aligned_corpus_has_primary_att);
	
// 		if ($use_alt_word_att)
// 			$word = escape_html($alt_rc[$i]);
// NB TODO have not checked how aligned mode might interact w/ show-parallel
		
		$alx_string .= $xml_before_string . $word . ( $show_tags ? bdo_tags_on_tag($tag) : '' ) . $xml_after_string . ' ';
	
		/* break line if this word is an end of sentence punctuation (And if the punctuation workaround is enabled) */
		if ($Corpus->visualise_break_context_on_punc)
			if (preg_match($line_breaker_regex, $word))
				$alx_string .= $line_breaker;
	}	
}

// TODO the above is just SCREAMING for some of the repetition to be factored out (as was, in fact, done for the concordance)
// context blobprocess()

$sermo_collection_url = get_sermo_collection_url($text_id, $Corpus->name)."&q=".rawurlencode(html_entity_decode($sermo_search_string))."&pb=".$pb_search;

/*
 * and we are READY to RENDER .... !
 */


echo print_html_header("{$Corpus->title} -- le contexte étandue", 
							$Config->css_path, 
							extra_code_files_for_concord_header('context', 'js'), 
							extra_code_files_for_concord_header('context', 'css') );

echo print_sermo_header();

?>
<div class="container">
<div class="container-wrapper">
<table class="concordtable" width="100%">
	<tr>
		<th colspan="<?php echo $fullwidth_colspan; ?>" class="concordtable">
			Le contexte étandue dans le texte <i><?php echo $text_id; ?></i>
		</th>
	</tr>
	<tr>
		<form action="redirect.php" method="get">
			<td width="<?php echo ($fullwidth_colspan == 4 ? '25' : '50'); ?>%" align="center" class="concordgrey">
				<select name="redirect">
					<option value="fileInfo" selected="selected">
						Info sur le texte <?php echo $text_id; ?>
					</option>
					<?php 
					if ($context_size < $Corpus->max_extended_context)
						echo '<option value="moreContext">Plus de contexte</option>';
					if ($context_size > $Corpus->initial_extended_context)
						echo '<option value="lessContext">Moins de contexte</option>';
					?>
					<option value="backFromContext">Retour au résultat de la requête principale</option>
					<option value="newQuery">Nouvelle requête</option>
				</select>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="submit" value="Valider" />
			</td>
		<input type="hidden" name="text" value="<?php echo $text_id; ?>" />
		<?php echo url_printinputs(array(
			array('text', ""), 
			array('contextSize', "$context_size"),
			array('redirect', "")
			)); ?> 
		</form>
		
		
		
		<?php 
		
		if (!empty($align_info))
			echo print_alignment_switcher('context', $qname, $align_info, ($show_align ? $alignment_att_to_show : false));
		
		?>
		
		
		
		<form action="context.php" method="get">
			<td width="<?php echo ($fullwidth_colspan == 3 ? '25' : '50'); ?>%" align="center" class="concordgrey">
				&nbsp;
				<input type="submit" value="<?php echo $tagshow_button_text; ?>" />
				&nbsp;
			</td>
		<input type="hidden" name="tagshow" value="<?php echo $tagshow_other_value; ?>" />
		<?php echo url_printinputs(array(array('tagshow', ""), array('uT', ""))); ?><input type="hidden" name="uT" value="y"/>
		</form>
		
		<?php 
		
		if (!empty($Corpus->alt_context_word_att))
		{
			?>
			<form action="context.php" method="get">
				<td width="25%" align="center" class="concordgrey">
					&nbsp;
					<input type="submit" value="<?php echo $altview_button_text; ?>" />
					&nbsp;
				</td>
			<input type="hidden" name="altview" value="<?php echo $altview_other_value; ?>" />
			<?php echo url_printinputs(array(array('altview', ""), array("uT", ""))); ?><input type="hidden" name="uT" value="y"/>
			</form>		
			<?php 
		}
		
		?>
		<td width="25%" align="center" class="concordgrey">
				&nbsp;
				<span class="linkToCollection"> 
				<a target= "_blank" href ="<?php echo $sermo_collection_url; ?>">
				      Voir dans le document original
				</a></span> 
				&nbsp;
		</td>
	</tr>
	
	<tr>
		<td colspan="<?php echo $fullwidth_colspan; ?>" class="concordgeneral">
			<p class="query-match-context" align="<?php echo ($Corpus->main_script_is_r2l ? 'right' : 'left'); ?>">
				<?php echo $bdo_tag1 , $lc_string , '<b>' , $node_string , '</b>' , $rc_string , $bdo_tag2; ?>
			</p>
		</td>
	</tr>
	
	<?php 
	if ($show_align) 
	{
		/* reset the bdo tags for the new corpus. */
		$bdo_tag1 = ($alignment_corpus_info->main_script_is_r2l ? '<bdo dir="rtl">' : '');
		$bdo_tag2 = ($alignment_corpus_info->main_script_is_r2l ? '</bdo>' : '');
		
		/* trim off the alignment-line leader (As we use our own, prettier announcement -- generated below) */
		$kwic[1] = preg_replace("/^-->$alignment_att_to_show: /", '', $kwic[1]);
		
		?>
		
		<tr>
			<td colspan="<?php echo $fullwidth_colspan; ?>" class="concordgrey">
				<p class="query-match-context">
					<b>
						Unit aligned with the location containing the node in 
						[<em><?php echo escape_html($alignment_corpus_info->title); ?></em>]:
					</b>
				</p>
				<p class="query-match-context" align="<?php echo ($alignment_corpus_info->main_script_is_r2l ? 'right' : 'left'); ?>">
					<?php echo $bdo_tag1 , $alx_string , $bdo_tag2; ?>
				</p>
			</td>
		</tr>
		
		<?php 
	}
	?>
	
</table>
</div> <!--container-wrapper-->
</div> <!--container-->
<?php



echo print_html_footer('hello');

cqpweb_shutdown_environment();



/* ------------- *
 * END OF SCRIPT *
 * ------------- */

/* Function that puts tags back into ltr order... */

function bdo_tags_on_tag($tag)
{
	global $Corpus;
	
	return '_<bdo dir="ltr">' . ($Corpus->visualise_gloss_in_context ? $tag : substr($tag, 1)) . '</bdo>';
}

function get_sermo_collection_url($text_id, $corpus_name){
	
	$result = do_mysql_query("SELECT lien from text_metadata_for_$corpus_name where text_id = '$text_id'");
	
	if (1 > mysql_num_rows($result))
		exiterror("La base de données ne semble pas contenir de métadonnées pour le texte $text_id.");
		
		$metadata = mysql_fetch_assoc($result);
		
		$url=explode("?",$metadata['lien']);
		
		$id=explode("=",$url[1]);
		
		//rawurlencode($text_id);
		$ful_url = $url[0]."?".$id[0]."=".rawurlencode($id[1]);;
		
		//return $metadata['lien'];
		return $ful_url;
		
	
}
