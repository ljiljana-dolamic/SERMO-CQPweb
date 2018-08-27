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
 * This file contains the user interfaces for each of the primary corpus query entry points.  
 * Most of these functions (as in all "indexforms" files) print a table for the right-hand side interface.
 * Some are support functions, providing reusable chunks of HTML.
 */


/**
 * Builds and returns an HTML string containing the search-box and associated UI elements 
 * used in the Standard and Restricted Query forms. 
 * 
 * @param string $qstring                 A search pattern that will be inserted into the query textbox Or an empty value.
 * @param string $qmode                   The query-mode to pre-set in the query control. Or an empty value.
 * @param string $qsubcorpus              String: preset subcorpus. Only works if $show_mini_restrictions is true. 
 * @param bool   $show_mini_restrictions  Set to true if you want the "simple restriction" control for Standard Query.
 */
function printquery_build_search_box($qstring, $qmode, $qsubcorpus, $show_mini_restrictions)
{
	global $Config;
	global $Corpus;
	global $User;
	
	
	/* GET VARIABLES READY: contents of query box */
	$qstring = ( ! empty($qstring) ? escape_html(prepare_query_string($qstring)) : '' );
	
	
	/* GET VARIABLES READY: the query mode. */
	$modemap = array(
			'cqp'       => 'Syntaxe CQP',
			'sq_nocase' => 'Requête simple (pas sensible à la casse)',
			'sq_case'   => 'Requête simple (sensible à la casse)',
	);
	if (! array_key_exists($qmode, $modemap) )
		$qmode = ($Corpus->uses_case_sensitivity ? 'sq_case' : 'sq_nocase');
		/* includes NULL, empty */
		
		$mode_options = '';
		foreach ($modemap as $mode => $modedesc)
			$mode_options .= "\n\t\t\t\t\t\t\t<option value=\"$mode\"" . ($qmode == $mode ? ' selected="selected"' : '') . ">$modedesc</option>";
			
			
			/* GET VARIABLES READY: hidden attribute help */
			$style_display = ('cqp' != $qmode ? "display: none" : '');
			$mode_js       = ('cqp' != $qmode ? 'onChange="if ($(\'#qmode\').val()==\'cqp\') $(\'#searchBoxAttributeInfo\').slideDown();"' : '');
			
			$p_atts = "\n";
			foreach(get_corpus_annotation_info() as $p)
			{
				$p->tagset = escape_html($p->tagset);
				$p->description = escape_html($p->description);
				$tagset = (empty($p->tagset) ? '' : "(using {$p->tagset})");
				$p_atts .= "\t\t\t<tr>\t<td><code>{$p->handle}</code></td>\t<td>{$p->description}$tagset</td>\t</tr>\n";
			}
			
			$s_atts = "\n";
			foreach(list_xml_all($Corpus->name) as $s=>$s_desc)
				$s_atts .= "\t\t\t\t\t<tr>\t<td><code>&lt;{$s}&gt;</code></td>\t<td>" . escape_html($s_desc) . "</td>\t</tr>\n";
				if ($s_atts == "\n")
					$s_atts = "\n<tr>\t<td colspan='2'><code>None.</code></td>\t</tr>\n";
					
					/* and, while we do the a-atts, simultaneously,  GET VARIABLES READY: aligned corpus display */
					$a_atts = "\n";
					$align_options = '';
					foreach(check_alignment_permissions(list_corpus_alignments($Corpus->name)) as $a=>$a_desc)
					{
						$a_atts .= "\t\t\t\t\t<tr>\t<td><code>&lt;{$a}&gt;</code></td>\t<td>" . escape_html($a_desc) . "</td>\t</tr>\n";
						$align_options .= "\n\t\t\t\t\t\t\t<option value=\"$a\">Show text from parallel corpus &ldquo;" . escape_html($a_desc) . "&rdquo;</option>";
					}
					if ($a_atts == "\n")
						$a_atts = "\n<tr>\t<td colspan='2'><code>None.</code></td>\t</tr>\n";
						/* we do this for a-atts but not p/s-atts because there is always at least word and at least text/text_id */
						
						
						
						/* GET VARIABLES READY: hits per page select */
						$pp_options = '';
						foreach (array (10,50, 100, 250, 350, 500, 1000) as $val)
							$pp_options .= "\n\t\t\t\t\t\t\t<option value=\"$val\""
							. ($Config->default_per_page == $val ? ' selected="selected"' : '')
							. ">$val</option>"
							;
							
							if ($User->is_admin())
								$pp_options .=  "\n\t\t\t\t\t\t\t<option value=\"all\">show all</option>";
								
								
								
								/* ASSEMBLE ALIGNMENT DISPLAY CONTROL */
								if (empty($align_options))
									$parallel_html = '';
									else
										$parallel_html = <<<END_PARALLEL_ROW
										
				<tr>
					<td class="basicbox">Display alignment:</td>
					<td class="basicbox">
						<select name="showAlign">
							<option selected="selected">Do not show aligned text in parallel corpus</option>
							$align_options
						</select>
					</td>
				</tr>
				
END_PARALLEL_ROW;
									
									
									
									
									/* ASSEMBLE THE RESTRICTIONS MINI-CONTROL TOOL */
									if ( ! $show_mini_restrictions)
										$restrictions_html = '';
										else
										{
											/* create options for the Primary Classification */
											/* first option is always whole corpus */
											$restrict_options = "\n\t\t\t\t\t\t\t<option value=\"\""
													. ( empty($subcorpus) ? ' selected="selected"' : '' )
													. '>Corpus entier</option>'
															;
															
															$field = $Corpus->primary_classification_field;
															foreach (metadata_category_listdescs($field, $Corpus->name) as $h => $c)
																$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"-|$field~$h\">".(empty($c) ? $h : escape_html($c))."</option>";
																
																/* list the user's subcorpora for this corpus, including the last set of restrictions used */
																
																$result = do_mysql_query("select * from saved_subcorpora where corpus = '{$Corpus->name}' and user = '{$User->username}' order by name");
																
																while (false !== ($sc = Subcorpus::new_from_db_result($result)))
																{
																	if ($sc->name == '--last_restrictions')
																		$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"--last_restrictions\">Dernières restrictions ("
																				. $sc->print_size_tokens() . ' tokens dans '
																						. $sc->print_size_items()  . ')</option>'
																								;
																								else
																									$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"~sc~{$sc->id}\""
																									. ($qsubcorpus == $sc->id ? ' selected="selected"' : '')
																									. '>Sous-corpus: ' . $sc->name . ' ('
																											. $sc->print_size_tokens() . ' tokens dans '
																													. $sc->print_size_items()  . ')</option>'
																															;
																}
																
																/* we now have all the subcorpus/restrictions options, so assemble the HTML */
																$restrictions_html = <<<END_RESTRICT_ROW

			<table>													
				<tr>
					<td class="basicbox">Choisissez le corpus / sous-corpus:</td>
					<input type="hidden" name="del" size="-1" value="begin" />
					<td class="basicbox">
						<select name="t">
							$restrict_options
						</select>
					</td>
				</tr>				
			<table>	
<input type="hidden" name="del" size="-1" value="end" />

END_RESTRICT_ROW;
																
										} /* end of $show_mini_restrictions is true */
										
										
										
										/* ALL DONE: so assemble the HTML from the above variables && return it. */
										
										return <<<END_OF_HTML
										
		$restrictions_html
			
		
			<textarea
				name="theData"
				rows="5"
				cols="65"
				style="font-size: 16px"
				spellcheck="false"
			>$qstring</textarea>
			
			&nbsp;<br/>
			&nbsp;<br/>
			
			
			<table>
				<tr>
					<td class="basicbox">Mode de recherche:</td>
					
					<td class="basicbox">
						<select id="qmode" name="qmode" $mode_js>
							$mode_options
						</select>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<a target="_blank" href="../doc/cqpweb-simple-syntax-help.pdf"
							onmouseover="return escape('Comment composer une recherche à l\'aide de la langue de la requête simple (Anglais)')">
							Syntaxe de langage de requête simple (Anglais)
						</a>
					</td>
				</tr>
				
				<tr>
					<td class="basicbox">Nombre de résultats par page:</td>
					<td class="basicbox">
						<select name="pp">
							<option value="count">Compter les résultats</option>
							
							$pp_options
							
						</select>
					</td>
				</tr>
				
				
				
				<tr>
					<td class="basicbox">&nbsp;</td>
					<td class="basicbox">
						<input type="submit" value="Valider"/>
						<input type="reset" value="Annuler"/>
					</td>
				</tr>
			</table>
			
			<div id="searchBoxAttributeInfo" style="$style_display">
				<table>
					<tr>
						<td colspan="2"><b>Attributs P dans ce corpus:</b></td>
					</tr>
					<tr>
						<td width="40%"><code>word</code></td>
						<td><p>Attribut principal de mot-token </p></td>
					</tr>
					
					$p_atts
					
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2"><b>Attributs S dans ce corpus:</b></td>
					</tr>
					
					$s_atts
							
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
							
							
				</table>
				<p>
					<a target="_blank" href="http://cwb.sourceforge.net/files/CQP_Tutorial/"
						onmouseover="return escape('Aide détaillée sur la syntaxe CQP (Anglais)')">
						Cliquez ici pour ouvrir le tutoriel complet de CQP-syntaxe (Anglais)
					</a>
				</p>
			</div>
				
							
END_OF_HTML;
										
}


function printquery_build_search_box_A($qstring, $qmode, $qsubcorpus, $show_mini_restrictions)
{
	global $Config;
	global $Corpus;
	global $User;
	
	
	/* GET VARIABLES READY: contents of query box */
	$qstring = ( ! empty($qstring) ? escape_html(prepare_query_string($qstring)) : '' );
	
	
	/* GET VARIABLES READY: the query mode. */
	/*Forcing the query mode to cqp*/
	$qmode ='cqp';
	
	$p_atts = "\n";
	$p_atts_options = '';
	$p_atts_desc = "\n";
	foreach(get_corpus_annotation_info() as $p)
	{
		if($p->handle != 'pb'){
		$p->tagset = escape_html($p->tagset);
		$p->description = escape_html($p->description);
		$tagset = (empty($p->tagset) ? '' : "(using {$p->tagset})");
		$p_atts .= "\t\t\t<tr>\t<td><code>{$p->handle}</code></td>\t<td>{$p->description}$tagset". ($p->handle == 'etiquette' ? '<span  class="modal-help query_type" title="Jeu d\'étiquettes Presto (http://presto.ens-lyon.fr/)" data-help-name="presto_help.pdf" >&#10068;</span>' : '')."</td>\t</tr>\n";		
		$p_atts_options.= "\n<option value=\"{$p->handle}\"" . ($p->handle == 'word' ? ' selected="selected"' : '') . ">{$p->handle}</option>";
		$p_atts_desc .= "\t\t\t<tr>\t<td><code>{$p->handle}</code></td>\t<td>{$p->description}$tagset</td>\t</tr>\n";
		}
	}
	
	$s_atts = "\n";

	$s_atts_options='';
	$s_atts_select='';
	foreach(list_xml_all($Corpus->name) as $s=>$s_desc){
	//	$s_atts_select .= "\t\t\t\t\t<tr>\t<td class=\"column span-4\"><input type=\"checkbox\" name=\"s_atts[]\" value=\"{$s}\"></td>\t<td class=\"column span-9\"><code>{$s}</code></td>\t<td class=\"column span-10\">" . escape_html($s_desc) . "</td>\t</tr>\n";
		//$s_atts_select .= "\t\t\t\t\t<tr>\t<td class=\"column span-4\"><input type=\"checkbox\" name=\"s_atts[]\" value=\"{$s}\"></td>\t<td colspan=\"2\" class=\"column span-10\">" . escape_html($s_desc) . "</td>\t</tr>\n";
		
		$s_atts .= "\t\t\t\t\t<tr>\t<td><code>&lt;{$s}&gt;</code></td>\t<td>" . escape_html($s_desc) . "</td>\t</tr>\n";
		//$s_atts_options.= "\n<option value=\"{$s}\"". ($s == 'bibl' ? ' selected="selected"' : '') . ">{$s}</option>";
	//	$s_atts_options.= "\n<option value=\"{$s}\"". ($s == 'ref' ? ' selected="selected"' : '') . ">". escape_html($s_desc)."</option>";
	}	
	$s_atts_options.= "\n<option value=\"date\">Date de sermon</option>";
	$s_atts_options.= "\n<option value=\"foreign_lang\">Texte en langue étrangère</option>";
	$s_atts_options.= "\n<option value=\"head_type\">Titre</option>";
	$s_atts_options.= "\n<option value=\"hi_rend\">Texte surligné</option>";
	$s_atts_options.= "\n<option value=\"note_type\">Note</option>";
	$s_atts_options.= "\n<option value=\"p_n\">Paragraphe</option>";
	$s_atts_options.= "\n<option value=\"q_resp\">Discours directs</option>";
	$s_atts_options.= "\n<option value=\"quote_source\">Citation biblique</option>";
	$s_atts_options.= "\n<option value=\"quote_type\">Type de citation</option>";
	$s_atts_options.= "\n<option value=\"ref_target\" selected=\"selected\">Référence</option>";
	$s_atts_options.= "\n<option value=\"s_n\">Phrase</option>";
	$s_atts_options.= "\n<option value=\"seg_type\">Autres segments</option>";
		
		
		if ($s_atts == "\n")
			$s_atts = "\n<tr>\t<td colspan='2'><code>None.</code></td>\t</tr>\n";
	
	
// 	foreach(get_corpus_annotation_info() as $p)
// 	{
// 		$p->tagset = escape_html($p->tagset);
// 		$p->description = escape_html($p->description);
// 		$tagset = (empty($p->tagset) ? '' : "(using {$p->tagset})");
// 		$p_atts .= "\t\t\t<tr>\t<td><code>{$p->handle}</code></td>\t<td>{$p->description}$tagset</td>\t</tr>\n";
		
// 		$p_atts_options.= "\n<option value=\"{$p->handle}\"" . ($p->handle == 'word' ? ' selected="selected"' : '') . ">{$p->handle}</option>";
// 	}
	
	
	/* GET VARIABLES READY: hits per page select */
	$pp_options = '';
	foreach (array (10,50, 100, 250, 350, 500, 1000) as $val)
		$pp_options .= "\n\t\t\t\t\t\t\t<option value=\"$val\""
		. ($Config->default_per_page == $val ? ' selected="selected"' : '')
		. ">$val</option>"
		;
		
		if ($User->is_admin())
			$pp_options .=  "\n\t\t\t\t\t\t\t<option value=\"all\">show all</option>";
			
			/* ASSEMBLE THE RESTRICTIONS MINI-CONTROL TOOL */
			if ( ! $show_mini_restrictions)
				$restrictions_html = '';
				else
				{
					/* create options for the Primary Classification */
					/* first option is always whole corpus */
					$restrict_options = "\n\t\t\t\t\t\t\t<option value=\"\""
							. ( empty($subcorpus) ? ' selected="selected"' : '' )
							. '>Corpus entier</option>'
									;
									
									$field = $Corpus->primary_classification_field;
									foreach (metadata_category_listdescs($field, $Corpus->name) as $h => $c)
										$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"-|$field~$h\">".(empty($c) ? $h : escape_html($c))."</option>";
										
										/* list the user's subcorpora for this corpus, including the last set of restrictions used */
										
										$result = do_mysql_query("select * from saved_subcorpora where corpus = '{$Corpus->name}' and user = '{$User->username}' order by name");
										
										while (false !== ($sc = Subcorpus::new_from_db_result($result)))
										{
											if ($sc->name == '--last_restrictions')
												$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"--last_restrictions\">Dernières restrictions ("
														. $sc->print_size_tokens() . ' tokens dans '
																. $sc->print_size_items()  . ')</option>'
																		;
																		else
																			$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"~sc~{$sc->id}\""
																			. ($qsubcorpus == $sc->id ? ' selected="selected"' : '')
																			. '>Sous-corpus: ' . $sc->name . ' ('
																					. $sc->print_size_tokens() . ' tokens dans '
																							. $sc->print_size_items()  . ')</option>'
																									;
										}
				
										
										/* we now have all the subcorpus/restrictions options, so assemble the HTML */
										$restrictions_html = <<<END_RESTRICT_ROW
				
<div class="assisted_query_part status column prepend-2 span-22">
<table>						
				<tr>
					<td class="basicbox">Choisissez le corpus / sous-corpus:</td>
					<input type="hidden" name="del" size="-1" value="begin" />
					<td class="basicbox">
						<select name="t">
							$restrict_options
						</select>
					</td>
				</tr>
				</table>
				<input type="hidden" name="del" size="-1" value="end" />
</div>				
END_RESTRICT_ROW;
			
			
} /* end of $show_mini_restrictions is true */
			
			
			/* ALL DONE: so assemble the HTML from the above variables && return it. */
			
			return <<<END_OF_HTML
		
        <input id="qmode" name="qmode" type="hidden" value="cqp">
        <input id="aquery" name="aquery" type="hidden" value="y">
   		<input id="p_options" type="hidden" value='$p_atts_options'>	
   		<input id="s_options" type="hidden" value='$s_atts_options'>	
			
		$restrictions_html	
		
   					<!--s attributes selections - add part for exclude structure -->
&nbsp;<br/>
&nbsp;<br/>
<div  class="status column prepend-2 span-22 last">
     <div class="column  span-1 last">&nbsp;&nbsp;</div>
	<div class="assisted_query_part status column  span-11">
				<table width="100%">
				<tr>
             	<td><input type="radio" name="s_atts_exclude" value="include" checked>Restreindre le corpus </td>
				
                <td><input type="radio" name="s_atts_exclude" value="exclude">Exclure du corpus</td>
                </tr>     
			   </table>	
     </div>
<div class="assisted_query_part status column  span-11">
				<table width="100%">
				<tr>
             	<td><input type="radio" name="f_atts_exclude" value="include" checked>Restreindre le corpus </td>
				
                <td><input type="radio" name="f_atts_exclude" value="exclude">Exclure du corpus</td>
                </tr>     
			   </table>	
     </div>
&nbsp;<br/>
&nbsp;<br/>
<div class="column  span-1 last">&nbsp;&nbsp;</div>
     <div class="assisted_query_part status column  span-11 ">
	  <table width="100%">
			<tr>
				<th colspan="3"> Attributs structurels:
				</th>
            </tr>
             <tr>
					<td><input type="checkbox" name="s_atts[]" value="_.ref_type='bible'"></td>
					<td colspan="2">Référence biblique<td>
		    </tr>
            <tr>
					<td><input type="checkbox" name="s_atts[]" value="_.ref_type='other'"></td>
					<td colspan="2">Autre référence<td>
			</tr>
             <tr>
					<td><input type="checkbox" name="s_atts[]" value="_.head_type='main'"></td>
					<td colspan="2">Titre<td>
			</tr>
        <tr>
					<td><input type="checkbox" name="s_atts[]" value="_.head_type='sub'"></td>
					<td colspan="2">Sous-titre<td>
			</tr>	
			   <tr>
					<td><input type="checkbox" name="s_atts[]" value="_.quote_source='.*'"></td>
					<td colspan="2">Citation<td>
				</tr>
<tr>
					<td><input type="checkbox" name="s_atts[]" value="_.q_resp='.*'"></td>
					<td colspan="2">Discours directs<td>
				</tr>	
<tr>
					<td><input type="checkbox" name="s_atts[]" value="_.note_type='margin'"></td>
					<td colspan="2">Notes marginales<td>
				</tr>	
<tr>
					<td><input type="checkbox" name="s_atts[]" value="_.note_type='foot'"></td>
					<td colspan="2">Notes de bas de page <td>
				</tr>	
			   </table>	
              </div>
        <div class="assisted_query_part status column  span-11">
				<table width="100%">
				<tr>
				<th colspan="3"> Attributs formels:
				</th>
             	
                </tr>
      <tr>
					<td><input type="checkbox" name="f_atts[]" value="_.hi_rend='I'"></td>
					<td colspan="2">Texte en italique<td>
				</tr>
<tr>
					<td><input type="checkbox" name="f_atts[]" value="_.hi_rend='G'"></td>
					<td colspan="2">Texte en gras<td>
				</tr>
<tr>
					<td><input type="checkbox" name="f_atts[]" value="_.hi_rend='E'"></td>
					<td colspan="2">Texte en exposant<td>
				</tr>
<tr>
					<td><input type="checkbox" name="f_atts[]" value="_.hi_rend='S'"></td>
					<td colspan="2">Texte surligné<td>
				</tr>	
			    	<tr><td colspan="3">&nbsp;<td></tr>
					<tr><td colspan="3">&nbsp;<td></tr>
					<tr><td colspan="3">&nbsp;<td></tr>
					<tr><td colspan="3">&nbsp;<td></tr>
			   </table>	
              </div>

</div>


        <!-- main table 2*3-->
    <div id = "full_query" class="status column prepend-2 span-22">
 					
							<div id="token_type" class="assisted_query_part">
       							<ul id="queryInfoTabs">
									<li  class="modal-help query_type column span-2" title="Ajouter un token" data-help-name="token_help.pdf" >&#10068;</li>
									<li id="addMot" class="query_type status column span-6">Ajouter un token</li>
									<li id="addStruct" class="query_type status column span-6">Ajouter une structure</li>
                                    <li  class="modal-help query_type column span-2" title="Ajouter une structure" data-help-name="structure_help.pdf" >&#10068;</li>
		 						</ul>
							</div>
							
							<div id="first_line" class="column span-24 last">
                                &nbsp;
					    	</div>
			
												
												
			



&nbsp;<br/>
&nbsp;<br/>           

           <div class="column  span-4">&nbsp;&nbsp;</div>
			<div class="assisted_query_part status column  span-12">
	  <table width="100%">
			<tr>
				<th> Etendre à:
				</th>
				<td colspan="2">
					<select id="expand" name="expand">
							<option value="none" selected="selected">aucun</option>
							<option value="s" >phrase</option>
							<option value="ref" >référence</option>
							<option value="quote" >citation</option>
							<option value="head" >titre</option>
					</select>
				</td>
            </tr>
             
			  </table>	
         </div>	
<div class="column  span-4">&nbsp;&nbsp;</div>			
			<table class="concordtable">
												
				<tr>
					
					<td class="basicbox">
						<input type="submit" value="Valider"/>
						<!--input type="reset" value="Annuler"/-->
					</td>
				</tr>
			</table>
			</div>									
											
			<div class="assisted_query_part status column prepend-2 span-12">
  				<!--P attributes descriptions -->
				<table class="concordtable ">
								
				<tr>
					<th>Attributs de token dans ce corpus:</th>	
				</tr>
                 $p_atts
			</table>				
			</div>
								
END_OF_HTML;
			
}


function printquery_search()
{
	/* most of the hard work of this function is done by the inner "print search box" function
	 * and thisd function merely wraps it, yanks vars from GET, and begins/ends the form. */


	?>
	<div id="query">
       <ul id="queryInfoTabs">
        <?php
if(isset($_GET['insertString'])){
	echo '<li id="queryA" class="query_type status column span-6">Requête assistée</li>';
}else{
	echo '<li id="queryA" class="query_type status column span-6 selected">Requête assistée</li>';
}
?>

 <?php
if(isset($_GET['insertString'])){
	echo '<li id="queryCQP" class="query_type status column span-6 selected">Requête cqp/ceql</li>';
}else{
	echo '<li id="queryCQP" class="query_type status column span-6 ">Requête cqp/ceql</li>';
}
?>
        
		<!-- li id="queryA" class="query_type status column span-6 selected">Requête assistée</li>
		<li id="queryCQP" class="query_type status column span-6 ">Requête cqp/ceql</li-->
		
		 </ul>
	</div>
	
<?php
if(isset($_GET['insertString'])){
	echo '<div id="assisted_query" style="display:none">';
}else{
	echo '<div id="assisted_query" >';
}
?>
 	<!-- div id="assisted_query" -->
		
			<form action="concordance.php" accept-charset="UTF-8" method="post"> 

				<?php
				echo printquery_build_search_box_A(
					isset($_GET['insertString'])    ? $_GET['insertString']    : NULL,
					isset($_GET['insertType'])      ? $_GET['insertType']      : NULL,
					isset($_GET['insertSubcorpus']) ? $_GET['insertSubcorpus'] : NULL,
					true
				);
				?>

				<input type="hidden" name="uT" value="y" />
			</form>
		
	</div>
	<?php
if(isset($_GET['insertString'])){
	echo '<div id="standard_query" class="status">';
}else{
	echo '<div id="standard_query" class="status" style="display:none">';
}
?>
	<!--div id="standard_query" class="status" style="display:none"-->
		

			<form action="concordance.php" accept-charset="UTF-8" method="get"> 

				<?php
				echo printquery_build_search_box(
					isset($_GET['insertString'])    ? $_GET['insertString']    : NULL,
					isset($_GET['insertType'])      ? $_GET['insertType']      : NULL,
					isset($_GET['insertSubcorpus']) ? $_GET['insertSubcorpus'] : NULL,
					true
				);
				?>

				<input type="hidden" name="uT" value="y" />
			</form>
		
	</div>
	

</div>
<?php
}

function printquery_freqLookup()
{
	/* most of the hard work of this function is done by the inner "print search box" function
	 * and thisd function merely wraps it, yanks vars from GET, and begins/ends the form. */
	
	
	?>
	<!--div id="freqLookup"-->
       <!--ul id="queryInfoTabs"-->
       <!--li id="wordFl" class="freq_type status column span-6 ">Liste de fréquences</li!-->
<!-- 	   <li id="wordLu" class="freq_type status column span-6 ">Chercher un mot</li> -->
		
		 <!-- /ul-->
	<!-- /div-->
	

 <div id="wordLookup" >
		 <?php
		 	printquery_lookup();
		 ?>
	</div>
	
	<!--  div id="frequencyList" class="status" style="display:none"-->
	<!-- ?php-->
 	<!--	printquery_freqlist();-->
	<!--?-->
<!-- 	</div> -->
	

</div>
<?php
}



function printquery_restricted()
{
	/* insert restrictions as checked tickboxes lower down */
// 	$checkarray = array();
// 	if (isset($_GET['insertRestrictions']))
// 	{
// 		/* note that, counter to what one might expect, the parameter here is given as a serialisation, not URL-format */
// 		if (false === ($restriction = Restriction::new_by_unserialise($_GET['insertRestrictions'])))
// 			/* it can't be read: so don't populate $checkarray. */
// 			;
// 		else
// 			foreach ($restriction->get_form_check_pairs() as $pair)
// 				$checkarray[$pair[0]][$pair[1]] = 'checked="checked" ';
// // old method:
// // 		preg_match_all('/\W+(\w+)=\W+(\w+)\W/', $_GET['insertRestrictions'], $matches, PREG_SET_ORDER);
// // 		foreach($matches as $m)
// // 			$checkarray[$m[1]][$m[2]] = 'checked="checked" ';
// 	}
	if (isset($_GET['insertRestrictions']))
		$insert_r = Restriction::new_by_unserialise($_GET['insertRestrictions']);
	else
		$insert_r = NULL;

	?>
<table class="concordtable" width="100%">

	<tr>
		<th class="concordtable" colspan="3">Restricted Query</th>
	</tr>

	<form action="concordance.php" accept-charset="UTF-8" method="get">
		<tr>
			<td class="concordgeneral" colspan="3">

					<?php
					echo printquery_build_search_box(
						isset($_GET['insertString']) ? $_GET['insertString']  : NULL,
						isset($_GET['insertType'])   ? $_GET['insertType']    : NULL,
						NULL,
						false
					);
					?>

				</td>
		</tr>

			<?php
			echo printquery_build_restriction_block($insert_r, 'query');
			?>

			<input type="hidden" name="uT" value="y" />
	</form>
</table>

<?php
}





/**
 * This provides the metadata restrictions block that is used for queries and for subcorpora.
 * 
 * @param Restriction $insert_restriction  If not empty, contains a Restriction to be rendered in the form.
 * @param string $thing_to_produce         String to rpint labelling the thing the form will produce: "query", "subcorpus"
 * checkarray is an array of categories / classes that are to be checked;
 */
// function printquery_build_restriction_block($checkarray, $thing_to_produce)
function printquery_build_restriction_block($insert_restriction, $thing_to_produce)
{
	global $Corpus;

	$block = '
		<tr>
			<th colspan="3" class="concordtable">
				Sélectionnez les catégories de votre '. $thing_to_produce . ':
			</th>
		</tr>
		';


	/* TEXT METADATA */

	/* get a list of classifications and categories from mysql; print them here as tickboxes */

	$block .= '<tr><input type="hidden" name="del" size="-1" value="begin" />';

	$classifications = metadata_list_classifications();

	$header_row = array();
	$body_row = array();
	$i = 0;

	foreach ($classifications as $c)
	{
		$header_row[$i] = '<td width="33%" class="concordgrey" align="center">' .escape_html($c['description']) . '</td>';
		$body_row[$i] = '<td class="concordgeneral" valign="top" nowrap="nowrap">';

		$catlist = metadata_category_listdescs($c['handle']);

		foreach ($catlist as $handle => $desc)
		{
			$t_value = '-|' . $c['handle'] . '~' . $handle;
			$check = ( ( $insert_restriction && $insert_restriction->form_t_value_is_activated($t_value) ) ? 'checked="checked" ' : '');
			$body_row[$i] .= '<input type="checkbox" name="t" value="' . $t_value . '" ' . $check 
				. '/> ' . ($desc == '' ? $handle : escape_html($desc)) . '<br/>';
		}


		/* whitespace is gratuitous for readability */
		$body_row[$i] .= '
			&nbsp;
			</td>';

		$i++;
		/* print three columns at a time */
		if ( $i == 3 )
		{
			$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
				<tr>
				' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
				<tr>
				';
			$i = 0;
		}
	}

	if ($i > 0) /* not all cells printed */
	{
		while ($i < 3)
		{
			$header_row[$i] = '<td class="concordgrey" align="center">&nbsp;</td>';
			$body_row[$i] = '<td class="concordgeneral">&nbsp;</td>';
			$i++;
		}
		$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
			<tr>
			' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
			<tr>
			';
	}


	if (empty($classifications))
		$block .= '<tr><td colspan="3" class="concordgrey" align="center">
			&nbsp;<br/>
			There are no text classification schemes set up for this corpus.
			<br/>&nbsp;
			</td></tr>';


	$classification_elements_matrix = array();
	$idlink_elements_matrix = array();

	$xml = get_xml_all_info($Corpus->name);


	foreach ($xml as $x)
		if ($x->datatype == METADATA_TYPE_NONE)
			$classification_elements_matrix[$x->handle] = array();

	foreach ($xml as $x)
	{
		if ($x->datatype == METADATA_TYPE_CLASSIFICATION)
			$classification_elements_matrix[$x->att_family][] = $x->handle;
		else if ($x->datatype == METADATA_TYPE_IDLINK)
		{
			foreach (get_all_idlink_info($Corpus->name, $x->handle) as $k=> $field)
				if ($field->datatype == METADATA_TYPE_CLASSIFICATION)
					$idlink_elements_matrix[$x->handle][$k] = $field;
		}
	}

	foreach($classification_elements_matrix as $k=>$c)
		if (empty($c))
			unset($classification_elements_matrix[$k]);

	/* we now know which elements we need a display for. */

	foreach ($classification_elements_matrix as $el => $class_atts)
	{
		/* We have already done <text>-level, above. Don't allow <text> to be a sub-text element. */
		if ('text' == $el)
			continue;

		$block .= <<<END_HTML
			<tr>
				<th colspan="3" class="concordtable">
					Select sub-text restrictions for your $thing_to_produce -- for <em>{$xml[$el]->description}</em> regions:
				</th>
			</tr>
END_HTML;

		$header_row = array();
		$body_row = array();
		$i = 0;

		foreach($class_atts as $c)
		{
			$header_row[$i] = '<td width="33%" class="concordgrey" align="center">' . $xml[$c]->description . '</td>';
			$body_row[$i] = '<td class="concordgeneral" valign="top" nowrap="nowrap">';

			$catlist = xml_category_listdescs($Corpus->name, $c);

			$t_base_c = preg_replace("/^{$el}_/",  '', $c);

			foreach ($catlist as $handle => $desc)
			{
				$t_value = $el . '|'. $t_base_c . '~' . $handle;
				$check = ( ( $insert_restriction && $insert_restriction->form_t_value_is_activated($t_value) ) ? 'checked="checked" ' : '');
				$body_row[$i] .= '<input type="checkbox" name="t" value="' . $t_value . '" ' . $check 
					. '/> ' . ($desc == '' ? $handle : escape_html($desc)) . '<br/>';
			}

			/* whitespace is gratuitous for readability */
			$body_row[$i] .= '
				&nbsp;
				</td>';

			$i++;
			/* print three columns at a time */
			if ( $i == 3 )
			{
				$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
				<tr>
				' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
				<tr>
				';
				$i = 0;
			}
		}

		if ($i > 0) /* not all cells printed */
		{
			while ($i < 3)
			{
				$header_row[$i] = '<td class="concordgrey" align="center">&nbsp;</td>';
				$body_row[$i] = '<td class="concordgeneral">&nbsp;</td>';
				$i++;
			}
			$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
			<tr>
			' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
			<tr>
			';
		}
	}

	//TODO
	// a lot of stuff is now repeated 3 times, for text metadata, xml classification, and idlink classifications. Look at factoring some of it out. 



	foreach ($idlink_elements_matrix as $el => $idlink_classifications)
	{
		$block .= <<<END_HTML
			<tr>
				<th colspan="3" class="concordtable">
					Select restrictions on <em>{$xml[$el]->description}</em> 
					for your $thing_to_produce -- affects <em>{$xml[$xml[$el]->att_family]->description}</em> regions:
				</th>
			</tr>
END_HTML;

		$header_row = array();
		$body_row = array();
		$i = 0;

		foreach ($idlink_classifications as $field_h => $field_o)
		{
			$header_row[$i] = '<td width="33%" class="concordgrey" align="center">' . $field_o->description . '</td>';
			$body_row[$i] = '<td class="concordgeneral" valign="top" nowrap="nowrap">';

			$catlist = idlink_category_listdescs($Corpus->name, $field_o->att_handle, $field_h);

			$t_base = preg_replace("/^{$xml[$el]->att_family}_/",  '', $el) . '/' . $field_h;

			foreach ($catlist as $handle => $desc)
			{
				$t_value = $xml[$el]->att_family . '|'. $t_base . '~' . $handle;
				$check = ( ( $insert_restriction && $insert_restriction->form_t_value_is_activated($t_value) ) ? 'checked="checked" ' : '');
				$body_row[$i] .= '<input type="checkbox" name="t" value="' . $t_value . '" ' . $check 
					. '/> ' . ($desc == '' ? $handle : escape_html($desc)) . '<br/>';
			}

			/* whitespace is gratuitous for readability */
			$body_row[$i] .= '
				&nbsp;
				</td>';

			$i++;
			/* print three columns at a time */
			if ( $i == 3 )
			{
				$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
				<tr>
				' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
				<tr>
				';
				$i = 0;
			}
		}

		if ($i > 0) /* not all cells printed */
		{
			while ($i < 3)
			{
				$header_row[$i] = '<td class="concordgrey" align="center">&nbsp;</td>';
				$body_row[$i] = '<td class="concordgeneral">&nbsp;</td>';
				$i++;
			}
			$block .= $header_row[0] . $header_row[1] . $header_row[2] . '</tr>
			<tr>
			' . $body_row[0] . $body_row[1] . $body_row[2] . '</tr>
			<tr>
			';
		}
	}

	$block .= '</tr>
		<input type="hidden" name="del" size="-1" value="end" />
		';

	return $block;
}





function printquery_lookup()
{
	global $Config;
	global $Corpus;
	global $User;
	/* much of this is the same as the form for freq list, but simpler */
	
	/* do we want to allow an option for "showing both words and tags"? */
	$primary_annotation = get_corpus_metadata('primary_annotation');
	
	
	$annotation_available = ( empty($primary_annotation) ? false : true );
	
	$result = do_mysql_query("select * from saved_subcorpora where corpus = '{$Corpus->name}' and user = '{$User->username}' order by name");
	
	$restrict_options= "<option value=\"\">". escape_html($Corpus->title) . "</option>\n";
	while (false !== ($sc = Subcorpus::new_from_db_result($result)))
	{
		
		$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"~sc~{$sc->id}\"". '>Sous-corpus: ' . $sc->name . '</option>';
	}
	
	
	$attribute = get_corpus_annotations();
	
	$att_options = '<option value="word">Forme</option>';
	
	foreach ($attribute as $k => $a){
		if($k != 'pb'){
			$att_options .= "<option value=\"$k\">$a</option>\n";
		}
	}
	
	$show_att_options = '<option value="word">Forme</option>';
	// show annotations
	foreach ($attribute as $k => $a){
		if($k == 'etiquette'){
			$show_att_options.= "<option value=\"$k\">$a</option>\n";
		}
	}
	
	//show word+annotation
	foreach ($attribute as $k => $a){
		if($k == 'etiquette'){
			$show_att_options.= "<option value=\"word_$k\">Forme et $a</option>\n";
		}
	}
	
	?>
	<div class="status prepend-2 span-22" style="text-align:center">
 <h1>Liste de fréquences</h1>
</div> 
	
<table class="concordtable" width="100%">

	<tr>
		<td class="concordgrey" colspan="2">
			&nbsp;<br/>
			Vous pouvez utiliser cette recherche pour trouver les <em> types</em> correspondant à votre requête, leurs étiquettes et leur distribution.
			<br/>&nbsp;
		</td>
	</tr>

	<form action="redirect.php" method="get">
		<tr>
			<td class="concordgeneral">Chercher dans ...</td>
			<td class="concordgeneral">
				<select name="t">
					<?php echo $restrict_options; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">Liste basée sur ...</td>
			<td class="concordgeneral">
				<select name="wlAtt">
					<?php echo $att_options; ?>
				</select>
			</td>
		</tr>
<!-- 		<tr> -->
<!-- 			<td class="concordgeneral">Entrez le mot ou la chaine de caractères que vous souhaitez chercher</td> -->
<!-- 			<td class="concordgeneral"> -->
<!-- 				<input type="text" name="lookupString" size="32" /> -->
<!-- 				<br/> -->
<!-- 				<em>(? = 0 ou 1 caractère; * = 0 ou plusieurs caractères)</em> -->
<!-- 			</td> -->
<!-- 		</tr> -->

<!-- 		<tr> -->
<!-- 			<td class="concordgeneral">Afficher les mots ...</td> -->
<!-- 			<td class="concordgeneral"> -->
<!-- 				<table> -->
<!-- 					<tr> -->
<!-- 						<td class="basicbox"> -->
<!-- 							<p> -->
<!-- 								<input type="radio" name="lookupType" value="begin" checked="checked" /> -->
<!-- 								commençant par -->
<!-- 							</p> -->
<!-- 							<p> -->
<!-- 								<input type="radio" name="lookupType" value="end" /> -->
<!-- 								se terminant par -->
<!-- 							</p> -->
<!-- 							<p> -->
<!-- 								<input type="radio" name="lookupType" value="contain"/> -->
<!-- 								contenant -->
<!-- 							</p> -->
<!-- 							<p> -->
<!-- 								<input type="radio" name="lookupType" value="exact"  /> -->
<!-- 								correspondant exactement -->
<!-- 							</p> -->
<!-- 						</td> -->
<!--						<td class="basicbox" valign="center"> --> 
<!--  							... au modèle que vous avez spécifié --> 
<!--						</td> --> 
<!-- 					</tr> -->
<tr>
			<td class="concordgeneral">Filtrer la liste par <em>pattern</em> ...</td>
			<td class="concordgeneral">
				<select name="lookupType">
					<option value="begin" selected="selected">commence par</option>
					<option value="end">fini par</option>
					<option value="contain">contient</option>
					<option value="exact">correspond exactement</option>
				</select>
				&nbsp;&nbsp;
				<input type="text" name="lookupString" size="32"  />
			</td>
		</tr>
<!-- 					<tr> -->
<!-- 			<td class="concordgeneral">Ordre:</td> -->
<!-- 			<td class="concordgeneral"> -->
<!-- 				<select name="wlOorder"> -->
<!-- 					<option value="desc" selected="selected">fréquence décroissantes</option> -->
<!-- 					<option value="asc">fréquence croissantes</option> -->
<!-- 					<option value="alph">ordre alphabetique</option> -->
<!-- 				</select> -->
<!-- 			</td> -->
<!-- 		</tr> -->
				
				<!--
				</table>
				<select name="lookupType">
					<option value="begin" selected="selected">starting with</option>
					<option value="end">ending with</option>
					<option value="contain">containing</option>
					<option value="exact">matching exactly</option>
				</select>
				the pattern you specified
				-->
			</td>
		</tr>

		<?php
		if ($annotation_available)
		{
			echo '
			<tr>
				<td class="concordgeneral">Afficher les résultats ...</td>
				<td class="concordgeneral">
					<select name="lookupShowType">'.
					'<option value="word" selected="selected">Forme</option>'.
						'<option value="annot" >Étiquettes morpho-syntaxique</option>'
						.'<option value="both">Forme et étiquettes morpho-syntaxique</option>'
					.'</select>
				</td>
			</tr>';
		}
		?>

		<tr>
			<td class="concordgeneral">Nombre de résultats affichés par page:</td>
			<td class="concordgeneral">
				<select name="pp">
					<option>10</option>
					<option selected="selected">50</option>
					<option>100</option>
					<option>250</option>
					<option>350</option>
					<option>500</option>
					<option>1000</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral" colspan="2" align="center">
				&nbsp;<br/>
				<input type="submit" value="Valider " />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="reset" value="Annuler" />
				<br/>&nbsp;
			</td>
		</tr>
		<input type="hidden" name="redirect" value="lookup" />
		<input type="hidden" name="uT" value="y" />
	</form>

</table>
<?php


}


function printquery_keywords()
{
	global $Config;
	global $Corpus;

	/* create the options for frequency lists to compare */

	/* needed for both local and public subcorpora */
	$subc_mapper = get_subcorpus_name_mapper();

	/* subcorpora belonging to this user that have freqlists compiled (list of IDs returned) */
	$subcorpora = list_freqtabled_subcorpora();

	/* public freqlists - corpora */
	$public_corpora = list_public_whole_corpus_freqtables();

	/* public freqlists - subcorpora (function returns associative array) */
	$public_subcorpora = list_public_freqtables();


	$list_options = "<option value=\"__entire_corpus\">Whole of " . escape_html($Corpus->title) ."</option>\n";

	foreach ($subcorpora as $s)
		$list_options .= "\t\t\t\t\t<option value=\"sc~$s\">Sous-corpus: {$subc_mapper[$s]}</option>\n";

	$list_options_list2 = $list_options;
	/* only list 2 has the "public" options */

	foreach ($public_corpora as $pc)
		$list_options_list2 .= 
			( $pc['corpus'] == $Corpus->name ? '' : 
				( "\t\t\t\t\t<option value=\"pc~{$pc['corpus']}\">Public frequency list:  " 
 					. escape_html($pc['public_freqlist_desc']) 
					. "</option>\n" )
			);

	foreach ($public_subcorpora as $ps)
		$list_options_list2 .= "\t\t\t\t\t<option value=\"ps~{$ps['freqtable_name']}\">
			Public frequency list: subcorpus {$subc_mapper[$ps['query_scope']]} from corpus {$ps['corpus']}
			</option>\n";

	/* and the options for selecting an attribute */

	$attribute = get_corpus_annotations();

	$att_options = '<option value="word">Word forms</option>
		';

	foreach ($attribute as $k => $a)
	{
		$a = escape_html($a);
		$att_options .= "<option value=\"$k\">$a</option>\n";
	}


?>
<table class="concordtable" width="100%">

	<tr>
		<th class="concordtable" colspan="4">Keywords and key tags</th>
	</tr>

	<tr>
		<td class="concordgrey" colspan="4" align="center">&nbsp;<br />
			Keyword lists are compiled by comparing frequency lists you have
			created for different subcorpora. <a
			href="index.php?thisQ=subcorpus&uT=y">Click here to create/view
				frequency lists</a>. <br />&nbsp;
		</td>
	</tr>

	<form action="keywords.php" method="get">
		<tr>
			<td class="concordgeneral">Select frequency list 1:</td>
			<td class="concordgeneral"><select name="kwTable1">
					<?php echo $list_options; ?>
				</select></td>
			<td class="concordgeneral">Select frequency list 2:</td>
			<td class="concordgeneral"><select name="kwTable2">
					<?php echo $list_options_list2; ?>
				</select></td>
		</tr>
		<tr>
			<td class="concordgeneral">Compare:</td>
			<td class="concordgeneral" colspan="3"><select name="kwCompareAtt">
					<?php echo $att_options; ?>
				</select></td>
		</tr>

		<tr>
			<th class="concordtable" colspan="4">Options for keyword analysis:</th>
		</tr>


		<tr>
			<td class="concordgeneral" rowspan="2">Show:</td>
			<td class="concordgeneral" rowspan="2"><select name="kwWhatToShow">
					<option value="allKey">All keywords</option>
					<option value="onlyPos">Positive keywords</option>
					<option value="onlyNeg">Negative keywords</option>
					<option value="lock">Lockwords</option>
			</select></td>
			<td class="concordgeneral">Comparison statistic:</td>
			<td class="concordgeneral"><select name="kwStatistic">
					<option value="LL" selected="selected">Log-likelihood</option>
					<option value="LR_LL">Log Ratio with Log-likelihood filter</option>
					<option value="LR_CI">Log Ratio with Confidence Interval filter</option>
					<option value="LR_UN">Log Ratio (unfiltered)</option>
			</select></td>
		</tr>

		<tr>
			<td class="concordgeneral">Significance cut-off point: <br>(or
				confidence interval width)
			</td>
			<td class="concordgeneral"><select name="kwAlpha">
					<option value="0.05">5%</option>
					<option value="0.01">1%</option>
					<option value="0.001">0.1%</option>
					<option value="0.0001" selected="selected">0.01%</option>
					<option value="0.00001">0.001%</option>
					<option value="0.000001">0.0001%</option>
					<option value="0.0000001">0.00001%</option>
					<option value="1.0">No cut-off</option>
			</select> <br> <input name="kwFamilywiseCorrect" value="Y"
				type="checkbox" checked="checked" /> Use Šidák correction?</td>
		</tr>


		<tr>
			<td class="concordgeneral">Min. frequency (list 1):</td>
			<td class="concordgeneral"><select name="kwMinFreq1">
					<option>1</option>
					<option>2</option>
					<option selected="selected">3</option>
					<option>4</option>
					<option>5</option>
					<option>6</option>
					<option>7</option>
					<option>8</option>
					<option>9</option>
					<option>10</option>
					<option>15</option>
					<option>20</option>
					<option>50</option>
					<option>100</option>
					<option>500</option>
					<option>1000</option>
			</select></td>
			<td class="concordgeneral">Min. frequency (list 2):</td>
			<td class="concordgeneral"><select name="kwMinFreq2">
					<option>0</option>
					<option>1</option>
					<option>2</option>
					<option selected="selected">3</option>
					<option>4</option>
					<option>5</option>
					<option>6</option>
					<option>7</option>
					<option>8</option>
					<option>9</option>
					<option>10</option>
					<option>15</option>
					<option>20</option>
					<option>50</option>
					<option>100</option>
					<option>500</option>
					<option>1000</option>
			</select></td>
		</tr>

		<tr>
			<td class="concordgeneral" colspan="4" align="center">&nbsp;<br> <input
				type="submit" name="kwMethod" value="Calculate keywords" /> <br>&nbsp;
			</td>
		</tr>

		<tr>
			<th class="concordtable" colspan="4">View unique words or tags on one
				frequency list:</th>
		</tr>

		<tr>
			<td class="concordgeneral" colspan="2">Display items that occur in:</td>
			<td class="concordgeneral" colspan="2"><select name="kwEmpty">
					<option value="f1">Frequency list 1 but NOT frequency list 2</option>
					<option value="f2">Frequency list 2 but NOT frequency list 1</option>
			</select></td>
		</tr>

		<tr>
			<td class="concordgeneral" colspan="4" align="center">&nbsp;<br> <input
				type="submit" name="kwMethod" value="Show unique items on list" /> <br>&nbsp;
			</td>
		</tr>

		<input type="hidden" name="uT" value="y" />
	</form>

</table>
<?php

}





function printquery_freqlist()
{
	/* much of this is the same as the form for keywords, but simpler */
	global $Corpus;
	
	/* create the options for frequency lists to compare */
	
	/* subcorpora belonging to this user that have freqlists compiled (list of IDs returned) */
	$subcorpora = list_freqtabled_subcorpora();
	/* public freqlists - corpora */
	
	$list_options = "<option value=\"__entire_corpus\">". escape_html($Corpus->title) . "</option>\n";
	
	$subc_mapper = get_subcorpus_name_mapper();
	foreach ($subcorpora as $s)
		$list_options .= "<option value=\"$s\">Sous-corpus: {$subc_mapper[$s]}</option>\n";
		
		/* and the options for selecting an attribute */
		
		$attribute = get_corpus_annotations();
		
		$att_options = '<option value="word">Forme</option>
		';
		
		foreach ($attribute as $k => $a){
			if($k != 'pb'){
			$att_options .= "<option value=\"$k\">$a</option>\n";
			}
		}
			
			?>
	<div class="status prepend-2 span-22" style="text-align:center">
	    <h1>Liste de fréquences</h1>
	</div>
<table class="concordtable" width="100%">

<!-- 	<tr> -->
<!-- 		<th class="concordtable" colspan="2">Liste de fréquences</th> -->
<!-- 	</tr> -->

	<tr>
		<td class="concordgrey" colspan="2" align="center">
			Vous pouvez afficher les listes de fréquences de  corpus entier et des listes de fréquences pour
sous-corpus que vous avez créée. <a href="index.php?thisQ=subcorpus&uT=y">Cliquez
ici pour créer / afficher des listes de fréquences de sous-corpus</a>.
		</td>
	</tr>

	<form action="freqlist.php" method="get">
		<tr>
			<td class="concordgeneral">Liste de fréquences pour ...</td>
			<td class="concordgeneral">
				<select name="flTable">
					<?php echo $list_options; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">Liste basée sur ...</td>
			<td class="concordgeneral">
				<select name="flAtt">
					<?php echo $att_options; ?>
				</select>
			</td>
		</tr>

		<tr>
			<th class="concordtable" colspan="2">Les options de la liste</th>
		</tr>

		<tr>
			<td class="concordgeneral">Filtrer la liste par <em>pattern</em> ...</td>
			<td class="concordgeneral">
				<select name="flFilterType">
					<option value="begin" selected="selected">commence par</option>
					<option value="end">fini par</option>
					<option value="contain">contient</option>
					<option value="exact">correspond exactement</option>
				</select>
				&nbsp;&nbsp;
				<input type="text" name="flFilterString" size="32" />
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">Filtrer la liste par <em>fréquence</em>  ...</td>
			<td class="concordgeneral">
				fréquence de
				<input type="text" name="flFreqLimit1" size="8" />
				à
				<input type="text" name="flFreqLimit2" size="8" />
			</td>
		</tr>


		<tr>
			<td class="concordgeneral">Nombre de résultats par page:</td>
			<td class="concordgeneral">
				<select name="pp">
					<option>10</option>
					<option selected="selected">50</option>
					<option>100</option>
					<option>250</option>
					<option>350</option>
					<option>500</option>
					<option>1000</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">Ordre:</td>
			<td class="concordgeneral">
				<select name="flOrder">
					<option value="desc" selected="selected">fréquence décroissantes</option>
					<option value="asc">fréquence croissantes</option>
					<option value="alph">Ordre alphabetique</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral" colspan="2" align="center">
				&nbsp;<br/>
				<input type="submit" value="Valider" />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="reset" value="Annuler" />
				<br/>&nbsp;
			</td>
		</tr>
		<input type="hidden" name="uT" value="y" />
	</form>

</table>
<?php


}


/* not really a "query", but closer to belonging in this file than any other. */
function printquery_corpusmetadata()
{
	global $Corpus;

	?>
<table class="concordtable" width="100%">

	<tr>
		<th colspan="2" class="concordtable">
				Métadonnée pour <?php echo escape_html($Corpus->title); ?>
			</th>
	</tr>

	<?php

	/* set up the data we need */

	/* number of files in corpus */
	list($num_texts) = mysql_fetch_row(do_mysql_query("select count(text_id) from text_metadata_for_{$Corpus->name}"));
	$num_texts = number_format((float)$num_texts);

	/* now get tokens / types */
	$tokens = get_corpus_wordcount();
	$types  = get_corpus_n_types();
	$words_in_all_texts = empty($tokens) ? 'Cannot be calculated (wordcount not cached)'        : number_format((float)$tokens);
	$types_in_corpus    = empty($types)  ? 'Cannot be calculated (frequency tables not set up)' : number_format((float)$types);
	$type_token_ratio   = (empty($tokens)||empty($types))
							? 'Cannot be calculated (type or token count not available)'
							: number_format( ((float)$types / (float)$tokens) , 4) . ' types per token';


	/* create a placeholder for the primary annotation's description */
	$primary_annotation_string = $Corpus->primary_annotation;
	/* the description itself will be grabbed when we scroll through the full list of annotations */


	?>
		<tr>
		<td width="50%" class="concordgrey">Corpus title</td>
		<td width="50%" class="concordgeneral"><?php echo escape_html($Corpus->title); ?></td>
	</tr>
	<tr>
		<td class="concordgrey">CQPweb's short handles for this corpus</td>
		<td class="concordgeneral"><?php echo "{$Corpus->name} / {$Corpus->cqp_name}"; ?></td>
	</tr>
	<tr>
		<td class="concordgrey">Total number of corpus texts</td>
		<td class="concordgeneral"><?php echo $num_texts; ?></td>
	</tr>
	<tr>
		<td class="concordgrey">Total words in all corpus texts</td>
		<td class="concordgeneral"><?php echo $words_in_all_texts; ?></td>
	</tr>
	<tr>
		<td class="concordgrey">Word types in the corpus</td>
		<td class="concordgeneral"><?php echo $types_in_corpus; ?></td>
	</tr>
	<tr>
		<td class="concordgrey">Type:token ratio</td>
		<td class="concordgeneral"><?php echo $type_token_ratio; ?></td>
	</tr>

	<?php


	/* VARIABLE METADATA */

	$result_variable = do_mysql_query("select * from corpus_metadata_variable where corpus = '{$Corpus->name}'");

	while (false !== ($metadata = mysql_fetch_assoc($result_variable)) )
	{
		/* if it looks like a URL, linkify it */
		if (0 < preg_match('|^https?://\S+$|', $metadata['value']))
			$metadata['value'] = "<a href=\"{$metadata['value']}\" target=\"_blank\">" . escape_html($metadata['value']) . "</a>";
		else
			$metadata['value'] = escape_html($metadata['value']);
		?>

		<tr>
		<td class="concordgrey"><?php echo escape_html($metadata['attribute']); ?></td>
		<td class="concordgeneral"><?php echo $metadata['value']; ?></td>
	</tr>

		<?php
	}

	?>

		<tr>
		<th class="concordtable" colspan="2">Text metadata and word-level
			annotation</th>
	</tr>

	<?php


	/* TEXT CLASSIFICATIONS */

	$result_textfields = do_mysql_query("select handle from text_metadata_fields where corpus = '{$Corpus->name}'");
	$num_rows = mysql_num_rows($result_textfields);

	?>

		<tr>
		<td rowspan="<?php echo $num_rows; ?>" class="concordgrey">The
			database stores the following information for each text in the
			corpus:</td>

	<?php
	$i = 1;
	while (($metadata = mysql_fetch_row($result_textfields)) != false)
	{
		echo '<td class="concordgeneral">';
		echo escape_html(metadata_expand_field($metadata[0]));
		echo '</td></tr>';
		if (($i) < $num_rows)
			echo '<tr>';
		$i++;
	}
	if ($i == 1)
		echo '<td class="concordgeneral">There is no text-level metadata for this corpus.</td></tr>';
	?>
		
	
	
	<tr>
		<td class="concordgrey">The <b>primary</b> classification of texts is
			based on:
		</td>
		<td class="concordgeneral">
				<?php
				echo (empty($Corpus->primary_classification_field)
					? 'A primary classification scheme for texts has not been set.'
					: escape_html(metadata_expand_field($Corpus->primary_classification_field)))
					;
				?>
			</td>
	</tr>
	<?php


	/* ANNOTATIONS */
	/* get a list of annotations */
	$result_annotations = do_mysql_query("select * from annotation_metadata where corpus = '{$Corpus->name}'");

	$num_rows = mysql_num_rows($result_annotations);
	?>
		<tr>
		<td rowspan="<?php echo $num_rows; ?>" class="concordgrey">Words in
			this corpus are annotated with:</td>
	<?php
	$i = 1;

	while (($annotation = mysql_fetch_assoc($result_annotations)) != false)
	{
		echo '<td class="concordgeneral">';
		if ($annotation['description'] != "")
		{
			echo escape_html($annotation['description']);

			/* while we're looking at the description, save it for later if this
			 * is the primary annotation */
			if ($primary_annotation_string == $annotation['handle'])
				$primary_annotation_string  = escape_html($annotation['description']);
		}
		else
			echo $annotation['handle'];
		if ($annotation['tagset'] != "")
		{
			echo ' (';
			if ($annotation['external_url'] != "")
				echo '<a target="_blank" href="', $annotation['external_url'], '">', $annotation['tagset'], '</a>';
			else
				echo $annotation['tagset'];
			echo ')';
		}

		echo '</td></tr>';
		if (($i) < $num_rows)
			echo '<tr>';
		$i++;
	}
	/* if there were no annotations.... */
	if ($i == 1)
		echo '<td class="concordgeneral">There is no word-level annotation in this corpus.</td></tr>';
	?>
		
	
	
	<tr>
		<td class="concordgrey">The <b>primary</b> word-level annotation
			scheme is:
		</td>
		<td class="concordgeneral">
				<?php 
				echo empty($primary_annotation_string) 
					? 'No primary word-level annotation scheme has been set' 
					: $primary_annotation_string; 
				?>
			</td>
	</tr>
	<?php


	/* EXTERNAL URL */
	if ( ! empty($Corpus->external_url) )
	{
		?>
		<tr>
		<td class="concordgrey">Further information about this corpus is
			available on the web at:</td>
		<td class="concordgeneral"><a target="_blank"
			href="<?php echo escape_html($Corpus->external_url); ?>">
					<?php echo escape_html($Corpus->external_url); ?>
				</a></td>
	</tr>
		<?php
	}

	?>
	</table>
<?php
}


function printquery_export()
{
	global $Corpus;
	global $User;

	if (PRIVILEGE_TYPE_CORPUS_FULL > $Corpus->access_level)
		exiterror("You do not have permission to use this function.");


	/* enable the user setting to be auto-selected for linebreak type */
	$da_selected = array('d' => '', 'a' => '', 'da' => '');
	if ($User->linefeed == 'au')
		$User->linefeed = guess_user_linefeed($User->username);
	$da_selected[$User->linefeed] = ' selected="selected" ';

	?>
<table class="concordtable" width="100%">

	<tr>
		<th colspan="2" class="concordtable">Export corpus or subcorpus</th>
	</tr>

	<tr>
		<td colspan="2" class="concordgrey">
			<p class="spacer">&nbsp;</p>
			<p>If you &ldquo;export&rdquo; a corpus, you download a copy of the
				whole text of the corpus (or one of your subcorpora) allowing you to
				analyse it offline.</p>
			<p>Be warned: export downloads can be very big files!</p>
			<p class="spacer">&nbsp;</p>
		</td>
	</tr>

	<form action="export.php" method="get">

		<tr>
			<td class="concordgeneral">What do you want to export?</td>
			<td class="concordgeneral"><select name="exportWhat">
					<option selected="selected" value="~~corpus">Whole corpus</option>
						<?php
						$result = do_mysql_query("select * from saved_subcorpora where corpus = '{$Corpus->name}' and user = '{$User->username}' order by name");

						while (false !== ($sc = Subcorpus::new_from_db_result($result)))
							if ($sc->name != '--last_restrictions')
								echo "\n\t\t\t\t\t\t<option value=\"sc~", $sc->id, '">', 'Subcorpus "', $sc->name, '"</option>';
						?>
					</select></td>
		</tr>

		<tr>
			<td class="concordgeneral" rowspan="3">Choose an export format:</td>
			<td class="concordgeneral"><input type="radio" name="format"
				value="standard" checked="checked"> Standard plain text</td>
		</tr>
		<tr>
			<td class="concordgeneral"><input type="radio" name="format"
				value="word_annot">Word-and-tag format (joined with forward-slash)</td>
		</tr>
		<tr>
			<td class="concordgeneral"><input type="radio" name="format"
				value="col">Columnar with all tags (CWB input format)</td>
		</tr>
		<!-- 			<tr> -->
		<!-- 				<td class="concordgeneral"> -->
		<!-- 					<input type="radio" name="format" value="xml">XML format with all tags-->
		<!-- 				</td> -->
		<!-- 			</tr>	 -->

		<tr>
			<td class="concordgeneral">Choose the operating system on which you
				will use the file:</td>
			<td class="concordgeneral"><select name="exportLinebreak">
					<option value="d" <?php echo $da_selected['d']; ?>>Macintosh (OS 9
						and below)</option>
					<option value="da" <?php echo $da_selected['da'];?>>Windows</option>
					<option value="a" <?php echo $da_selected['a']; ?>>UNIX (incl. OS
						X)</option>
			</select></td>
		</tr>

		<tr>
			<td class="concordgeneral">Enter a name for the downloaded file:</td>
			<td class="concordgeneral"><input type="text" name="exportFilename"
				value="<?php echo $Corpus->name; ?>-export" /></td>
		</tr>

		<tr>
			<td colspan="2" class="concordgeneral">
				<p class="spacer">&nbsp;</p>
				<p align="center">
					<input type="submit" value="Click to export corpus data!" />
				</p>
				<p class="spacer">&nbsp;</p>
			</td>
		</tr>

		<input type="hidden" name="uT" value="y" />

	</form>

</table>
<?php
}


