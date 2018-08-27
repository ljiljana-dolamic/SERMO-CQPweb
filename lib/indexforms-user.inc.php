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
 * Each of these functions prints a table for the right-hand side interface.
 * 
 * This file contains the forms deployed by userhome and not queryhome. 
 * 
 */




function printscreen_accessdenied()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Access denied!
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p>&nbsp;</p>
				
				<?php
				
				global $User;
				
				if ($User->logged_in)
				{
					?>
					<p>
						You do not have the necessary privileges to access the corpus 
						<b><?php echo escape_html(isset($_GET['corpusDenied']) ? $_GET['corpusDenied'] : ''); ?></b>.
					</p>
					<?php
					
					// TODO : if the corpus has an access statement, spell it out here.
				}
				else
				{
					?>
					<p>
						You cannot access that corpus because you are not logged in.
					</p>
					<p>
						Please <a href="../usr/index.php?thisQ=login&uT=y">log in to CQPweb</a> and then try again!
					</p>
					<?php
				}
				
				?>

				<p>&nbsp;</p>
			</td>
		</tr>
	</table>
	<?php
}


function printscreen_welcome()
{
	global $User;
	
	if (empty($User->realname) || $User->realname == 'unknown person')
		$personalise = '';
	else
		$personalise = ', ' . escape_html($User->realname);
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				You are logged on to CQPweb
			</th>
		</tr>
		
		<?php
		if (!empty($_GET['extraMsg']))
			echo '<tr><td class="concordgeneral">&nbsp;<br/>', escape_html($_GET['extraMsg']), "<br/>&nbsp;</td></tr>\n";
		?>
		
		<tr>
			<td class="concordgeneral">
				&nbsp;<br/>
			
				Welcome back to the CQPweb server<?php echo $personalise; ?>. You are logged in to the system.

				<br/>&nbsp;<br/>

				This is your user page; select an option from the menu on the left, or
				<a href="../">click here to return to the main homepage</a>.

				<br/>&nbsp;
			</td>
		</tr>
	</table>
	<?php
}

function printscreen_login()
{
	?>
	<div class="column span-16">
	<table class="concordtable" width="100%">
		

		<?php
		if (!empty($_GET['extraMsg']))
			echo '<tr><td class="concordgeneral">&nbsp;<br/>', escape_html($_GET['extraMsg']), "<br/>&nbsp;</td></tr>\n";
		?>

		<tr>
			<td class="concordgeneral">
				
				<?php
				
				echo print_login_form( isset($_GET['locationAfter']) ? $_GET['locationAfter'] : false );
				
				?>
			
			</td>
		</tr>
	</table>
	</div>
	<?php
}


function printscreen_logout()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Log out of CQPweb?
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p>&nbsp;</p>
				<p>Are you sure you want to log out of the system?</p>
				
				<table class="basicbox" style="margin:auto">
					<form action="redirect.php" method="GET">
						<tr>
							<td class="basicbox">
								<input type="submit" value="Click here to log out and return to the main menu" />
							</td>
						</tr>
						<input type="hidden" name="redirect" value="userLogout" />
						<input type="hidden" name="uT" value="y" />
					</form>
				</table>

				<p>&nbsp;</p>
			</td>
		</tr>
	</table>
	<?php
}


function printscreen_create()
{
	global $Config;
	
	/**
	 * If we are returning from a failed CAPTCHA, we should put several of the values into the slots.
	 */
	if (isset($_GET['captchaFail']))
	{
		$prepop = new stdClass();
		foreach (array('newUsername', 'newEmail', 'realName', 'affiliation', 'country') as $x)
			$prepop->$x = isset($_GET[$x]) ? escape_html($_GET[$x]) : '';
	}
	else
		$prepop = false;
	

	if (!$Config->allow_account_self_registration)
	{
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Account self-registration not available
				</th>
			</tr>
			<tr>
				<td class="concordgrey" colspan="2">
					&nbsp;<br/>
					Sorry but self-registration has been disabled on this CQPweb server. 
					<?php
					if (! empty($Config->account_create_contact))
						echo "<br/>&nbsp;<br/>To request an account, contact {$Config->account_create_contact}."; 					
					?>
					<br/>&nbsp;
				</td>
			</tr>
		</table>
		<?php	
		return;
	}
	
	/* initialise the iso 3166-1 array... */
	require('../lib/user-iso31661.inc.php');
	natsort($Config->iso31661);

	?>
	<div class="column span-16">
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">
				<h1>Créer un compte</h1>
			</th>
		</tr>
		<tr>
		<td class="concordgrey" colspan="2">
		 <strong>Les champs marqués par * sont obligatoire</strong>
		</td>
		</tr>
<!-- 		<tr> -->
<!-- 			<td class="concordgrey" colspan="2"> -->
<!-- 				&nbsp;<br/> -->
<!-- 				<b>First</b>, select a username and password. Your username can be up to 30 letters long, and must consist of only -->
<!-- 				unaccented letters, digits and the underscore (&nbsp;_&nbsp;). -->
<!-- 				<br/>&nbsp;<br/> -->
<!-- 				Your password or passphrase can consist of any characters you like including punctuation marks and spaces.  -->
<!-- 				The length limit is 255 characters. -->
<!-- 				<br/>&nbsp; -->
<!-- 			</td> -->
<!-- 		</tr> -->
		<?php
		if ($prepop)
		{
			?>
			<tr>
				<td class="concorderror" colspan="2">
					&nbsp;<br/>
					You failed the human-being test; please try again.
					<br/>&nbsp;<br/>
					Note: you will need to re-enter your chosen password.
					<br/>&nbsp;
				</td>
			</tr>
			<?php
		}
		?>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					<strong>* Nom d'utilisateur:</strong>( chiffres, caractères sans accent, tiret bas (&nbsp;_&nbsp;)!; max. 30 caractère.):
				</td>
				<td class="concordgeneral">
					<input type="text" size="30" maxlength="30" name="newUsername" 
					<?php
					if ($prepop)
						echo " value=\"{$prepop->newUsername}\" ";
					?>
					/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					<strong>* Mot de passe:</strong>
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="newPassword" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					<strong>* Repetez le mot de passe:</strong>
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="newPasswordCheck" />
				</td>
			</tr>
			
			<tr>
				<td class="concordgeneral">
					<strong>* e-mail</strong>
					
				</td>
				<td class="concordgeneral">
					<input type="text" size="30" maxlength="255" name="newEmail"
					<?php
					if ($prepop)
						echo " value=\"{$prepop->newEmail}\" ";
					?>
					/>
				</td>
			</tr>
			<?php
			
			if ($Config->account_create_captcha)
			{
				$captcha_code = create_new_captcha();
				$params = "redirect=captchaImage&which=$captcha_code&uT=y&cacheblock=" . uniqid();
				?>
				<tr>
					<td class="concordgeneral">
						Type in the 6 characters from the picture to prove you are a human being:
						<br>
						<em>NB.: all letters are lowercase.</em>
					</td>
					<td class="concordgeneral">
						<script type="text/javascript" src="../jsc/captcha.js"></script>
						<img id="captchaImg" src="../usr/redirect.php?<?php echo $params; ?>" />
						<br/>
						<a onClick="refresh_captcha()" class="menuItem">[Too hard? Click for another]</a>
						<br/>
						<input type="text" size="30" maxlength="10" name="captchaResponse" />
					</td>
				</tr>
				<input id="captchaRef" type="hidden" name="captchaRef" value="<?php echo $captcha_code; ?>" />
				<?php
			}
			
			?>
			
			<tr>
				<td class="concordgeneral">
					Nom:
				</td>
				<td class="concordgeneral">
					<input type="text" size="30" maxlength="255" name="realName" 
					<?php
					if ($prepop)
						echo " value=\"{$prepop->realName}\" ";
					?>
					/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Affiliation:
					
				</td>
				<td class="concordgeneral">
					<input type="text" size="30" maxlength="255" name="affiliation" 
					<?php
					if ($prepop)
						echo " value=\"{$prepop->affiliation}\" ";
					?>
					/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Pays:
				</td>
				<td class="concordgeneral">
					<select name="country">
						<option selected="selected" value="00">Non précisé</option>
						<?php
						if ( (! $prepop) || '00' == $prepop->country)
							echo '<option selected="selected" value="00">Non précisé</option>', "\n";
						else
							echo '<option selected="selected" value="00">Non précisé</option>, "\n"';				
						unset($Config->iso31661['00']);
						foreach($Config->iso31661 as $code => $country)
						{
							if ($prepop && $code == $prepop->country)
								echo "\t\t\t\t\t\t<option selected=\"selected\" value=\"$code\">$country</option>\n";
							else
								echo "\t\t\t\t\t\t<option value=\"$code\">$country</option>\n";
						}
						?>
					</select>
				</td>
			</tr>
<!-- 			<tr> -->
<!-- 				<td class="concordgrey" colspan="2" align="center"> -->
<!-- 					&nbsp;<br/> -->
<!-- 					When you are happy with the settings you have entered, use the button below to register. -->
<!-- 					<br/>&nbsp; -->
<!-- 				</td> -->
<!-- 			</tr> -->
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
					<input type="submit" value="Créez le compte" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="redirect" value="newUser" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	</div>
	<?php
}




function printscreen_verify()
{
	$screentype = (isset($_GET['verifyScreenType']) ? $_GET['verifyScreenType'] : 'newform');
	
	if ($screentype == 'newform' || $screentype == 'badlink')
	{
		?>
		<div class="column span-16">
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Saisissez la clef d'activation
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					<?php
					if ($screentype=='badlink')
						echo "\t\t\t\t\t<p> On n'a pas pu lire une clé de vérification depuis le lien sur lequel vous avez cliqué.</p>\n"
							,"\t\t\t\t\t<p>Saisissez le code de 32 lettres manuellement?</p>\n";
					else
						echo "\t\t\t\t\t<p>Vous devriez avoir reçu un email avec un code de 32 lettres.</p>\n"
							,"\t\t\t\t\t<p>Saisissez ce code dans le formulaire ci-dessous pour activer le compte.</p>\n";						
					?>

					<form action="redirect.php" method="get">
					
						<table class="basicbox" style="margin:auto">
							<tr>
								<td class="basicbox" >
									Saisez la clef:
								</td>
								<td class="basicbox" >
									<input type="text" name="v" size="32" maxlength="32" />
								</td>
							</tr>

							<tr>
								<td class="basicbox" colspan="2" align="center">
									<input type="submit" value="Activer" /> 
								</td>
							</tr>						
						</table>
						<input type="hidden" name="redirect" value="verifyUser" />
						<input type="hidden" name="uT" value="y" />
					</form>
					<p>Si vous n'avez pas reçu l'e-mail avec la clef d'activation,
						<a href="index.php?thisQ=resend&uT=y">cliquez ici</a>
						pour en demander un autre.
					</p>
					<p>&nbsp;</p>
				</td>
			</tr>
		</table>
		</div>
		<?php	}
	else if ($screentype == 'success')
	{
		?>
		<div class="column span-16">
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Votre compte est activé!
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					<p align="center">
						Votre compte est activé! 
					</p>
					<p align="center">
						Welcome to our CQPweb server!
					</p>
					<p align="center">
						<a href="index.php">Cliquez ici pour s'identifier.</a>
					</p>
					<p>&nbsp;</p>
				</td>
			</tr>
		</table>
		</div>
		<?php
	}
	else if ($screentype == 'failure')
	{
		?>
		<div class="column span-16">
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					La vérification du compte a échoué!
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					<p>
						Votre compte n'a pas pu être vérifié. La clef d'activation fournie n'a pas été trouvée dans notre base de données. 
					</p>
					<p>
						Nous vous recommandons de demander <a href="index.php?thisQ=resend">une nouvelle clef d'activation</a>.
					</p>
					<p>
						Si un nouveau courriel ne résout pas le problème, nous suggérons de
						<a href="create">redémarrer le processus de création de compte</a>.
					</p>
					<p>&nbsp;</p>
				</td>
			</tr>
		</table>
		</div>
		<?php
	}
	else if ($screentype == 'newEmailSent')
	{
		?>
		<div class="column span-16">
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">

Un nouvel e-mail de vérification a été envoyé!
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					<p>
						Un message avec un nouveau lien d'activation devrait arriver bientôt. 
					</p>
					<p>
						Notez que les liens d'activation des e-mails antérieurs <em> ne fonctionnent plus</em>.
					</p>
					<p>&nbsp;</p>
				</td>
			</tr>
		</table>
		</div>
		<?php
	}
}


function printscreen_resend()
{
	?>
	<div class="column span-16">
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				<h1>Ré-envoyer l'email d'activation du compte</h1>
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p>&nbsp;</p>
				

				<table class="basicbox" style="margin:auto">
					<form action="redirect.php" method="GET">
						<tr>
							<td class="basicbox">Saisissez le e-mail:</td>
							<td class="basicbox">
								<input type="text" name="email" width="50" />
							</td>
						</tr>
						<tr>
							<td class="basicbox" colspan="2">
								<input type="submit" value="Demander un nouvel e-mail d'activation" />
							</td>
						</tr>
						<input type="hidden" name="redirect" value="resendVerifyEmail" />
						<input type="hidden" name="uT" value="y" />
					</form>
				</table>

				<p>&nbsp;</p>
			</td>
		</tr>
	</table>
	</div>
	<?php
}




function printscreen_lostusername()
{
	?>
	<div class="column span-16">
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				<h1>Récupérer le nom d'utilisateur perdu ou oublié</h1>
			</th>
		</tr>
		<tr>
			<form action="redirect.php" method="GET">
				<td class="concordgeneral">
					<p align="center">Si vous avez perdu ou oublié votre nom d'utilisateur, vous pouvez demander un rappel par e-mail</p>
					
					<p align="center">
						Votre e-mail: <input type="text" name="emailToRemind" size="30" maxlength="255" />
					</p>
					<p align="center">
						<input type="submit" value="Demander un rappel de nom d'utilisateur" />
					</p>
					<p>&nbsp;</p>
				</td>
				<input type="hidden" name="redirect" value="remindUsername" />
				<input type="hidden" name="uT" value="y" />
			</form>
		</tr>
	</table>
	</div>
	<?php
}


function printscreen_lostpassword()
{
	?>
	<div class="column span-16">
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">
				<h1>Réinitialiser le mot de passe perdu</h1>
			</th>
		</tr>
		<?php
		
		if (isset($_GET['showSentMessage']) && $_GET['showSentMessage'])
		{
			?>
			
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
					<b>
						Un e-mail a été envoyé à l'adresse associée à votre compte. S'il vous plaît vérifier votre boîte de réception!
					</b>
					<br/>&nbsp;
				</td>
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td class="concordgrey" colspan="2">
				&nbsp;<br/>
				
				<br/>&nbsp;<br/>
				
				Tout d'abord, utilisez le  <b> premier </b> formulaire ci-dessous pour demander un code de vérification de réinitialisation du mot de passe.
Cela sera envoyé à l'adresse e-mail associée à votre nom d'utilisateur.
				
				<br/>&nbsp;<br/>
				
				Ensuite, revenez sur cette page et utilisez le  <b> second </b> formulaire ci-dessous pour changer votre mot de passe, en utilisant le
code de vérification que nous vous envoyons par e-mail.
				
				<br/>&nbsp;
			</td>
		<tr>
			<th class="concordtable" colspan="2">
				<h1>Demander la réinitialisation du mot de passe</h1>
			</th>
		</tr>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Saisissez votre e-mail:
				</td>
				<td class="concordgeneral">
					<input type="text" size="40" maxlength="30" name="userForPasswordReset" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
						<input type="submit" value="Demander le code de verification" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="redirect" value="requestPasswordReset" />
			<input type="hidden" name="uT" value="y" />
		</form>
		<tr>
			<th class="concordtable" colspan="2">
				<hi>Réinitialisez votre mot de passe</hi>
			</th>
		</tr>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Nom d'utilisateur:
				</td>
				<td class="concordgeneral">
					<input type="text" size="40" maxlength="30" name="userForPasswordReset" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Saisissez votre <b>nouveau</b> mot de passe:
				</td>
				<td class="concordgeneral">
					<input type="password" size="40" maxlength="255" name="newPassword" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Retapez le <b> nouveau </ b> mot de passe:
				</td>
				<td class="concordgeneral">
					<input type="password" size="40" maxlength="255" name="newPasswordCheck" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Entrez le code de vérification de 32 lettres qui vous a été envoyé par courriel:
					<br/>
					<em>(avec/sana espaces)</em>
				</td>
				<td class="concordgeneral">
					<input type="text" size="40" maxlength="40" name="v" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
					<input type="submit" value="Réinitialiser le mot de passe" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="redirect" value="resetUserPassword" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	</div>
	<?php
}





function printscreen_usersettings()
{
	global $User;
	
	list ($optionsfrom, $optionsto) = print_fromto_form_options(10, $User->coll_from, $User->coll_to);
	
	?>
<table class="concordtable" width="100%">

	<form action="redirect.php" method="get">
	
		<tr>
			<th colspan="2" class="concordtable">User interface settings</th>
		</tr>
	
		<tr>
			<td colspan="2" class="concordgrey" align="center">
				<p>&nbsp;</p>
				<p>Use this form to personalise your options for the user interface.</p> 
				<p>Important note: these settings apply to all the corpora that you access on CQPweb.</p>
				<p>&nbsp;</p>
			</td>
		</tr>		

		<tr>
			<th colspan="2" class="concordtable">Display options</th>
		</tr>		

		<tr>
			<td class="concordgeneral">Default view</td>
			<td class="concordgeneral">
				<select name="newSetting_conc_kwicview">
					<option value="1"<?php echo ($User->conc_kwicview == '0' ? ' selected="selected"' : '');?>>KWIC view</option>
					<option value="0"<?php echo ($User->conc_kwicview == '0' ? ' selected="selected"' : '');?>>Sentence view</option>
				</select>
			</td>
		</tr>


		<tr>
			<td class="concordgeneral">Default display order of concordances</td>
			<td class="concordgeneral">
				<select name="newSetting_conc_corpus_order">
					<option value="1"<?php echo ($User->conc_corpus_order == '1' ? ' selected="selected"' : '');?>>Corpus order</option>
					<option value="0"<?php echo ($User->conc_corpus_order == '0' ? ' selected="selected"' : '');?>>Random order</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">
				Show Simple Query translated into CQP syntax (in title bar and query history)
			</td>
			<td class="concordgeneral">
				<select name="newSetting_cqp_syntax">
					<option value="1"<?php echo ($User->cqp_syntax == '1' ? ' selected="selected"' : '');?>>Yes</option>
					<option value="0"<?php echo ($User->cqp_syntax == '0' ? ' selected="selected"' : '');?>>No</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">Context display</td>
			<td class="concordgeneral">
				<select name="newSetting_context_with_tags">
					<option value="0"<?php echo ($User->context_with_tags == '0' ? ' selected="selected"' : '');?>>Without tags</option>
					<option value="1"<?php echo ($User->context_with_tags == '1' ? ' selected="selected"' : '');?>>With tags</option>
				</select>
			</td>
		</tr>
		
		<tr>
			<td class="concordgeneral">
				Show tooltips (JavaScript enabled browsers only)
				<br/>
				<em>(When moving the mouse over some links (e.g. in a concordance), additional 
				information will be displayed in tooltip boxes.)</em>
			</td>
			<td class="concordgeneral">
				<select name="newSetting_use_tooltips">
					<option value="1"<?php echo ($User->use_tooltips == '1' ? ' selected="selected"' : '');?>>Yes</option>
					<option value="0"<?php echo ($User->use_tooltips == '0' ? ' selected="selected"' : '');?>>No</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">Default setting for thinning queries</td>
			<td class="concordgeneral">
				<select name="newSetting_thin_default_reproducible">
					<option value="0"<?php echo ($User->thin_default_reproducible == '0' ? ' selected="selected"' : '');?>>Random: selection is not reproducible</option>
					<option value="1"<?php echo ($User->thin_default_reproducible == '1' ? ' selected="selected"' : '');?>>Random: selection is reproducible</option>
				</select>
			</td>
		</tr>

		<tr>
			<th colspan="2" class="concordtable">Collocation options</th>
		</tr>		

		<tr>
			<td class="concordgeneral">Default statistic to use when calculating collocations</td>
			<td class="concordgeneral">
				<select name="newSetting_coll_statistic">
					<?php echo print_statistic_form_options($User->coll_statistic); ?>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">
				Default minimum for freq(node, collocate) [<em>frequency of co-occurrence</em>]
			</td>
			<td class="concordgeneral">
				<select name="newSetting_coll_freqtogether">
					<?php echo print_freqtogether_form_options($User->coll_freqtogether); ?>
				</select>
			</td>
		</tr>

		<tr>                               
			<td class="concordgeneral">
				Default minimum for freq(collocate) [<em>overall frequency of collocate</em>]
				</td>
			<td class="concordgeneral">    
				<select name="newSetting_coll_freqalone">
					<?php echo print_freqalone_form_options($User->coll_freqalone); ?>
				</select>
			</td>
		</tr>

		<tr>                               
			<td class="concordgeneral">
				Default range for calculating collocations
			</td>
			<td class="concordgeneral">   
				From
				<select name="newSetting_coll_from">
					<?php echo $optionsfrom; ?>
				</select>
				to
				<select name="newSetting_coll_to">
					<?php echo $optionsto; ?>
				</select>				
			</td>
		</tr>

		<tr>
			<th colspan="2" class="concordtable">Download options</th>
		</tr>
		
		<tr>
			<td class="concordgeneral">File format to use in text-only downloads</td>
			<td class="concordgeneral">
				<select name="newSetting_linefeed">
					<option value="au"<?php echo ($User->linefeed == 'au' ? ' selected="selected"' : '');?>>Automatically detect my computer</option>
					<option value="da"<?php echo ($User->linefeed == 'da' ? ' selected="selected"' : '');?>>Windows</option>
					<option value="a"<?php  echo ($User->linefeed == 'a'  ? ' selected="selected"' : '');?>>Unix / Linux (inc. Mac OS X)</option>
					<option value="d"<?php  echo ($User->linefeed == 'd'  ? ' selected="selected"' : '');?>>Macintosh (OS 9 and below)</option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th colspan="2" class="concordtable">Accessibility options</th>
		</tr>
		
		<tr>
			<td class="concordgeneral">
				Override corpus colour scheme with monochrome
				<br/>
				<em>(useful if the color schemes cause you vision difficulties)</em>
			</td>
			<td class="concordgeneral">
				<select name="newSetting_css_monochrome">
					<option value="1"<?php echo ($User->css_monochrome == '1' ? ' selected="selected"' : '');?>>Yes</option>
					<option value="0"<?php echo ($User->css_monochrome == '0' ? ' selected="selected"' : '');?>>No</option>
				</select>
			</td>
		</tr>
<!--
		<tr>
			<th colspan="2" class="concordtable">Other options</th>
		</tr>		
		<tr>
			<td class="concordgeneral">Real name</td>
			<td class="concordgeneral">
				<input name="newSetting_realname" type="text" width="64" value="<?php echo escape_html($User->realname); ?>"/>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">Email address (system admin may use this if s/he needs to contact you!)</td>
			<td class="concordgeneral">
				<input name="newSetting_email" type="text" width="64" value="<?php echo escape_html($User->email); ?>"/>
			</td>
		</tr>
-->
		<tr>
			<td class="concordgrey" align="right">
				<input type="submit" value="Update settings" />
			</td>
			<td class="concordgrey" align="left">
				<input type="reset" value="Clear changes" />
			</td>
		</tr>
		<input type="hidden" name="redirect" value="revisedUserSettings" />
		<input type="hidden" name="uT" value="y" />

	</form>
</table>

	<?php

}

function printscreen_usermacros()
{
	global $User;
	
	// TODO - prob better to have these actions in user_admin instead.
	
	/* add a macro? */
	if (!empty($_GET['macroNewName']) && !empty($_GET['macroNewBody']) )
		user_macro_create($User->username, $_GET['macroNewName'],$_GET['macroNewBody']); 
	
	/* delete a macro? */
	if (!empty($_GET['macroDelete']) && !empty($_GET['macroDeleteNArgs']))
		user_macro_delete($User->username, $_GET['macroDelete'], $_GET['macroDeleteNArgs']);
	// TODO use ID field instead
	
	?>
<table class="concordtable" width="100%">
	<tr>
		<th class="concordtable" colspan="3">User's CQP macros</th>
	</tr>
	
	<?php
	
	$result = do_mysql_query("select * from user_macros where user='{$User->username}'");
	if (mysql_num_rows($result) == 0)
	{
		?>
		
		<tr>
			<td colspan="3" align="center" class="concordgrey">
				&nbsp;<br/>
				You have not created any user macros.
				<br/>&nbsp;
			</td>
		</tr>
		
		<?php
	}
	else
	{
		?>
		
		<th class="concordtable">Macro</th>
		<th class="concordtable">Macro expansion</th>
		<th class="concordtable">Actions</th>
		
		<?php
		
		while (false !== ($r = mysql_fetch_object($result)))
		{
			echo '<tr>';
			
			echo "<td class=\"concordgeneral\">{$r->macro_name}({$r->macro_num_args})</td>";
			
			echo '<td class="concordgrey"><pre>'
				, $r->macro_body
				, '</pre></td>';
			
			echo '<form action="index.php" method="get"><td class="concordgeneral" align="center">'
				, '<input type="submit" value="Delete macro" /></td>'
				, '<input type="hidden" name="macroDelete" value="'.$r->macro_name.'" />'
				, '<input type="hidden" name="macroDeleteNArgs" value="'.$r->macro_num_args.'" />'
				, '<input type="hidden" name="thisQ" value="userSettings" />'
				, '<input type="hidden" name="uT" value="y" />'
				, '</form>';
			
			echo '</tr>';	
		}	
	}
	
	?>
	
</table>

<table class="concordtable" width="100%">
	<tr>
		<th colspan="2" class="concordtable">Create a new CQP macro</th>
	</tr>
	<form action="index.php" method="get">
		<tr>
			<td class="concordgeneral">Enter a name for the macro:</td>
			<td class="concordgeneral">
				<input type="text" name="macroNewName" />
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">Enter the body of the macro:</td>
			<td class="concordgeneral">
				<textarea rows="25" cols="80" name="macroNewBody"></textarea>
			</td>
		</tr>
		<tr>
			<td class="concordgrey">Click here to save your macro</br>(It will be available in all CQP queries)</td>
			<td class="concordgrey"><input type="submit" value="Create macro"/></td>
		</tr>
		
		<input type="hidden" name="macroUsername" value="<?php echo $Uxer->username;?>" />
		<input type="hidden" name="thisQ" value="userMacros" />
		<input type="hidden" name="uT" value="y" />
		
	</form>
</table>
	<?php

}


function printscreen_corpusaccess()
{
	global $User;
	
	$header_text_mapper = array(
		PRIVILEGE_TYPE_CORPUS_FULL       => "You have <em>full</em> access to:",
		PRIVILEGE_TYPE_CORPUS_NORMAL     => "You have <em>normal</em> access to:",
		PRIVILEGE_TYPE_CORPUS_RESTRICTED => "You have <em>restricted</em> access to:"
		);
	
	/* now, compile an array of corpora to create table cells for */
	$accessible_corpora = array(
		PRIVILEGE_TYPE_CORPUS_FULL       => array(),
		PRIVILEGE_TYPE_CORPUS_NORMAL     => array(),
		PRIVILEGE_TYPE_CORPUS_RESTRICTED => array()
		);
	foreach ($User->privileges as $p)
	{
		switch($p->type)
		{
		case PRIVILEGE_TYPE_CORPUS_FULL:
		case PRIVILEGE_TYPE_CORPUS_NORMAL:
		case PRIVILEGE_TYPE_CORPUS_RESTRICTED:
			foreach ($p->scope_object as $c)
				if ( ! in_array($c, $accessible_corpora[$p->type]) )
					$accessible_corpora[$p->type][] = $c;
			break;
		default:
			break;			
		}
	}
	/* remove from normal if in full */
	foreach($accessible_corpora[PRIVILEGE_TYPE_CORPUS_NORMAL] as $k=>$c)
		if (in_array($c, $accessible_corpora[PRIVILEGE_TYPE_CORPUS_FULL]))
			unset($accessible_corpora[PRIVILEGE_TYPE_CORPUS_NORMAL][$k]);
	/* remove from restricted if in full or normal */
	foreach($accessible_corpora[PRIVILEGE_TYPE_CORPUS_RESTRICTED] as $k=>$c)
		if (in_array($c, $accessible_corpora[PRIVILEGE_TYPE_CORPUS_FULL]) || in_array($c, $accessible_corpora[PRIVILEGE_TYPE_CORPUS_NORMAL]))
			unset($accessible_corpora[PRIVILEGE_TYPE_CORPUS_RESTRICTED][$k]);

	?>
	
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="3" class="concordtable">Corpus access permissions</th>
		</tr>
		<tr>
			<td colspan="3" class="concordgrey" align="center">
				&nbsp;<br/>
				You have permission to access the following corpora.
				<br/>&nbsp;
			</td>
		</tr>
		
		<?php
		
		/* in case of superuser, shortcut everything and return */
		if ($User->is_admin())
		{
			echo "\t\t<tr><td colspan=\"3\" class=\"concordgeneral\" align=\"center\">"
				, "&nbsp;<br/><b>You are a superuser. You have full access to everything.</b><br/>&nbsp;"
				, "</td></tr>\n\t</table>";
			return;
		}
		
		foreach(array(PRIVILEGE_TYPE_CORPUS_FULL, PRIVILEGE_TYPE_CORPUS_NORMAL, PRIVILEGE_TYPE_CORPUS_RESTRICTED) as $t)
		{
			if ( empty($accessible_corpora[$t] ))
				continue;
			
			?>
			<tr>
				<th colspan="3" class="concordtable"><?php echo $header_text_mapper[$t]; ?></th>
			</tr>
			<?php
			
			/* the following hunk o' code is a variant on what is found in mainhome */
			
			$i = 0;
			$celltype = 'concordgeneral';
			
			foreach($accessible_corpora[$t] as $c)
			{
				if ($i == 0)
					echo "\t\t<tr>";
				
				/* get corpus title */
				$c_info = get_corpus_info($c);
				$corpus_title_html = (empty($c_info->title) ? $c : escape_html($c_info->title));
				
				echo "
					<td class=\"$celltype\" width=\"33.3%\" align=\"center\">
						&nbsp;<br/>
						<a href=\"../{$c}/\">$corpus_title_html</a>
						<br/>&nbsp;
					</td>\n";
				//TODO: print more info on each corpus here? as a hover? acces statement maybe?
				
				$celltype = ($celltype=='concordgrey'?'concordgeneral':'concordgrey');
				
				if ($i == 2)
				{
					echo "\t\t</tr>\n";
					$i = 0;
				}
				else
					$i++;
			}
	
			if ($i == 1)
			{
				echo "\t\t\t<td class=\"$celltype\" width=\"33.3%\" align=\"center\">&nbsp;</td>\n";
				$i++;
				$celltype = ($celltype=='concordgrey'?'concordgeneral':'concordgrey');
			}
			if ($i == 2)
				echo "\t\t\t<td class=\"$celltype\" width=\"33.3%\" align=\"center\">&nbsp;</td>\n\t\t</tr>\n";
		}

		?>
		<tr>
			<td colspan="3" class="concordgrey">
				&nbsp;<br/>
				If you think that you should have permission for more corpora than are listed above, 
				you should contact the system administrator, explaining which corpora you wish to use,
				and on what grounds you believe you have permission to use them.
				<br/>&nbsp;
			</td>
		</tr>
	</table>
	<?php
}



function printscreen_userdetails()
{
	global $User;
	global $Config;
	
	/* initialise the iso 3166-1 array... */
	require('../lib/user-iso31661.inc.php');
	natsort($Config->iso31661);
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="3" class="concordtable">
				Account details
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				Username:
			</td>
			<td class="concordgeneral" colspan="2">
				<?php echo $User->username, "\n"; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Email address:
			</td>
			<td class="concordgeneral" colspan="2">
				<?php echo escape_html($User->email), "\n"; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" colspan="3">
				&nbsp;<br/>
				<b>Important note</b>:
				You cannot change either the username or the email address that this account is associated with.
				<br/>&nbsp;
			</td>
		</tr>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Your full name:
				</td>
				<td class="concordgeneral">
					<input type="text" name="updateValue" value="<?php echo escape_html($User->realname); ?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
				<input type="hidden" name="fieldToUpdate" value="realname" />
				<input type="hidden" name="redirect" value="updateUserAccountDetails" />
				<input type="hidden" name="uT" value="y" />
			</tr>
		</form>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Your affiliation (institution or company):
				</td>
				<td class="concordgeneral">
					<input type="text" name="updateValue" value="<?php echo escape_html($User->affiliation); ?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
				<input type="hidden" name="fieldToUpdate" value="affiliation" />
				<input type="hidden" name="redirect" value="updateUserAccountDetails" />
				<input type="hidden" name="uT" value="y" />
			</tr>
		</form>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Your location:
				</td>
				<td class="concordgeneral">
					<table class="basicbox" width="100%">
						<tr>
							<td class="basicbox">
								<?php echo escape_html($Config->iso31661[$User->country]); ?>
							</td>
							<td class="basicbox">
								<select name="updateValue">
									<option selected="selected">Select new location ...</option>
									<?php
									foreach ($Config->iso31661 as $k => $country)
										echo "\t\t\t\t\t\t<option value=\"$k\">", escape_html($country), "</option>\n";
									?>
								</select>
							</td>
						</tr>
					</table>
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
				<input type="hidden" name="fieldToUpdate" value="country" />
				<input type="hidden" name="redirect" value="updateUserAccountDetails" />
				<input type="hidden" name="uT" value="y" />
			</tr>
		</form>

	</table>
	<?php
}


function printscreen_changepassword()
{
	global $User;
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">
				Change your password
			</th>
		</tr>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Enter your <b>current</b> password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="oldPassword" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter your <b>new</b> password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="newPassword" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Retype the <b>new</b> password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="newPasswordCheck" />
				</td>
			</tr>
			<tr>
				<td class="concordgrey" colspan="2" align="center">
					&nbsp;<br/>
					Click below to change your password.
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
					<input type="submit" value="Submit this form to change your password" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="userForPasswordReset" value="<?php echo escape_html($User->username); ?>" />
			<input type="hidden" name="redirect" value="resetUserPassword" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	<?php
}

