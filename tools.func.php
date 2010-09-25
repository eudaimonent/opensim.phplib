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

 function  isNumeric($str, $nullok=false)
 function  isAlphabetNumeric($str, $nullok=false)
 function  isAlphabetNumericSpecial($str, $nullok=false)
 function  isGUID($uuid, $nullok=false)
 
 function  make_random_hash()
 function  make_random_guid()
 
 function  j2k_to_tga($file, $iscopy=true) 		need j2k_to_image (OpenJpeg)

 ****************************************************************/





function  isNumeric($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^[0-9\.]+$/', $str)) return false;

	return true;
}



function  isAlphabetNumeric($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^\w+$/', $str)) return false;
	return true;
}



function  isAlphabetNumericSpecial($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^[_a-zA-Z0-9 &@%#\-\.]+$/', $str)) return false;
	return true;
}



function  isGUID($uuid, $nullok=false)
{
	if ($uuid==null) return $nullok;
	if (!preg_match('/^[0-9A-Fa-f]{8,8}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{12,12}$/', $uuid)) return false;
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
 



//
// Convert Image from JPEG2000 to TGA
// 
function  j2k_to_tga($file, $iscopy=true)
{
	if (!file_exists($file)) return false;

	$com_totga = '';
	if (file_exists('/usr/local/bin/j2k_to_image')) 	 $com_totga = '/usr/local/bin/j2k_to_image';
	else if (file_exists('/usr/bin/j2k_to_image'))  	 $com_totga = '/usr/bin/j2k_to_image';
	else if (file_exists('/usr/X11R6/bin/j2k_to_image')) $com_totga = '/usr/X11R6/bin/j2k_to_image';
	else if (file_exists('/bin/j2k_to_image'))      	 $com_totga = '/bin/j2k_to_image';

	if ($com_totga=='') return false;


	if ($iscopy) $ret = copy  ($file, $file.'.j2k');
	else 		 $ret = rename($file, $file.'.j2k');
	if (!$ret) return false;

	exec("$com_totga -i $file.j2k -o $file.tga 1>/dev/null 2>&1");
	unlink($file.'.j2k');

	return true;
}

?>
