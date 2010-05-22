<?php
/*********************************************************************************
 * opensim.func.php v1.0.0 for OpenSim 	by Fumi.Iseki  2010 5/13
 *
 * 			Copyright (c) 2009, 2010   http://www.nsl.tuis.ac.jp/
 *
 *			supported versions of OpenSim are 0.6.7, 0.6.8, 0.6.9 and 0.7Dev
 *			tools.func.php is needed
 *			opensim.mysql.php is needed
 *
 *********************************************************************************/


/*********************************************************************************
 Function List

 function  opensim_get_db_version(&$db=null)
 function  opensim_check_db(&$db=null)

 function  opensim_get_avatar_num(&$db=null)
 function  opensim_get_avatar_name($uuid, &$db=null)
 function  opensim_get_avatar_info($uuid, &$db=null)
 function  opensim_get_avatar_infos($condition="", &$db=null)
 function  opensim_get_avatar_profiles($condition="", &$db=null)
 function  opensim_get_avatar_online($uuid, &$db=null)
 function  opensim_create_avatar($UUID, $firstname, $lastname, $passwd, $homeregion, &$db=null)
 function  opensim_delete_avatar($uuid, &$db=null)

 function  opensim_get_region_num(&$db=null)
 function  opensim_get_region_name($region, &$db=null)
 function  opensim_get_region_names($condition="", &$db=null)
 function  opensim_get_region_info($region, &$db=null)
 function  opensim_get_region_infos($condition="", &$db=null)
 function  opensim_get_region_name_by_id($id, &$db=null)

 function  opensim_get_region_owner($region, &$db=null)
 function  opensim_set_region_owner($region, $woner_uuid, &$db=null)
 function  opensim_create_inventory_folders($uuid, &$db=null)
 function  opensim_set_home_region($uuid, $region, &$db=null)

 function  opensim_get_password($uuid, $tbl="", &$db=null)
 function  opensim_set_password($uuid, $passwdhash, $passwdsalt="", $tbl="", &$db=null)
 function  opensim_supply_passwordSalt(&$db=null)
 function  opensim_succession_presence(&$db=null)

 function  opensim_succession_data($region_nmae, &$db=null)

 function  opensim_get_voice_mode($region, &$db=null)
 function  opensim_set_voice_mode($region, $mode, &$db=null)

 *********************************************************************************/




/////////////////////////////////////////////////////////////////////////////////////
//
// Load Function
//

require_once(CMS_MODULE_PATH."/include/tools.func.php");
require_once(CMS_MODULE_PATH."/include/opensim.mysql.php");





/////////////////////////////////////////////////////////////////////////////////////
//
// for DB
//

function  opensim_get_db_version(&$db=null)
{
	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$ver = "0.6";
	if ($db->exist_table("UserAccounts")) $ver = "0.7";
	if ($db->Errno!=0) $ver = "0.0";

	if ($flg) $db->close();

	return $ver;
}



function  opensim_check_db(&$db=null)
{
	$ret['grid_status']      = false;
	$ret['now_online']       = 0;
	$ret['lastmonth_online'] = 0;
	$ret['user_count']       = 0;
	$ret['region_count']     = 0;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$db->query("SELECT COUNT(*) FROM regions");
	if ($db->Errno==0) {
		list($ret['region_count']) = $db->next_record();

		if ($db->exist_table("GridUser")) {				// 0.7Dev2
			$db->query("SELECT COUNT(*) FROM UserAccounts");
			list($ret['user_count']) = $db->next_record();
			$db->query("SELECT COUNT(*) FROM GridUser WHERE Online='true' and Login>(unix_timestamp(from_unixtime(unix_timestamp(now())-86400)))");
			list($ret['now_online']) = $db->next_record();
			$db->query("SELECT COUNT(*) FROM GridUser WHERE Login>unix_timestamp(from_unixtime(unix_timestamp(now())-2419200))");
			list($ret['lastmonth_online']) = $db->next_record();
		}
		else if ($db->exist_table("Presence")) {		// 0.7Dev1
			$db->query("SELECT COUNT(*) FROM UserAccounts");
			list($ret['user_count']) = $db->next_record();
			$db->query("SELECT COUNT(*) FROM Presence WHERE Online='true' and Login>(unix_timestamp(from_unixtime(unix_timestamp(now())-86400)))");
			list($ret['now_online']) = $db->next_record();
			$db->query("SELECT COUNT(*) FROM Presence WHERE Login>unix_timestamp(from_unixtime(unix_timestamp(now())-2419200))");
			list($ret['lastmonth_online']) = $db->next_record();
		}
		else {  // 0.6.x
			$db->query("SELECT COUNT(*) FROM users");
			list($ret['user_count']) = $db->next_record();
			$db->query("SELECT COUNT(*) FROM agents WHERE agentOnline=1 and logintime>(unix_timestamp(from_unixtime(unix_timestamp(now())-86400)))");
			list($ret['now_online']) = $db->next_record();
			$db->query("SELECT COUNT(*) FROM agents WHERE logintime>unix_timestamp(from_unixtime(unix_timestamp(now())-2419200))");
			list($ret['lastmonth_online']) = $db->next_record();
		}

		$ret['grid_status'] = true;
	}

	if ($flg) $db->close();

	return $ret;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Avatar
//

function  opensim_get_avatar_num(&$db=null)
{
	$num = 0;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	if ($db->exist_table("UserAccounts")) {
		$db->query("SELECT COUNT(*) FROM UserAccounts");
	}
	else {
		$db->query("SELECT COUNT(*) FROM users");
	}

	list($num) = $db->next_record();
	if ($flg) $db->close();

	return $num;
}



function  opensim_get_avatar_name($uuid, &$db=null)
{
	if (!isGUID($uuid)) return null;

	$firstname = null;
	$lastname  = null;
	$fullname  = null;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}
	
	if ($db->exist_table("UserAccounts")) {
		$db->query("SELECT FirstName,LastName FROM UserAccounts WHERE PrincipalID='$uuid'");
	}
	else {
		$db->query("SELECT username,lastname FROM users WHERE UUID='$uuid'");
	}

	list($firstname, $lastname) = $db->next_record();
	if ($flg) $db->close();

	$fullname = $firstname." ".$lastname;
	if ($fullname==" ") $fullname = null;

	$name['firstname'] = $firstname;
	$name['lastname']  = $lastname;
	$name['fullname']  = $fullname;

	return $name;
}



function  opensim_get_avatar_info($uuid, &$db=null)
{
	if (!isGUID($uuid)) return null;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}
	$online = false;
	$profileTXT = "";

	if ($db->exist_table("GridUser")) {
		$db->query("SELECT PrincipalID,FirstName,LastName,HomeRegionID,Created,Login FROM UserAccounts".
						" LEFT JOIN GridUser ON PrincipalID=UserID AND Logout!='0' WHERE PrincipalID='$uuid'");
		list($UUID, $firstname, $lastname, $regionUUID, $created, $lastlogin) = $db->next_record();
		$db->query("SELECT regionName,serverIP,serverHttpPort,serverURI FROM regions WHERE uuid='$regionUUID'");
		list($regionName, $serverIP, $serverHttpPort, $serverURI) = $db->next_record();
		$db->query("SELECT Online FROM GridUser WHERE UserID='$UUID'");
		list($agentOnline) = $db->next_record();
		if ($agentOnline=="True") $online = true;
	}
	else if ($db->exist_table("Presence")) {
		$db->query("SELECT PrincipalID,FirstName,LastName,HomeRegionID,Created,Login FROM UserAccounts".
						" LEFT JOIN Presence ON PrincipalID=UserID AND Logout!='0' WHERE PrincipalID='$uuid'");
		list($UUID, $firstname, $lastname, $regionUUID, $created, $lastlogin) = $db->next_record();
		$db->query("SELECT regionName,serverIP,serverHttpPort,serverURI FROM regions WHERE uuid='$regionUUID'");
		list($regionName, $serverIP, $serverHttpPort, $serverURI) = $db->next_record();
		$db->query("SELECT Online FROM Presence WHERE UserID='$UUID'");
		list($agentOnline) = $db->next_record();
		if ($agentOnline=="true") $online = true;
	}
	else {
		$db->query("SELECT UUID,username,lastname,homeRegion,created,lastLogin,profileAboutText FROM users WHERE uuid='$uuid'");
		list($UUID, $firstname, $lastname, $rgnHandle, $created, $lastlogin, $profileTXT ) = $db->next_record();
		$db->query("SELECT uuid,regionName,serverIP,serverHttpPort,serverURI FROM regions WHERE regionHandle='$rgnHandle'");
		list($regionUUID, $regionName, $serverIP, $serverHttpPort, $serverURI) = $db->next_record();
		$db->query("SELECT agentOnline FROM agents WHERE UUID='$UUID'");
		list($agentOnline) = $db->next_record();
		if ($agentOnline==1) $online = true;
	}
	if ($flg) $db->close();


	$fullname = $firstname." ".$lastname;
	if ($fullname==" ") $fullname = null;

	$avinfo['UUID'] 		  = $UUID;
	$avinfo['firstname'] 	  = $firstname;
	$avinfo['lastname'] 	  = $lastname;
	$avinfo['fullname']   	  = $fullname;
	$avinfo['created'] 		  = $created;
	$avinfo['lastlogin'] 	  = $lastlogin;
	$avinfo['online'] 	  	  = $online;
	$avinfo['regionUUID'] 	  = $regionUUID;
	$avinfo['regionName'] 	  = $regionName;
	$avinfo['serverIP'] 	  = $serverIP;
	$avinfo['serverHttpPort'] = $serverHttpPort;
	$avinfo['serverURI'] 	  = $serverURI;
	$avinfo['agentOnline']	  = $agentOnline;
	$avinfo['profileTXT']	  = $profileTXT;

	return $avinfo;
}



//
// Attention: When call this function, please check $condition for prevention of SQL Injection.
//
function  opensim_get_avatar_infos($condition="", &$db=null)
{
	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}
	$avinfos = array();
	
	if ($db->exist_table("GridUser")) {
		$db->query("SELECT PrincipalID,FirstName,LastName,Created,Login,homeRegionID FROM UserAccounts ".
							"LEFT JOIN GridUser ON PrincipalID=UserID AND Logout!='0' ".$condition);
	}
	else if ($db->exist_table("Presence")) {
		$db->query("SELECT PrincipalID,FirstName,LastName,Created,Login,homeRegionID FROM UserAccounts ".
							"LEFT JOIN Presence ON PrincipalID=UserID AND Logout!='0' ".$condition);
	}
	else {
		$db->query("SELECT UUID,username,lastname,created,lastLogin,homeRegion FROM users ".$condition);
	}

	if ($db->Errno==0) {
		while (list($UUID,$firstname,$lastname,$created,$lastlogin,$hmregion) = $db->next_record()) {
			$avinfos[$UUID]['UUID']	     = $UUID;
			$avinfos[$UUID]['firstname'] = $firstname;
			$avinfos[$UUID]['lastname']  = $lastname;
			$avinfos[$UUID]['created']   = $created;
			$avinfos[$UUID]['lastlogin'] = $lastlogin;
			$avinfos[$UUID]['hmregion']  = $hmregion;
		}
	}			  
	if ($flg) $db->close();

	return $avinfos;
}



//
// Attention: When call this function, please check $condition for prevention of SQL Injection.
//
function  opensim_get_avatar_profiles($condition="", &$db=null)
{
	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}
	$profs = array();

	if ($db->exist_table("users")) {
		$db->query("SELECT UUID,profileCanDoMask,profileWantDoMask,profileAboutText,".
						"profileFirstText,profileImage,profileFirstImage,partner,email FROM users ".$condition);
		if ($db->Errno==0) {
			while (list($UUID,$skilmask,$wantmask,$abouttext,$firsttext,$image,$firstimage,$partnar,$email) = $db->next_record()) {
				$profs[$UUID]['UUID'] 		= $UUID;
				$profs[$UUID]['SkillsMask'] = $skilmask;
				$profs[$UUID]['WantToMask'] = $wantmask;
				$profs[$UUID]['AboutText']  = $abouttext;
				$profs[$UUID]['FirstAboutText'] = $firsttext;
				$profs[$UUID]['Image'] 	   	= $image;
				$profs[$UUID]['FirstImage'] = $firstimage;
				$profs[$UUID]['Partnar']    = $partnar;
				$profs[$UUID]['Email'] 	   	= $email;
			}
		}
	}
	if ($flg) $db->close();

	return $profs;
}



function  opensim_get_avatar_online($uuid, &$db=null)
{
	if (!isGUID($uuid)) return null;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}
	$online = false;
	$region = "00000000-0000-0000-0000-000000000000";

	if ($db->exist_table("GridUser")) {
		$db->query("SELECT Online,LastRegionID FROM GridUser WHERE UserID='$uuid'");
		if ($db->Errno==0) {
			list($onln, $region) = $db->next_record();
			if ($onln=="True") $online = true;
		}
	}
	else if ($db->exist_table("Presence")) {
		$db->query("SELECT Online,RegionID FROM Presence WHERE UserID='$uuid' AND Logout='0'");
		if ($db->Errno==0) {
			list($onln, $region) = $db->next_record();
			if ($onln=="true") $online = true;
		}
	}
	else {            
		$db->query("SELECT agentOnline,currentRegion FROM agents WHERE UUID='$uuid' AND logoutTime='0'");
		if ($db->Errno==0) {
			list($onln, $region) = $db->next_record();
			if ($onln==1) $online = true;
		}
	}
	if ($flg) $db->close();

	$ret['online'] = $online;
	$ret['region'] = $region;
	return $ret;
}                 



function  opensim_create_avatar($UUID, $firstname, $lastname, $passwd, $homeregion, &$db=null)
{
	if (!isGUID($UUID)) return false;
	if (!isAlphabetNumericSpecial($firstname))  return false;
	if (!isAlphabetNumericSpecial($lastname))   return false;
	if (!isAlphabetNumericSpecial($passwd))     return false;
	if (!isAlphabetNumericSpecial($homeregion)) return false;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$nulluuid   = "00000000-0000-0000-0000-000000000000";
	$passwdsalt = make_random_hash();
	$passwdhash = md5(md5($passwd).":".$passwdsalt);

	$db->query("SELECT uuid,regionHandle FROM regions WHERE regionName='$homeregion'");
	$errno = $db->Errno;
	if ($errno==0) {
		list($regionID,$regionHandle) = $db->next_record();

		// for 0.7
		if ($db->exist_table("UserAccounts")) {
			$serviceURLs = "HomeURI= GatekeeperURI= InventoryServerURI= AssetServerURI=";
			$db->query("INSERT INTO UserAccounts (PrincipalID,ScopeID,FirstName,LastName,Email,ServiceURLs,Created,UserLevel,UserFlags,UserTitle) ".
								  "VALUES ('$UUID','$nulluuid','$firstname','$lastname','','$serviceURLs','".time()."','0','0','')");
			$errno = $db->Errno;
			if ($errno==0) {

				if ($db->exist_table("GridUser")) {
					$db->query("INSERT INTO GridUser (UserID,HomeRegionID,HomePosition,HomeLookAt,".
													 "LastRegionID,LastPosition,LastLookAt,Online,Login,Logout) ".
									"VALUES ('$UUID','$regionID','<128,128,0>','<0,0,0>',".
											"'$regionID','<128,128,0>','<0,0,0>','false','0','0')");
				}
				if ($db->exist_table("Presence")) {
					$ssid = make_random_guid();
					$csid = make_random_guid();
					$db->query("INSERT INTO Presence (UserID,RegionID,SessionID,SecureSessionID,Online,Login,Logout,".
												 "Position,LookAt,HomeRegionID,HomePosition,HomeLookAt) ".
							 		"VALUES ('$UUID','$regionID','$ssid','$csid','false','0','0',".
										   "'<128,128,0>','<0,0,0>','$regionID','<128,128,0>','<0,0,0>')");
				}
				$errno = $db->Errno;
			}
			if ($errno==0) {
				$db->query("INSERT INTO auth (UUID,passwordHash,passwordSalt,webLoginKey,accountType) ".
								  "VALUES ('$UUID','$passwdhash','$passwdsalt','$nulluuid','UserAccount')");
				$errno = $db->Errno;
			}
			if ($errno==0) {
				$errno = opensim_create_inventory_folders($UUID, $db);
			}

			if ($errno!=0) {
				$db->query("DELETE FROM UserAccounts WHERE PrincipalID='$UUID'");
				$db->query("DELETE FROM auth         WHERE UUID='$UUID'");
				$db->query("DELETE FROM inventoryfolders WHERE agentID='$UUID'");
				if ($db->exist_table("Presence")) $db->query("DELETE FROM Presence WHERE UserID='$UUID'");
				if ($db->exist_table("GridUser")) $db->query("DELETE FROM GridUser WHERE UserID='$UUID'");
			}
		}

		// for 0.6
		if ($db->exist_table("users") and $errno==0) {
			$db->query("INSERT INTO users (UUID,username,lastname,passwordHash,passwordSalt,homeRegion,".
										  "homeLocationX,homeLocationY,homeLocationZ,homeLookAtX,homeLookAtY,homeLookAtZ,".
										  "created,lastLogin,userInventoryURI,userAssetURI,profileCanDoMask,profileWantDoMask,".
										  "profileAboutText,profileFirstText,profileImage,profileFirstImage,homeRegionID) ".
						"VALUES ('$UUID','$firstname','$lastname','$passwdhash','$passwdsalt','$regionHandle',".
								"'128','128','128','100','100','100',".
								"'".time()."','0','','','0','0','','','$nulluuid','$nulluuid','$regionID')");

			if ($db->Errno!=0) {
				$db->query("DELETE FROM users WHERE UUID='$UUID'");
				if (!$db->exist_table("UserAccounts")) $errno = 99;
			}
		}
	}

	if ($flg) $db->close();

	if ($errno!=0) return false;
	return true;
}



//
// データベースからアバタ情報を削除する．
//
function  opensim_delete_avatar($uuid, &$db=null)
{
	if (!isGUID($uuid)) return false;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	if ($db->exist_table("UserAccounts")) {
		$db->query("DELETE FROM UserAccounts WHERE PrincipalID='$uuid'");
		$db->query("DELETE FROM auth         WHERE UUID='$uuid'");
		$db->query("DELETE FROM Avatars      WHERE PrincipalID='$uuid'");
		$db->query("DELETE FROM Friends      WHERE PrincipalID='$uuid'");
		$db->query("DELETE FROM tokens       WHERE UUID='$uuid'");
		if ($db->exist_table("Presence")) $db->query("DELETE FROM Presence WHERE UserID='$uuid'");
		if ($db->exist_table("GridUser")) $db->query("DELETE FROM GridUser WHERE UserID='$uuid'");

	}
	if ($db->exist_table("users")) {
		$db->query("DELETE FROM users        WHERE UUID='$uuid'");
		$db->query("DELETE FROM agents       WHERE UUID='$uuid'");
		$db->query("DELETE FROM avatarappearance  WHERE Owner='$uuid'");
		$db->query("DELETE FROM avatarattachments WHERE UUID='$uuid'");
		$db->query("DELETE FROM userfriends	 WHERE ownerID='$uuid'");
	}
	$db->query("DELETE FROM estate_managers	 WHERE uuid='$uuid'");
	$db->query("DELETE FROM estate_users	 WHERE uuid='$uuid'");
	$db->query("DELETE FROM estateban		 WHERE bannedUUID='$uuid'");
	$db->query("DELETE FROM inventoryfolders WHERE agentID='$uuid'");
	$db->query("DELETE FROM inventoryitems	 WHERE avatarID='$uuid'");
	$db->query("DELETE FROM landaccesslist   WHERE AccessUUID='$uuid'");
	$db->query("DELETE FROM regionban		 WHERE bannedUUID='$uuid'");

	// for DTL Money Server
	if ($db->exist_table("balances")) {
		//$db->query("DELETE FROM transactions WHERE UUID='$uuid'");
		$db->query("DELETE FROM balances WHERE user LIKE '".$uuid."@%'");
		$db->query("DELETE FROM userinfo WHERE user LIKE '".$uuid."@%'");
	}
	if ($flg) $db->close();

	return true;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Region
//

function  opensim_get_region_num(&$db=null)
{
	$num = 0;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$db->query("SELECT COUNT(*) FROM regions");
	list($num) = $db->next_record();
	if ($flg) $db->close();

	return $num;
}



function  opensim_get_region_name($region, &$db=null)
{
	if (!isGUID($region)) return null;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$db->query("SELECT regionName FROM regions WHERE uuid='$region'");
	list($regionName) = $db->next_record();
	if ($flg) $db->close();

	return $regionName;
}



//
// Attention: When call this function, please check $condition for prevention of SQL Injection.
//
function  opensim_get_region_names($condition="", &$db=null)
{
	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$regions = array();
	$db->query("SELECT regionName FROM regions ".$condition);
	while ($db->Errno==0 and list($region)=$db->next_record()) {
		$regions[] = $region;
	}
	if ($flg) $db->close();

	return $regions;
}

 

function  opensim_get_region_name_by_id($id, &$db=null)
{
	if (!isGUID($id) and !isNumeric($id)) return null;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	if (isGUID($id)) {
		$db->query("SELECT regionName FROM regions WHERE uuid='$id'");
		list($regionName) = $db->next_record();
	}
	else {
		$db->query("SELECT regionName FROM regions WHERE regionHandle='$id'");
		list($regionName) = $db->next_record();
	}

	if ($flg) $db->close();

	return $regionName;
}



function  opensim_get_region_info($region, &$db=null)
{
	if (!isGUID($region)) return null;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$db->query("SELECT regionName,serverIP,serverHttpPort,serverURI,locX,locY FROM regions WHERE uuid='$region'");
	list($regionName, $serverIP, $serverHttpPort, $serverURI, $locX, $locY) = $db->next_record();
    $rginfo = opensim_get_region_owner($region, $db);
	if ($flg) $db->close();

	$rginfo['regionName'] 	  = $regionName;
	$rginfo['serverIP'] 	  = $serverIP;
	$rginfo['serverHttpPort'] = $serverHttpPort;
	$rginfo['serverURI'] 	  = $serverURI;
	$rginfo['locX'] 		  = $locX;
	$rginfo['locY'] 		  = $locY;

	return $rginfo;
}



//
// Attention: When call this function, please check $condition for prevention of SQL Injection.
//
function  opensim_get_region_infos($condition="", &$db=null)
{
	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}
	$rginfos = array();

	$items = " regions.uuid,regionName,locX,locY,serverIP,serverURI,serverHttpPort,owner_uuid,estate_map.EstateID,EstateOwner,";
 	$join1 = " FROM regions LEFT JOIN estate_map ON RegionID=regions.uuid ";
 	$join2 = " LEFT JOIN estate_settings ON estate_map.EstateID=estate_settings.EstateID ";

	if ($db->exist_table("UserAccounts")) {
		$uname = "firstname,lastname ";
		$join3 = " LEFT JOIN UserAccounts ON EstateOwner=UserAccounts.PrincipalID ";
		$frmwh = " FROM UserAccounts WHERE UserAccounts.PrincipalID=";
	}
	else {
		$unmae = "username,lastname ";
		$join3 = " LEFT JOIN users ON EstateOwner=users.UUID ";
		$frmwh = " FROM users WHERE users.UUID=";
	}
	$query_str = "SELECT ".$items.$uname.$join1.$join2.$join3.$condition;

	$db->query($query_str);
	if ($db->Errno==0) {
		while (list($UUID,$regionName,$locX,$locY,$serverIP,$serverURI,$serverPort,
						$owneruuid,$estateid,$estateowner,$firstname,$lastname) = $db->next_record()) {
			$rginfos[$UUID]['UUID']		  	= $UUID;
			$rginfos[$UUID]['regionName'] 	= $regionName;
			$rginfos[$UUID]['locX']		  	= $locX;
			$rginfos[$UUID]['locY']		  	= $locY;
			$rginfos[$UUID]['serverIP']	  	= $serverIP;
			$rginfos[$UUID]['serverURI']  	= $serverURI;
			$rginfos[$UUID]['serverPort'] 	= $serverPort;
			$rginfos[$UUID]['owner_uuid'] 	= $owneruuid;
			$rginfos[$UUID]['estate_id'] 	= $estateid;
			$rginfos[$UUID]['estate_owner'] = $estateowner;
			$rginfos[$UUID]['est_firstname']= $firstname;
			$rginfos[$UUID]['est_lastname'] = $lastname;
			$rginfos[$UUID]['est_fullname'] = null;
			$fullname = $firstname." ".$lastname;
			if ($fullname!=" ") $rginfos[$UUID]['est_fullname'] = $fullname;
		}
	}

	// Region Owner
	foreach($rginfos as $region) {
		$rginfos[$region['UUID']]['rgn_firstname'] = null;
		$rginfos[$region['UUID']]['rgn_lastname']  = null;
		$rginfos[$region['UUID']]['rgn_fullname']  = null;

		if ($region['owner_uuid']!=null) {
			$db->query("SELECT ".$uname.$frmwh."'".$region['owner_uuid']."'");
			list($firstname,$lastname) = $db->next_record();
			$rginfos[$region['UUID']]['rgn_firstname'] = $firstname;
			$rginfos[$region['UUID']]['rgn_lastname']  = $lastname;
			$fullname = $firstname." ".$lastname;
			if ($fullname!=" ") $rginfos[$region['UUID']]['rgn_fullname'] = $fullname;
		}
	}

	if ($flg) $db->close();

	return $rginfos;
}





/////////////////////////////////////////////////////////////////////////////////////
//
// for Region Owner
//

//
// SIMのリージョンIDからオーナーの情報を返す．
// 
function  opensim_get_region_owner($region, &$db=null)
{
	if (!isGUID($region)) return null;

	$firstname = null;
	$lastname  = null;
	$fullname  = null;
	$owneruuid = null;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}
	
	if ($db->exist_table("UserAccounts")) {
		$rqdt = "PrincipalID,FirstName,LastName";
		$tbls = "UserAccounts,estate_map,estate_settings";
		$cndn = "RegionID='$region' AND estate_map.EstateID=estate_settings.EstateID AND EstateOwner=PrincipalID";
	}
	else {
		$rqdt = "UUID,username,lastname";
		$tbls = "users,estate_map,estate_settings";
		$cndn = "RegionID='$region' AND estate_map.EstateID=estate_settings.EstateID AND EstateOwner=UUID";
	}

	$db->query("SELECT ".$rqdt." FROM ".$tbls." WHERE ".$cndn);
	list($owneruuid, $firstname, $lastname) = $db->next_record();
	if ($flg) $db->close();

	$fullname = $firstname." ".$lastname;
	if ($fullname==" ") $fullname = null;

	$name['firstname']  = $firstname;
	$name['lastname']   = $lastname;
	$name['fullname']   = $fullname;
	$name['owner_uuid'] = $owneruuid;

	return $name;
}



function  opensim_set_region_owner($region, $owner, &$db=null)
{
	if (!isGUID($region)) return false;
	if (!isGUID($owner))  return false;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$db->query("UPDATE estate_settings,estate_map SET EstateOwner='$owner' WHERE estate_settings.EstateID=estate_map.EstateID AND RegionID='$region'");
	$errno = $db->Errno;
	if ($errno==0) $db->query("UPDATE regions SET owner_uuid='$owner' WHERE uuid='$region'");

	if ($flg) $db->close();

	if ($errno!=0) return false;
	return true;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Inventory
//

function  opensim_create_inventory_folders($uuid, &$db=null)
{
	if (!isGUID($uuid)) return 999;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}
	
	$my_inventory = make_random_guid();
	$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
					  "VALUES ('My Inventory','8','1','$my_inventory','$uuid','00000000-0000-0000-0000-000000000000')");
	$errno = $db->Errno;

	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Textures','0','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Sounds','1','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Calling Cards','2','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Landmarks','3','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Clothing','5','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Objects','6','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Notecards','7','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Scripts','10','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Body Parts','13','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Trash','14','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Photo Album','15','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Lost And Found','16','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Animations','20','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}
	if ($errno==0) {
		$db->query("INSERT INTO inventoryfolders (folderName,type,version,folderID,agentID,parentFolderID) ".
						  "VALUES ('Gestures','21','1','".make_random_guid()."','$uuid','$my_inventory')");
		$errno = $db->Errno;
	}

	if ($flg) $db->close();
	return $errno;
}


 

/////////////////////////////////////////////////////////////////////////////////////
//
// for Home Region
//

function  opensim_set_home_region($uuid, $hmregion, &$db=null)
{
	if (!isGUID($uuid)) return false;
	if (!isAlphabetNumericSpecial($hmregion)) return false;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$db->query("SELECT uuid,regionHandle FROM regions WHERE regionName='$hmregion'");
	$errno = $db->Errno;
	if ($errno==0) {
		list($regionID, $regionHandle) = $db->next_record();

		if ($db->exist_table("Griduser")) {
			$db->query("UPDATE GridUser SET HomeRegionID='$regionID' WHERE UserID='$uuid'");
			$errno = $db->Errno;
		}
		if ($db->exist_table("Presence")) {
			$db->query("UPDATE Presence SET HomeRegionID='$regionID' WHERE UserID='$uuid'");
			$errno = $db->Errno;
		}

		if ($db->exist_table("users") and $errno==0) {
			$db->query("UPDATE users SET homeRegion='$regionHandle',homeRegionID='$regionID' WHERE UUID='$uuid'");
			if ($db->Errno!=0) {
				if (!$db->exist_table("auth")) $errno = 99;
			}
		}
	}
	if ($flg) $db->close();

	if ($errno!=0) return false;
	return true;
}



/////////////////////////////////////////////////////////////////////////////////////
//
// for Password
//

function  opensim_get_password($uuid, $tbl="", &$db=null)
{
	if (!isGUID($uuid)) return null;
	if (!isAlphabetNumeric($tbl, true)) return null;

	$passwdhash = null;
	$passwdsalt = null;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	if ($tbl=="" or $tbl=="auth") {
		if ($db->exist_table("auth")) {
			$db->query("SELECT passwordHash,passwordSalt FROM auth WHERE UUID='$uuid'");
			list($passwdhash, $passwdsalt) = $db->next_record();
			if ($flg) $db->close();
			$ret['passwordHash'] = $passwdhash;
			$ret['passwordSalt'] = $passwdsalt;
			return $ret;
		}
	}

	if ($tbl=="" or $tbl=="users") {
		if ($db->exist_table("users")) {
			$db->query("SELECT passwordHash,passwordSalt FROM users WHERE UUID='$uuid'");
			list($passwdhash, $passwdsalt) = $db->next_record();
		}
	}
	if ($flg) $db->close();

	$ret['passwordHash'] = $passwdhash;
	$ret['passwordSalt'] = $passwdsalt;
	return $ret;
}



function  opensim_set_password($uuid, $passwdhash, $passwdsalt="", $tbl="", &$db=null)
{
	if (!isGUID($uuid)) return false;
	if (!isAlphabetNumeric($passwdhash)) return false;
	if (!isAlphabetNumeric($passwdsalt, true)) return false;
	if (!isAlphabetNumeric($tbl, true)) return false;

	if ($passwdsalt=="") {
		$passwdsalt = make_random_hash();
		$passwdhash = md5($passwdhash.":".$passwdsalt);
	}
	$setpasswd = "passwordHash='$passwdhash',passwordSalt='$passwdsalt'";

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$errno = 0;
	if ($tbl=="" or $tbl=="auth") {
		if ($db->exist_table("auth")) {
			$db->query("UPDATE auth SET ".$setpasswd." WHERE UUID='$uuid'");
			$errno = $db->Errno;
		}
	}
	if (($tbl=="" or $tbl=="users") and $errno==0) {
		if ($db->exist_table("users")) {
			$db->query("UPDATE users SET ".$setpasswd." WHERE UUID='$uuid'");
			if ($db->Errno!=0) {
				if (!$db->exist_table("auth")) $errno = 99;
			}
		}
	}
	if ($flg) $db->close();

	if ($errno!=0) return false;
	return true;
}




/////////////////////////////////////////////////////////////////////////////////////
//
// for Update DB
//

function  opensim_supply_passwordSalt(&$db=null)
{
	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$dbup = new DB;
	if ($db->exist_table('auth')) {
		$db->query("SELECT UUID,passwordHash,passwordSalt FROM auth");
		while ($data = $db->next_record()) {
			if ($data['passwordSalt']=="") {
				$passwdSalt = make_random_hash();
				$passwdHash = md5($data['passwordHash'].":".$passwdSalt);
				opensim_set_password($data['UUID'], $passwdHash, $passwdSalt, "auth", $dbup);
			}
		}
	}

	if ($db->exist_table('users')) {
		$db->query("SELECT UUID,passwordHash,passwordSalt FROM users");
		while ($data = $db->next_record()) {
			if ($data['passwordSalt']=="") {
				$passwdSalt = make_random_hash();
				$passwdHash = md5($data['passwordHash'].":".$passwdSalt);
				opensim_set_password($data['UUID'], $passwdHash, $passwdSalt, "users", $dbup);
			}
		}
	}
	$dbup->close();

	if ($flg) $db->close();
	return;
}



function  opensim_succession_agents_to_presence($region_id, &$db=null)
{
	if (!isGUID($region_id)) return false;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$db->query("SELECT agents.UUID,sessionID,secureSessionID,currentRegion,loginTime,logoutTime,homeRegion ".
							 "FROM agents,users WHERE agents.UUID=users.UUID");
	$errno = $db->Errno;
	
	if ($errno==0) {
		$db2 = new DB;
		while(list($UUID,$sessionID,$secureSessionID,$currentRegion,$login,$logout,$homeHandle) = $db->next_record())
		{
			$db2->query("SELECT uuid FROM regions WHERE regionHandle='$homeHandle'");
			list($homeRegion) = $db2->next_record();
			if ($homeRegion==null) $homeRegion = $region_id;

			$db2->query("SELECT UserID,HomeRegionID FROM Presence WHERE UserID='$UUID'");
			list($userid, $hmregion) = $db2->next_record();

			if ($userid==null) {
				if ($login!=0 and $logout<$login) $logout = $login;

				$db2->query("INSERT INTO Presence (UserID,RegionID,SessionID,SecureSessionID,Online,Login,Logout,".
												 "Position,LookAt,HomeRegionID,HomePosition,HomeLookAt) ".
								  			"VALUES ('$UUID','$currentRegion','$sessionID','$secureSessionID','false','$login','$logout',".
										   			"'<128,128,0>','<0,0,0>','$homeRegion','<128,128,0>','<0,0,0>')");
				$errno =$db2->Errno;

				if ($errno!=0) {
					$db->query("DELETE FROM Presence WHERE UserID='$UUID'");
				}
			}
			else if ($hmregion=="00000000-0000-0000-0000-000000000000" or $hmregion==null) {
				$db2->query("UPDATE Presence SET HomeRegionID='$homeRegion' WHERE UserID='$UUID'");
			}
		}
	}
	//$db2->close();	// should not be close!!
	if ($flg) $db->close();

	if ($errno!=0) return false;
	return true;
}



function  opensim_succession_agents_to_griduser($region_id, &$db=null)
{
	if (!isGUID($region_id)) return false;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$db->query("SELECT agents.UUID,currentRegion,loginTime,logoutTime,homeRegion FROM agents,users WHERE agents.UUID=users.UUID");
	$errno = $db->Errno;
	
	if ($errno==0) {
		$db2 = new DB;
		while(list($UUID,$currentRegion,$login,$logout,$homeHandle) = $db->next_record()) {
			$db2->query("SELECT uuid FROM regions WHERE regionHandle='$homeHandle'");
			list($homeRegion) = $db2->next_record();
			if ($homeRegion==null) $homeRegion = $region_id;

			$db2->query("SELECT UserID,HomeRegionID FROM GridUser WHERE UserID='$UUID'");
			list($userid, $hmregion) = $db2->next_record();

			if ($userid==null) {
				if ($login!=0 and $logout<$login) $logout = $login;

				$db2->query("INSERT INTO GridUser (UserID,HomeRegionID,HomePosition,HomeLookAt,LastRegionID,LastPosition,LastLookAt,Online,Login,Logout) ".
							"VALUES ('$UUID','$homeRegion','<128,128,0>','<0,0,0>','$currentRegion','<128,128,0>','<0,0,0>','False','$login','$logout')");
				$errno =$db2->Errno;

				if ($errno!=0) {
					$db->query("DELETE FROM GridUser WHERE UserID='$UUID'");
				}
			}
			else if ($hmregion=="00000000-0000-0000-0000-000000000000" or $hmregion==null) {
				$db2->query("UPDATE GridUser SET HomeRegionID='$homeRegion' WHERE UserID='$UUID'");
			}
		}
	}
	//$db2->close();	// should not be close!!
	if ($flg) $db->close();

	if ($errno!=0) return false;
	return true;
}



function  opensim_succession_presence_to_griduser($region_id, &$db=null)
{
	if (!isGUID($region_id)) return false;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$db->query("SELECT UserID,RegionID,Login,Logout,HomeRegionID FROM Presence");
	$errno = $db->Errno;
	
	if ($errno==0) {
		$db2 = new DB;
		while(list($UUID,$currentRegion,$login,$logout,$homeRegion) = $db->next_record()) {
			if ($homeRegion==null) $homeRegion = $region_id;

			$db2->query("SELECT UserID,HomeRegionID FROM GridUser WHERE UserID='$UUID'");
			list($userid, $hmregion) = $db2->next_record();

			if ($userid==null) {
				if ($login!=0 and $logout<$login) $logout = $login;

				$db2->query("INSERT INTO GridUser (UserID,HomeRegionID,HomePosition,HomeLookAt,LastRegionID,LastPosition,LastLookAt,Online,Login,Logout) ".
							"VALUES ('$UUID','$homeRegion','<128,128,0>','<0,0,0>','$currentRegion','<128,128,0>','<0,0,0>','False','$login','$logout')");
				$errno =$db2->Errno;

				if ($errno!=0) {
					$db->query("DELETE FROM GridUser WHERE UserID='$UUID'");
				}
			}
			else if ($hmregion=="00000000-0000-0000-0000-000000000000" or $hmregion==null) {
				$db2->query("UPDATE GridUser SET HomeRegionID='$homeRegion' WHERE UserID='$UUID'");
			}
		}
	}
	//$db2->close();	// should not be close!!
	if ($flg) $db->close();

	if ($errno!=0) return false;
	return true;
}



//
// agents -> Presence -> GridUser
//
//		$region_name is default home region name.
//
function  opensim_succession_data($region_name, &$db=null)
{
	if (!isAlphabetNumericSpecial($region_name)) return false;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$exist_agents   = $db->exist_table("agents");
	$exist_presence = $db->exist_table("Presence");
	$exist_griduser = $db->exist_table("GridUser");

	$db->query("SELECT uuid FROM regions WHERE regionName='".$region_name."'");
	list($region_id) = $db->next_record();
	if ($region_id=="") $region_id = "00000000-0000-0000-0000-000000000000";

	//if ($exist_presence and $exist_griduser) {
	//	opensim_succession_presence_to_griduser($region_id, $db);
	//}
	if ($exist_agents and $exist_griduser) {
		opensim_succession_agents_to_griduser($region_id, $db);
	}
	//if ($exist_agents and $exist_presence) {
	//	opensim_succession_agents_to_presence($region_id, $db);
	//}

	if ($flg) $db->close();
	return;
}





/////////////////////////////////////////////////////////////////////////////////////
//
// for Voice (VoIP)
//

function  opensim_get_voice_mode($region, &$db=null)
{
	if (!isGUID($region)) return -1;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}
	$voiceflag = 0x60000000;

	$db->query("SELECT LandFlags FROM land WHERE RegionUUID='$region'");
	while (list($flag) = $db->next_record()) {
		$voiceflag &= $flag;
	}
	if ($flg) $db->close();

	if      ($voiceflag==0x20000000) return 1;
	else if ($voiceflag==0x40000000) return 2;
	return 0;
}	



function  opensim_set_voice_mode($region, $mode, &$db=null)
{
	if (!isGUID($region)) false;
	if (!preg_match("/^[0-2]$/", $mode)) false;

	$flg = false;
	if (!is_object($db)) {
		$db  = new DB;
		$flg = true;
	}

	$colum = 0;
	$vflags = array();

	$db->query("SELECT UUID,LandFlags FROM land WHERE RegionUUID='$region'");
	while (list($UUID, $flag) = $db->next_record()) {
		$flag &= 0x9fffffff;
		if ($mode==1)      $flag |= 0x20000000;
		else if ($mode==2) $flag |= 0x40000000;

		$vflags[$colum]['UUID'] = $UUID;
		$vflags[$colum]['flag'] = $flag;
		$colum++;
	}

	foreach($vflags as $vflag) {
		$UUID = $vflag['UUID'];
		$flag = $vflag['flag'];
		$db->query("UPDATE land SET LandFlags='$flag' WHERE UUID='$UUID'");
	}
	if ($flg) $db->close();

	return true;
}	



?>
