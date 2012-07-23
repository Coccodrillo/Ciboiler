<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*

Copyright (c) 2008 sophistry (contact at CodeIgniter.com forums)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

------------------------------------

An interface for the PHP IMAP functions
Grabs email messages from a POP3 or IMAP 
account so they can be put into a database 

This code's only link to a database is the nested
associative array built in the grab_email_as_array() 
function - it serves as a rudimentary 'interface'
but, is limited because it embeds the field names
directly in this class

If there are attachments, this class 
creates a directory for each email that
has an attachment and puts them all there

Known limitations:
tested on POP3 server only, should work on IMAP, but never been tested
only extracts the last HTML and PLAIN sub parts in a message
may be conflicts with filenames of attachments all in one dir

Class Created by: sophistry 
--inspired by CodeIgniter, but not beholden to it 
20079019

*/

class imap_pop {

// despite the names, these IMAP vars 
// can be pop3 server vars too
var $IMAP_server;
var $IMAP_login;
var $IMAP_pass;

var $IMAP_service_flags;
var $IMAP_mailbox;

var $IMAP_resource;
var $IMAP_state;
var $connected;

// /full/path/to/attachment/directory/ 
// (dir must have www chmod read/write permissions)
var $IMAP_attachment_dir;

// make this property so we can separate
// the parts parsing into its own method
var $parts_array = array();

// when extract_a_part() is called
// it fills these vars with the string
// found in the PLAIN and HTML parts
// for the current message being processed
// only keeps the last part, so it will only 
// keep the last of multiple HTML parts
var $PLAIN;
var $HTML;

var $msg_id = 0;
var $msg_count = 0;


// constructor function handles array items passed
// by the config file loader in CI, or they can be passed
// directly into the new() step
// this design allows the library to be autoloaded
// and used in multiple controllers
function imap_pop($init_array = NULL)
{
	// if no init parameter sent
	// nothing happens in constructor
	// user has to call connect_and_count()
	// and pass the parameters
	if ( !is_null($init_array) )
	{
		// handle the array items
		// pass to connect_and_count()
		$this->connect_and_count($init_array);
	}

}


// grab email from a POP3 account
function connect_and_count($init_array)
{	
	// these array items need to be the 
	// same names as the config items OR
	// the db table fields that store the account info
	$this->IMAP_server = $init_array['server'];
	$this->IMAP_login = $init_array['login'];
	$this->IMAP_pass = $init_array['pass'];
	
	$this->IMAP_service_flags = $init_array['service_flags'];
	$this->IMAP_mailbox = $init_array['mailbox'];
	// grab the resource returned by imap_open()
	// suppress warning with @ so we can handle it internally
	$imap_str = '{'. $this->IMAP_server . $this->IMAP_service_flags.'}'.$this->IMAP_mailbox;
	//p($imap_str);
	$this->IMAP_resource = @imap_open($imap_str, $this->IMAP_login, $this->IMAP_pass);
	
	if($this->IMAP_resource)
	{
		// handle the strange mailbox is empty error
		// so we can just get on with things
		// this clears all errors in the stack
		// but, at this point there shouldn't be more than one
		$err = imap_errors();
		if($err[0] == 'Mailbox is empty')
		{
			// keep the state
			$this->IMAP_state = 'Mailbox is empty';
		}
		
		// store the number of emails 
		// waiting at server into var
		$m = $this->count_messages();
		$this->IMAP_state = 'Connected. Message count: ' . $m;
		$this->connected = TRUE;
		return TRUE;
	}
	else 
	{
		$this->IMAP_state = 'Not connected';
		$this->connected = FALSE;
		return FALSE;
	}
}

/**
* Get the number of emails at server
* Calling this function updates msg_count var
*/
function count_messages()
{
	$this->msg_count = imap_num_msg($this->IMAP_resource);
	return $this->msg_count;
}

/**
* Get count of messages returned by latest
* call to the imap_num_msg() function that
* was stored in the property 
* ACCESSOR method
*/
function get_message_count()
{
	return $this->msg_count;
}

/**
* Get an array of emails waiting 
* returns empty array if no messages
* SLOW: all these functions process about 5 messages per second!
* But, there seems to be some kind of cache that
* speeds up subsequent mailbox requests
*/
function get_message_list_overview()
{
	$a = imap_fetch_overview($this->IMAP_resource,'1:*');
	return $a;
}

// this is fairly useless because imap_headers()
// returns a string rather than an array
// not faster than the other listers
// but it may be useful later
function get_message_list_headers()
{
	$a = imap_headers($this->IMAP_resource);
	return $a;
}

// loop over the messages by hand
// may be the best function to use
// to allow user feedback (i.e., progress bar)
function get_message_list_loop($start=0, $end=0)
{	
	$a = array();
	
	// start is the id
	// start it one down
	// because id immediately
	// increments in the next loop
	$id = ($start==0) ? 0 : $start-1;
	$end = ($end==0) ? $this->msg_count : $end;

	while ($id++ < $end)
	{
		$a[] = imap_headerinfo($this->IMAP_resource, $id);
		// it is not faster to use imap_fetchheader
		//$a[] = imap_fetchheader($this->IMAP_resource, $id);
	}
	return $a;
}

// set the directory where we will store
// attachments. check it is there and
// writeable by the webserver
function set_IMAP_attachment_dir($dir)
{
	if (is_dir($dir))
	{
		$this->IMAP_state = 'Attachment directory exists: ' . $dir;
		if (is_writeable($dir))
		{
			$this->IMAP_state = 'Attachment directory writeable: ' . $dir;
			$this->IMAP_attachment_dir = $dir;
		}
		else
		{
			$this->IMAP_state = 'Attachment directory NOT writeable: ' . $dir;
		}
	}
}

// sets the msg_id and then checks it
// returns FALSE if id is > msg_count
// otherwise TRUE
// should also set an error here
function set_msg_id($id)
{
	$this->msg_id = $id;
	// make sure it is less than message count
	// and greater than zero
	$id_ok = (bool)( ($this->msg_count >= $this->msg_id) && ($this->msg_id > 0) );
	if (!$id_ok) 
	{
		$this->IMAP_state = 'Not a valid message id: ' . $this->msg_id;
		//unset($this->msg_id);
	}
	return $id_ok;
}

// Normal POP3 does not mark messages for later deletion
// must delete them and expunge them in same connection
// TRUE on success, FALSE on failure
// BUT... Google's gmail service does mark as read and
// then does not serve them up to POP3 again even though 
// they are still in the INBOX. They are doing some extra 
// thing to the message to make it invisible once it has been
// picked up by ANY POP3 request that grabs the email data
// So, gmail does not seem to be affected by imap_delete() imap_expunge(),
// it only cares about its own settings with regard to how 
// to handle message storage after a POP3 connection
function delete_and_expunge($id_or_range)
{
	// make sure we've got a message there of that id
	// and it's not a bogus id like 0 or -1
	// also allow ranges to be sent to this function
	// format 1:5, so if a colon is sent, we assume
	// it is properly formatted - not the best idea
	// should check the full formatting with regexp '/[0-9]+:[0-9\*]/'
	if ($this->set_msg_id($id_or_range) || strpos($id_or_range,':') || strpos($id_or_range,',')) 
	{
		// should capture error here when the message doesn't exist
		imap_delete($this->IMAP_resource, $this->msg_id);
		imap_expunge($this->IMAP_resource);
		$this->IMAP_state = 'Deleted and Expunged message id_or_range: ' . $this->msg_id;
		return TRUE;
	}
	else
	{
		$this->IMAP_state = 'Could not delete and expunge message id_or_range: ' . $this->msg_id;
		return FALSE;
	}
}


// deal with most of the variations in the msg_id spec
// just handles 0, single value whole number, or colon range x:y
// other variations (not handled at the moment)
// are comma separated list of ids and 1:* (star)
function _prepare_msg_ids ($id_or_range=0)
{
	if (!$id_or_range)
	{
		// zero or EMPTY means get all emails in mailbox
		$id_start = 1;
		$id_end = $this->msg_count;
	}
	else
	{
		// just a number, assign both start and end
		$id_start = $id_end = $id_or_range;
	}
	
	// range value, explode, overwrite previous
	if (strpos($id_or_range,':'))
	{
		list($id_start, $id_end) = explode(':',$id_or_range);
	}

	return array($id_start,$id_end);
}



/**
* Get email by coordinating multiple imap functions
* imap_fetchstructure, imap_fetchbody, 
* imap_fetchheader, and/or imap_body
* (imap_fetchbody is called in sub-routine extract_a_part)
* gmail "removes" the message from the POP3 INBOX (if set to do so)
* when you call any body or structure type of imap_ function
* but does not "remove" it when you call header imap_ functions
*/
function grab_email_as_array($id = 0)
{
	//p($id);
	// set this to 1 to keep errors in imap parsing out of the php stack
	$cap_errs = 1;
	// we are going to return this 
	// array with all the email data
	// from one email message in it
	// hold the email addresses in arrays
	// for transport to the related db tables
	$email_arrays_array = array();
	// email elements that are not arrays - 
	// they are in the email table
	$email_strings_array = array();
	// an array to hold them both
	$main_email_array = array();
	// set the msg_id if one is sent in
	// if not valid, return empty array
	$bool = $this->set_msg_id($id);
	
	if (!$bool) return $email_array;
	
	// make sure we start fresh
	unset($this->PLAIN);
	unset($this->HTML);
	
	// get the header info first
	// NOTE: this function does not remove the message from gmail's INBOX
	$header_obj=imap_headerinfo($this->IMAP_resource, $this->msg_id);
	//p($header_obj);exit();
	
	// check for errors here to clear the 
	// error stack and prevent it from posting
	// errors about badly formatted emails
	// should probably store the errors with the email in the db
	if ($cap_errs) $err = imap_errors();
	//p($err);
	
	// fill the parts_array var with the parts
	// $structure is the map to the email message
	
	// NOTE: calling this function removes message from 
	// gmail's POP3 INBOX - not by deleting it, but making 
	// it effectively invisible (depending on gmail account's POP3 settings)
	$structure = imap_fetchstructure($this->IMAP_resource, $this->msg_id);
	//p($structure);
	//exit();
	// check for erros here to clear the 
	// error stack and prevent it from posting
	// errors about badly formatted emails
	// should probably store the errors with the email in the db
	if ($cap_errs) $err = imap_errors();
	
	// could pull out the raw email body here for storage/export potential
	//$text=imap_body($this->IMAP_resource,$this->msg_id);
	//p($text);//exit();
	// see if it is a multipart messsage
	// should handle bothe these cases in the extract_a_part function
	if (isset($structure->parts) && count($structure->parts))
	{
		// extract every part of the email into the array_parts var
		// this is a custom array to help unify what we need from the parts
		foreach ($structure->parts as $index => $part_def_obj)
		{
			// extract this part of email
			// if this is a PLAIN or HTML part it will
			// be written to the respective property
			$this->extract_a_part($part_def_obj,$index+1);
		}
	} 
	else 
	{ 
		// not a multipart message
		// get the body of message
		
		// NOTE: calling this function removes message from 
		// gmail's POP3 INBOX - not deleting it, but making 
		// it effectively invisible
		$text=imap_body($this->IMAP_resource,$this->msg_id);
		// decode if quoted-printable
		if ($structure->encoding==4) $text=quoted_printable_decode($text);
		
		// create a var for $this->PLAIN or $this->HTML
		$this->{$structure->subtype} = $text;
		$this->parts_array['not multipart']['text'] = array('type'=>$structure->subtype,'string'=>$text);
	}
	//p($this->parts_array);
	//exit();
	
	// start stuffing the single email array
	
	// first make sure the header_obj has the properties
	// we want to use later in this code so we don't have to
	// do a bunch of isset checks to avoid PHP warnings
	// from to message_id subject date udate Size
	// also gather the data for later fingerprinting
	// by stringing out the data points that won't change
	// these are items in the email that come here as arrays
	// rather than strings so they are handled differently
	// NOTE: the only time a BCC array will be set is if
	// you are looking at sent mail or the mailserver does something
	// unusual to show that there was a BCC but it was removed.
	// otherwise the BCC state will 
	// have to be deduced from the lack of address in the to or cc
	// fields (as well as any other fields like Resent-To: etc...)
	// and its presence in one of the Received: header strings
	// Resent-To: addresses have to be parsed out of the header manually
	$address_keys_we_need_to_be_set = explode(' ', 'from to cc bcc reply_to sender return_path');
	$other_keys_we_need_to_be_set = explode(' ', 'message_id subject date udate Size');
	$data_points_for_fingerprint = explode(' ', 'fromaddress toaddress subject date');
	$email_data_to_use_in_fingerprint = '';
	foreach ($other_keys_we_need_to_be_set as $prop)
	{
		$header_obj->$prop = isset($header_obj->$prop) ? $header_obj->$prop : '';
	}
	
	// turn each of the arrays of objects into arrays of arrays
	// with each address part getting encoding
	foreach ($address_keys_we_need_to_be_set as $key)
	{	
		// make sure the array item is set
		if (isset($header_obj->$key))
		{
			// it's there, 
			// variable is named for the key
			$$key = array();
			$arr = array();
			foreach ($header_obj->$key as $obj) 
			{
				// coerce it to an array
				$arr[] = (array)$obj;
				//p($key);
				//p($arr);
				// take the personal part and apply decoding
				if (isset($arr['personal']))
				{
					$arr['personal'] = $this->decode_mime_text($arr['personal']);
				}
				
				// push the array onto the array
				array_push( $$key, $arr );
			}
			$email_arrays_array[$key] = $arr;
			//p($$key);
		}
		
	}

	//p($to);p($from);p($header_obj);exit();
	
	foreach ($data_points_for_fingerprint as $prop)
	{
		// will use this subset of raw strings later in fingerprinting
		$email_data_to_use_in_fingerprint .= $header_obj->$prop;
	}
	//p($email_data_to_use_in_fingerprint);exit();
	
	// the email_strings_array keys correspond to database fields
	// this will make it easy to add the data to the email table
	
	// NOTE: this header function does not remove the message from gmail's INBOX
	$email_strings_array['header']     = imap_fetchheader($this->IMAP_resource, $this->msg_id);
	//p($email_strings_array['header']);exit();
	
	// use the extract_headers_to_array() fn 
	// to get any header that is not included
	// in the native imap_ function calls
	// commented here since it is not in use
	//$header_array = $this->extract_headers_to_array($email_strings_array['header']);
	
	$email_strings_array['message_id'] = $header_obj->message_id;
	$email_strings_array['subject']    = $this->decode_mime_text($header_obj->subject);
	$email_strings_array['date_string']= $header_obj->date;
	$email_strings_array['date_sent_stamp'] = date("Y-m-d H:i:s",$header_obj->udate);
	// this is actually the datestamp of 
	// when the message was put into this array
	// rather than "received" (which should better
	// be the datestamp for when the message
	// was accepted to the receiving SMTP server
	$email_strings_array['date_received_stamp'] = date("Y-m-d H:i:s");
	$email_strings_array['size']       = $header_obj->Size;
	$email_strings_array['text']       = (isset($this->PLAIN)) ? $this->PLAIN : '';
	$email_strings_array['html']       = (isset($this->HTML)) ? $this->HTML : '';
	
	// set a temporary array item to enable
	// message to be deleted at mailserver to 
	// sync with db, unset before db insert
	// not needed for google's gmail
	// since they make POP3 pulled emails invisible
	// once they are pulled one time
	$email_strings_array['temp_msg_id']= $this->msg_id;
	
	// generate a unique id so we 
	// can do reliable dupe detection
	// hashes the string representation
	// of some datapoints of this email
	// so, if we get the same email again
	// it will result in the same hash
	// we could do basic dupe detection using 
	// message_id, but it is not reliable
	// because it is not always there
	$email_strings_array['email_fingerprint_auto']     = $this->email_fingerprint( $email_data_to_use_in_fingerprint );
	//p($email_array);exit();
	
	$this->IMAP_state = 'Got email as array, message id: ' . $this->msg_id;
	
	// load them up as two items in the main array
	$main_email_array['strings'] = $email_strings_array;
	// arrays are separated because thay will be used to
	// populate related database tables in a normalized email storage schema
	$main_email_array['arrays'] = $email_arrays_array;
	
	return $main_email_array;

}


// standard place to do fingerprinting
// so we can use it to check email dupes
// send it a string with unchanging data
// that are pulled from the email header
// could "salt" this but md5'ing the var_export 
// is kind of like salt (except if everyone does it!)
function email_fingerprint($str)
{
	return md5(var_export($str,TRUE));
}

/**
* Parse e-mail structure into array var
* this will handle nested parts properly
* it will recurse and use the initial part number
* concatenating it with nested parts using dot .
* Useful information copied from http://php.net 

Table 142.  Returned Objects for imap_fetchstructure()

type	Primary body type
encoding	Body transfer encoding
ifsubtype	TRUE if there is a subtype string
subtype	MIME subtype
ifdescription	TRUE if there is a description string
description	Content description string
ifid	TRUE if there is an identification string
id	Identification string
lines	Number of lines
bytes	Number of bytes
ifdisposition	TRUE if there is a disposition string
disposition	Disposition string
ifdparameters	TRUE if the dparameters array exists
dparameters	An array of objects where each object has an "attribute" and a "value" property corresponding to the parameters on the Content-disposition MIMEheader.
ifparameters	TRUE if the parameters array exists
parameters	An array of objects where each object has an "attribute" and a "value" property.
parts	An array of objects identical in structure to the top-level object, each of which corresponds to a MIME body part.

Table 143. Primary body type
0	text
1	multipart
2	message
3	application
4	audio
5	image
6	video
7	model
8	other
9	unknown/unknown

Table 144. Transfer encodings
0	7BIT
1	8BIT
2	BINARY
3	BASE64
4	QUOTED-PRINTABLE
5	OTHER
*/

function extract_a_part($part_def_obj, $part_number)
{

	// get just one part as a string
	$part_string=imap_fetchbody($this->IMAP_resource, $this->msg_id, $part_number);
	
	//this works!  Now we need to do something with it.
	
	//p($part_string);
	// DECODE the part
	// if base64
	if ($part_def_obj->encoding==3) $part_string=base64_decode($part_string);
	// if quoted printable
	if ($part_def_obj->encoding==4) $part_string=quoted_printable_decode($part_string);
	// If binary or 8bit - we don't need to decode
	
	$sub_type = strtoupper($part_def_obj->subtype);
	// attachments, multipart types are 1-9
	
	//p($part_def_obj);
	if ($part_def_obj->type)
	{
		// determine body type (more to do here)
		switch($part_def_obj->type) 
		{
			case '5': //  image, should put proper name here for the image
			//$this->parts_array[$part_number]['image'] = array('filename'=>'IMAGE', 'string'=>$part_string, 'part_no'=>$part_number);
			break;
		}
		
		// get an attachment, set filename to dparameter value
		$filename='';
		if ($part_def_obj->ifdparameters && count($part_def_obj->dparameters))
		{
			foreach ($part_def_obj->dparameters as $dp)
			{
				if ((strtoupper($dp->attribute)=='NAME') || (strtoupper($dp->attribute)=='FILENAME')) $filename=$dp->value;
			}
		}
		// if no filename yet, set filename to parameter value
		if ($filename=='')
		{
			if ($part_def_obj->ifparameters && count($part_def_obj->parameters))
			{
				foreach ($part_def_obj->parameters as $p)
				{
					if ((strtoupper($p->attribute)=='NAME') || (strtoupper($p->attribute)=='FILENAME')) $filename=$p->value;
				}
			}
		}
		
		// we've got to have a filename by now!
		if ($filename!='' )
		{
			$this->parts_array[$part_number]['attachment'] = 
			array('filename'=>$filename,
					'string'=>$part_string, 
					'encoding'=>$part_def_obj->encoding, 
					'part_no'=>$part_number,
					'type'=>$part_def_obj->type,
					'subtype'=>$sub_type);
		}
		
		// now write the attachments to the disk
		
		// Get store dir, call this every message based on
		// the email data so it can hold all parts for a single email
		//$dir = $this->dir_name();
		$a_f = $this->decode_mime_text($filename);
		// replace crap with underscore, there is a CI function to do this
		$a_f = preg_replace('/[^a-z0-9_\-\.]/i', '_', $a_f);
		//$this->save_files($dir.$a_f, $part_string);
		
	}
	//Additional elseif clause added to detect text file attachments.
	elseif(isset($part_def_obj->disposition) and ($part_def_obj->disposition == 'ATTACHMENT')) {

		$filename = $part_def_obj->dparameters;
		$filename = $filename[0]->value;

		$this->parts_array[$part_number]['attachment'] = 
		array('filename'=>$filename,
				'string'=>$part_string, 
				'encoding'=>$part_def_obj->encoding, 
				'part_no'=>$part_number,
				'type'=>$part_def_obj->type,
				'subtype'=>$sub_type);

	}
	// Text or HTML email, type is 0
	else
	{	
		// creates an instance var for $this->HTML or $this->PLAIN
		// NOTE: only works for the last part or sub-part extracted
		$this->$sub_type = $part_string;
		$this->parts_array[$part_number]['text'] = array('type'=>$sub_type,'string'=>$part_string);
	}
	
	// if there are subparts call this function recursively
	if (isset($part_def_obj->parts) && count($part_def_obj->parts))
	{
		
		foreach ($part_def_obj->parts as $index => $sub_part_def_obj)
		{
			$this->extract_a_part($sub_part_def_obj, ($part_number.'.'.($index+1)));           
		}
	}
	
	return TRUE;
}


// get mime meta data
// this needs some attention
function decode_mime_text($str)
{
	
	$txt = '';
	$str = htmlspecialchars(chop($str));
	
	$elements = imap_mime_header_decode($str);
	if(is_array($elements))
	{
		for ($i=0; $i<count($elements); $i++) 
		{
			$charset = $elements[$i]->charset;
			$txt .= $elements[$i]->text;
		}
	} 
	else 
	{
		$txt = $str;
	}
	
	if($txt == '')
	{
		$txt = 'NO DATA - value missing';
	}
	
	return $txt;
}


/**
* Helper function
* Extract an array listing from the header
* This code makes sure this is done in a way that we get 
* all the possible headers (multi-line, domain keys, 
* repeated headers , etc...) tucked away nicely into 
* a nested array
*
* To Do: 20070904
* These headers (resent) have to be plucked out
* as they are not supported in the imap_headerinfo() function
* used in the grab_email_as_array() function above
*
* resent-from     =       "Resent-From:" mailbox-list CRLF
* resent-sender   =       "Resent-Sender:" mailbox CRLF
* resent-to       =       "Resent-To:" address-list CRLF
* resent-cc       =       "Resent-Cc:" address-list CRLF
* resent-bcc      =       "Resent-Bcc:" (address-list / [CFWS]) CRLF
*/ 
function extract_headers_to_array($header)
{
	//p($header);
	$header_array = explode("\n", rtrim($header));
	// drop off any empty, null or FALSE values
	$header_array=array_filter($header_array);
	//p($header_array);
	
	$new_header_array = array();
	foreach ($header_array as $key => $line)
	{
		//p($new_header_array);
		// check if this line starts with a header name
		// if it does, build the new header item
		// if it doesn't, build the string out
		if (preg_match('/^([^:\s]+):\s(.+)/',$line,$m))
		{
			//p($m[1]);
			//p($m[2]);
			$current_header = $m[1];
			$current_data = $m[2];
			// if there is no header by this name yet
			// set the data, otherwise, append it as array item
			if (!isset($new_header_array[$current_header])) 
			{
				// this is the normal branch, new header, one line of data
				$new_header_array[$current_header] = $current_data;
			}
			else
			{
				// if it is not an array, it is a string and we need
				// to convert the existing data to an array, and add the new
				if (!is_array($new_header_array[$current_header]))
				{
					// this runs when a header name is repeated (like Received often is)
					// runs the 1st time it is repeated (second occurance of the header)
					// converts the existing string and the incoming string to a 2-item sub-array
					$new_header_array[$current_header] = array($new_header_array[$current_header],$current_data);	
				}
				else
				// if it is already an array then append an array item
				{
					// this runs when a header name is repeated (like Received often is)
					// runs 3rd and subsequent times
					$new_header_array[$current_header][] = $current_data;
				}
			}
		}
		else 
		{
			// if it is already an array then append 
			// the string to the last sub-array item
			// because we assume the lines with no header names
			// are part of the most recently added sub-array item
			if (is_array($new_header_array[$current_header]))
			{
				// this runs if there has already been a header of the same header name
				$new_header_array[$current_header][count($new_header_array[$current_header])-1] .= $line;
			}
			else
			// if it is not an array, it is still just a string and we need
			// to build the string out
			{
				// this runs if the line is part of the first header encountered
				// but is part of a long multiline string (like Received header)
				$new_header_array[$current_header] .= $line;
			}
		}
	}
	
	return $new_header_array;
}



/**
* Wrapper to close the IMAP connection
* Returns TRUE if closed with no errors
*/ 
function close()
{
	if (is_resource($this->IMAP_resource))
	{
		$closed = imap_close($this->IMAP_resource);
		$this->IMAP_state = 'Connection closed.';
	}
	else
	{
		$closed = FALSE;
		$this->IMAP_state = 'Connection could not be closed - no resource.';
	}
	
	return $closed;
}

// This is a pretty lame function...
// if it doesn't exist already
// create a writeable directory 
// named for current month and year
// where we can store attachments
function make_dir($potential_name='') 
{
	//$dir_n = date('Y') . "_" . date('m');
	$dir_n = $potential_name;
	
	if (!is_dir($this->IMAP_attachment_dir . $dir_n)) 
		  mkdir($this->IMAP_attachment_dir . $dir_n, 0777);
		
	return $dir_n . '/';
}

 /**
 * Save messages on local disc, potential 
 * name and file lock collision here
 */ 
	function save_files($filename, $part)
{
	$fp=fopen($this->IMAP_attachment_dir.$filename,"w+");
	fwrite($fp,$part);
	fclose($fp);
	chown($this->IMAP_attachment_dir.$filename, 'www');
}

//-----------------------------------------------
// wrapper function to loop over the emails and get them all
// in a nice tidy, decoded and converted format
// accepts no param, one number (msgid), or range specified with x:y
function grab_emails_as_nested_array($id_or_range = 0)
{
	$a = array();
	
	// deals with all the variations in msg_id spec
	list($id_start, $id_end) = $this->_prepare_msg_ids($id_or_range);
	
	for ($id=$id_start; $id <= $id_end; $id++)
	{
		$a[] = $this->grab_email_as_array($id);
	}
	return $a;

}

//-----------------------------------------------
// This is the same function wrapper as the nested array fn above
// but, this one writes attachments to files too
// loop over the emails and get them all
// in a nice tidy, decoded and converted format
// also, write the attachments to files in the attachment folder
// accepts no param, one number (msgid), or range specified with x:y
function grab_emails_as_nested_array_and_store($id_or_range = 0)
{
	$a = array();
	
	// deals with all the variations in msg_id spec
	list($id_start, $id_end) = $this->_prepare_msg_ids($id_or_range);
	
	for ($id=$id_start; $id <= $id_end; $id++)
	{
		$a[] = $this->grab_email_as_array_and_store($id);
	}
	return $a;

}

//------------------------------------------------
// grab the email and save attachments to files
// this is just like grab_email_as_array() but,
// attachment files are written to disk
// this is the function to use when you are
// transferring to a db and you want the files
// to be written to disk at the same time all the
// email data is transferred and deleted from the mailserver
//
function grab_email_as_array_and_store($id = 0)
{
	// make sure we've got a message there of that id
	// and it's not a bogus id like 0 or -1
	if (!$this->set_msg_id($id)) return FALSE;
	
	// Get message header as array of pertinent values
	$email_array = $this->grab_email_as_array($this->msg_id);
	
	// Get store dir, call this every message based on
	// the email data so it can hold all parts for a single email
	// make a new dir based on the fingerprint
	$dir = $this->make_dir($email_array['strings']['email_fingerprint_auto']);
	
	// now write files into the new directory 
	// named with the fingerprint hash

	// I think this loop may be subject to missing some stuff
	// loop through the parts extracted in the grab_email_as_array() method
	// and either save them to disk (attachments) or write to db (HTML or text)
	// define the pattern that is going to make a nice filename
	// there is a CI function that does this
	$pattern_for_filename = '/[^a-z0-9_\-\.]/i';
	foreach ($this->parts_array as $part)
	{
		
		
		//what does this part look like?
		if (isset($part['text']['type']) AND ($part['text']['type'] == 'HTML' OR $part['text']['type'] == 'PLAIN'))
		{
			// handle PLAIN and HTML types elsewhere

		}
		elseif (isset($part['attachment']) AND $part['attachment'])
		{
			// Save file attachments to disk
			foreach (array($part['attachment']) as $attach)
			{
				$a_f = $this->decode_mime_text($attach['filename']);
				// replace crap with underscore, there is a CI function to do this
				$a_f = preg_replace($pattern_for_filename, '_', $a_f);
				$filename = $dir.$a_f;
				$this->save_files($filename, $attach['string']);
			}
		
		}
		elseif (isset($part['image']) AND $part['image'])
		{
			// Save image attachments to disk
			foreach ($part['image'] as $image)
			{
				$i_f = $this->decode_mime_text($image['filename']);
				// replace crap with underscore, there is a CI function to do this
				$i_f = preg_replace($pattern_for_filename, '_', $i_f);
				$filename = $dir.$i_f;
				$this->save_files($filename, $image['string']);
			}
		}
	}
	
	if($email_array != '')
	{
		unset($this->parts_array);
	}

	// return so the data can be used for a db insert
	return $email_array;
}


}

?>