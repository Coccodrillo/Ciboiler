<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:  Ion Auth Lang - Slovenian
*
* Author: Rok Biderman
* 		  rok.biderman@interaktivnost.net
*
* Location: http://github.com/benedmunds/ion_auth/
*
* Created:  14.07.2012
*
* Description:  Slovenian language file for Ion Auth messages and errors
*
*/

// Account Creation
$lang['account_creation_successful'] 	  	 = 'Račun uspešno ustvarjen';
$lang['account_creation_unsuccessful'] 	 	 = 'Pri ustvarjanju računa je prišlo do napake';
$lang['account_creation_duplicate_email'] 	 = 'Email naslov neveljaven ali že v rabi';
$lang['account_creation_duplicate_username'] = 'Uporabniško ime neveljavno ali že v rabi';

// Password
$lang['password_change_successful'] 	 	 = 'Geslo uspešno spremenjeno';
$lang['password_change_unsuccessful'] 	  	 = 'Pri spreminjanju gesla je prišlo do napake';
$lang['forgot_password_successful'] 	 	 = 'Email sporočilo za ponastavitev gesla je bilo poslano';
$lang['forgot_password_unsuccessful'] 	 	 = 'Pri ponastavljanju gesla je prišlo do napake';

// Activation
$lang['activate_successful'] 		  	     = 'Račun aktiviran';
$lang['activate_unsuccessful'] 		 	     = 'Pri aktivaciji računa je prišlo do napake';
$lang['deactivate_successful'] 		  	     = 'Račun deaktiviran';
$lang['deactivate_unsuccessful'] 	  	     = 'Pri deaktivaciji računa je prišlo do napake';
$lang['activation_email_successful'] 	  	 = 'Aktivacijski email poslan';
$lang['activation_email_unsuccessful']   	 = 'Pri pošiljanju aktivacijskega emaila je prišlo do napake';

// Login / Logout
$lang['login_successful'] 		  	         = 'Prijava je bila uspešna';
$lang['login_unsuccessful'] 		  	     = 'Nepravilno uporabniško ime ali geslo';
$lang['login_unsuccessful_not_active'] 		 = 'Račun je neaktiven';
$lang['logout_successful'] 		 	         = 'Uspešna odjava';

// Account Changes
$lang['update_successful'] 		 	         = 'Podatki o uporabniku uspešno spremenjeni';
$lang['update_unsuccessful'] 		 	     = 'Pri posodabljanju uporabniških podatkov je prišlo do napake';
$lang['delete_successful'] 		 	         = 'Uporabnik izbrisan';
$lang['delete_unsuccessful'] 		 	     = 'Pri brisanju uporabnika je prišlo do napake';

// Email Subjects
$lang['email_forgotten_password_subject']    = 'Preverjanje pozabljenega gesla';
$lang['email_new_password_subject']          = 'Novo geslo';
$lang['email_activation_subject']            = 'Aktivacija računa';
