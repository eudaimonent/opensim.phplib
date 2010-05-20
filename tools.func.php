<?php
/****************************************************************
 * tools.func.php v1.0.0  
 *							by Fumi.Iseki (c) 2010 5/13
 *
 *							http://www.nsl.tuis.ac.jp/
 *
 ****************************************************************/


/****************************************************************
 * Function List
 *
 *
 * function  isNumeric($str)
 * function  isAlphabetNumeric($str)
 * function  isAlphabetNumericSpecial($str)
 * function  isGUID($uuid)
 *
 * function  make_random_hash()
 * function  make_random_guid()
 *
 ****************************************************************/





function  isNumeric($str)
{
	if ($str==null) return false;
	if (!preg_match("/^[0-9]+$/", $str)) return false;
	return true;
}



function  isAlphabetNumeric($str)
{
	if ($str==null) return false;
	if (!preg_match("/^\w+$/", $str)) return false;
	return true;
}



function  isAlphabetNumericSpecial($str)
{
	if ($str==null) return false;
	if (!preg_match("/^[_a-zA-Z0-9 @%#\-\.]+$/", $str)) return false;
	return true;
}



function  isGUID($uuid)
{
	if ($uuid==null) return false;
	if (!preg_match("/^[0-9A-Fa-f]{8,8}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{12,12}$/", $uuid)) return false;
	return true;
}



function  make_random_hash()
{
 	$ret = sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
 													  mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
	return $ret;
}



function  make_random_guid()
{
	$uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,   
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	return $uuid;
}
 

?>
