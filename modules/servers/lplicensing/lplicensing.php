<?php 
if( !defined("WHMCS") ) 
{
    exit( "This file cannot be accessed directly" );
}
$GLOBALS['license_token'] = 'YOUR_TOKEN';
$licensing = DI::make("license");
function lplicensing_MetaData()
{
    return array( "DisplayName" => "License-System", "APIVersion" => "1", "RequiresServer" => false );
}
function lplicensing_TestConnection($params){
 if (empty($params['serveraccesshash'])) {
        return "Wrong ServerHash";
    }
}
function lplicensing_ConfigOptions()
{
    $id = App::getFromRequest("id");
    $addonsCollection = WHMCS\Database\Capsule::table("tbladdons")->orderBy("weight", "asc")->orderBy("name", "asc")->get();
    $supportUpdateAddons = array(  );
    $supportUpdateAddons[00] = "None";
	$supportUpdateAddons[9] = "JetBackup5 Licensing System";
	$supportUpdateAddons[10] = "LiteSpeed Licensing System";
	$supportUpdateAddons[11] = "SitePad Licensing System";
	$supportUpdateAddons[12] = "JetBackUp Licensing System";
	$supportUpdateAddons[13] = "Plesk Dedicated Licensing System";
    $supportUpdateAddons[14] = "cPanel Dedicated Licensing System";
    $supportUpdateAddons[15] = "cPanel VPS Licensing System";
    $supportUpdateAddons[16] = "CloudLinux Licensing System";
    $supportUpdateAddons[17] = "Imunify360 Licensing System";
    $supportUpdateAddons[18] = "Plesk VPS Licensing System";
    $supportUpdateAddons[19] = "Softaculous Licensing System";
    $supportUpdateAddons[20] = "KernelCare Licensing System";
    $supportUpdateAddons[21] = "WHM Reseller Licensing System";
	$supportUpdateAddons[22] = "WHM Sonic Licensing System";

	
	
    foreach( $addonsCollection as $addon ) 
    {
        $addonId = $addon->id;
        $addonName = str_replace(",", "&comma;", $addon->name);
        $addonPackages = explode(",", $addon->packages);
        if( in_array($id, $addonPackages) ) 
        {
            $supportUpdateAddons[$addonId] = $addonName;
        }
    }
    $configarray = array( "Products" => array( "Type" => "dropdown", "Options" => $supportUpdateAddons ) );
    return $configarray;
}
function lplicensing_genkey($length, $prefix)
{
    if( !$length ) 
    {
        $length = 10;
    }
    $seeds = "abcdef0123456789";
    $key = NULL;
    $seeds_count = strlen($seeds) - 1;
    for( $i = 0; $i < $length; $i++ ) 
    {
        $key .= $seeds[rand(0, $seeds_count)];
    }
    $licensekey = $prefix . $key;
    $result = select_query("mod_licensing", "COUNT(*)", array( "licensekey" => $licensekey ));
    $data = mysql_fetch_array($result);
    if( $data[0] ) 
    {
        $licensekey = lplicensing_genkey($length, $prefix);
    }
    return $licensekey;
}
function lplicensing_CreateAccount(array $params)
{
    global $license_token;

 if (!array_key_exists('IP', $params['customfields'])) {
 return "Ip Null!";
 }
  if (empty($params['customfields']['IP'])) {
        return "Ip Empty!";
    }
    
	$result = select_query( "mod_licensing", "COUNT(*)", array( "serviceid" => $params['serviceid'] ) );
    $data = mysql_fetch_array( $result );
    $month = 1 ;
    $addonbillingcycle = $data['billingcycle'];
	if ($data[0]) {
		return "A license has already been generated for this item";
    }
    if ($addonbillingcycle == "Monthly") {
        $month = 1;
    }
    else {
        if ($addonbillingcycle == "Quarterly") {
            $month = 3;
        }
        else {
            if ($addonbillingcycle == "Semi-Annually") {
                $month = 6;
            }
            else {
                if ($addonbillingcycle == "Annually") {
                    $month = 12;

                }
                else {
                    if ($addonbillingcycle == "Biennially") {
                        $month = 24;
                    }
                    else {
                        $month = 1;
                    }
                }
            }
        }
    }
	$length = "11";
	$prefix = "LP-";
	$prod= array('9','10','11','12','13','14','15','16','17','18','19','20','21','22');
	$rep = array('jb5','litespeed','sitepad','jetbackup','dplesk','dcpanel','cpanel','cloudLinux','imunify360','plesk','softaculous','kernelcare','whmreseller','whmsonic');
	$productname=str_replace($prod,$rep,$params['configoption1']);
    $ch = curl_init();
    $args = http_build_query(['token'=>$GLOBALS['license_token']] + ['month'=> $month,'key'=>$productname,'ip'=>$params['customfields']['IP']]);
    curl_setopt($ch,CURLOPT_URL,"https://api.licenses.cc/resellerapi/register?$args");
    curl_setopt($ch,CURLOPT_USERAGENT, 'License-System');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    if(json_decode($res)->status=="success"){
    update_query("tblhosting", array("domainstatus" => "Active"), array("id" => $params["serviceid"]));
	$licensekey = lplicensing_genkey( $length, $prefix );
	insert_query( "mod_licensing", array( "serviceid" => $params['serviceid'], "licensekey" => $licensekey, "validdomain" => "", "validip" => $params['customfields']['IP'], "validdirectory" => "", "reissues" => "0", "status" => "Active" ) );
	updateService( array( "domain" => $params['customfields']['IP'], "username" => "", "password" => $licensekey ) );
	$addonid = explode( "|", $params['configoption7'] );
	$addonid = $addonid[0];
	if ($addonid) {
		$result = select_query( "tblhosting", "orderid,paymentmethod", array( "id" => $params['serviceid'] ) );
		$data = mysql_fetch_array( $result );
		$orderid = $data['orderid'];
		$paymentmethod = $data['paymentmethod'];
		$result = select_query( "tbladdons", "", array( "id" => $addonid ) );
		$data = mysql_fetch_array( $result );
		$addonname = $data['name'];
		$result = select_query( "tblpricing", "", array( "relid" => $addonid, "type" => "addon", "currency" => $params['clientsdetails']['currency'] ) );
		$data2 = mysql_fetch_array( $result );
		$addonsetupfee = $data2['msetupfee'];
		$addonrecurring = $data2['monthly'];
		$addonbillingcycle = $data['billingcycle'];
		$addontax = $data['tax'];
		if ($addonbillingcycle == "Monthly") {
			$nextduedate = date( "Y-m-d", mktime( 0, 0, 0, date( "m" ) + 1, date( "d" ), date( "Y" ) ) );
		}
		else {
			if ($addonbillingcycle == "Quarterly") {
				$nextduedate = date( "Y-m-d", mktime( 0, 0, 0, date( "m" ) + 3, date( "d" ), date( "Y" ) ) );
			}
			else {
				if ($addonbillingcycle == "Semi-Annually") {
					$nextduedate = date( "Y-m-d", mktime( 0, 0, 0, date( "m" ) + 6, date( "d" ), date( "Y" ) ) );
				}
				else {
					if ($addonbillingcycle == "Annually") {
						$nextduedate = date( "Y-m-d", mktime( 0, 0, 0, date( "m" ) + 12, date( "d" ), date( "Y" ) ) );
					}
					else {
						if ($addonbillingcycle == "Biennially") {
							$nextduedate = date( "Y-m-d", mktime( 0, 0, 0, date( "m" ) + 24, date( "d" ), date( "Y" ) ) );
						}
						else {
							$nextduedate = "0000-00-00";
						}
					}
				}
			}
		}
		insert_query( "tblhostingaddons", array( "orderid" => $orderid, "hostingid" => $params['serviceid'], "addonid" => $addonid, "setupfee" => $addonsetupfee, "recurring" => $addonrecurring, "billingcycle" => $addonbillingcycle, "tax" => $addontax, "status" => "Active", "regdate" => "now()", "nextduedate" => $nextduedate, "nextinvoicedate" => $nextduedate, "paymentmethod" => $paymentmethod ) );
	}
    return "success";
    }else{

    return json_decode($res)->message;
    }

}
function lplicensing_SuspendAccount($params)
{

    $addonId = (array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0);
    $existingLicense = lplicensing_does_license_exist($params);
    if( !$existingLicense ) 
    {
        return "No license exists for this item";
    }
	$prod= array('9','10','11','12','13','14','15','16','17','18','19','20','21','22');
	$rep = array('jb5','litespeed','sitepad','jetbackup','dplesk','dcpanel','cpanel','cloudLinux','imunify360','plesk','softaculous','kernelcare','whmreseller','whmsonic');
    $productname=str_replace($prod,$rep,$params['configoption1']);
    $args = http_build_query(['token'=>$GLOBALS['license_token']] + ['key'=>$productname,'ip'=>$params['customfields']['IP']]);
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"https://api.licenses.cc/resellerapi/deactivate?$args");
    curl_setopt($ch,CURLOPT_USERAGENT, 'License-System');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    if(json_decode($res)->status=="success"){
    WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->update(array( "status" => "Suspended" ));
    return "success";
    }else{
    return json_decode($res)->message;
    }
}
function lplicensing_UnsuspendAccount($params)
{
    $addonId = (array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0);
    $existingLicense = lplicensing_does_license_exist($params);
    if( !$existingLicense ) 
    {
        return "No license exists for this item";
    }
	$prod= array('9','10','11','12','13','14','15','16','17','18','19','20','21','22');
	$rep = array('jb5','litespeed','sitepad','jetbackup','dplesk','dcpanel','cpanel','cloudLinux','imunify360','plesk','softaculous','kernelcare','whmreseller','whmsonic');
    $productname=str_replace($prod,$rep,$params['configoption1']);
    $args = http_build_query(['token'=>$GLOBALS['license_token']] + ['key'=>$productname,'ip'=>$params['customfields']['IP']]);
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"https://api.licenses.cc/resellerapi/activate?$args");
    curl_setopt($ch,CURLOPT_USERAGENT, 'License-System');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    if(json_decode($res)->status=="success"){
    WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->update(array( "status" => "Active" ));
     return "success";
    }else{
    return json_decode($res)->message;
    }
}
function lplicensing_TerminateAccount($params)
{

    $addonId = (array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0);
    $existingLicense = lplicensing_does_license_exist($params);
    if( !$existingLicense ) 
    {
        return "No license exists for this item";
    }
	$prod= array('9','10','11','12','13','14','15','16','17','18','19','20','21','22');
	$rep = array('jb5','litespeed','sitepad','jetbackup','dplesk','dcpanel','cpanel','cloudLinux','imunify360','plesk','softaculous','kernelcare','whmreseller','whmsonic');
	$productname=str_replace($prod,$rep,$params['configoption1']);
    $args = http_build_query(['token'=>$GLOBALS['license_token']] + ['key'=>$productname,'ip'=>$params['customfields']['IP']]);
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"#");
    curl_setopt($ch,CURLOPT_USERAGENT, 'License-System');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    if(json_decode($res)->status=="success"){
    WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->update(array( "status" => "Expired" ));
    return "success";
    }else{
    return json_decode($res)->message;
    }
}
function lplicensing_AdminCustomButtonArray()
{
    $buttonarray = array( "Renew license" => "ReNew" );
    return $buttonarray;
}
function lplicensing_ClientAreaCustomButtonArray()
{
    $buttonarray = array( "ChangeIp"=>"ChangeIp" );
    return $buttonarray;
}

function lplicensing_revoke($params)
{
    $addonId = (array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0);
    $existingLicense = lplicensing_does_license_exist($params);
    if( !$existingLicense ) 
    {
        return "No license exists for this item";
    }
    WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->delete();
    updateService(array( "domain" => "" ));
    return "success";
}
function lplicensing_ReNew($params)
{

   $addonId = (array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0);
    $existingLicense = lplicensing_does_license_exist($params);
    if( !$existingLicense ) 
    {
        return "No license exists for this item";
    }
    $result = select_query( "mod_licensing", "COUNT(*)", array( "serviceid" => $params['serviceid'] ) );
    $data = mysql_fetch_array( $result );
    $addonbillingcycle = $data['billingcycle'];
    $month = 1 ;
    if ($addonbillingcycle == "Monthly") {
        $month = 1;
    }
    else {
        if ($addonbillingcycle == "Quarterly") {
            $month = 3;
        }
        else {
            if ($addonbillingcycle == "Semi-Annually") {
                $month = 6;
            }
            else {
                if ($addonbillingcycle == "Annually") {
                    $month = 12;

                }
                else {
                    if ($addonbillingcycle == "Biennially") {
                        $month = 24;
                    }
                    else {
                        $month = 1;
                    }
                }
            }
        }
    }

	$prod= array('9','10','11','12','13','14','15','16','17','18','19','20','21','22');
	$rep = array('jb5','litespeed','sitepad','jetbackup','dplesk','dcpanel','cpanel','cloudLinux','imunify360','plesk','softaculous','kernelcare','whmreseller','whmsonic');
    $productname=str_replace($prod,$rep,$params['configoption1']);
    $args = http_build_query(['token'=>$GLOBALS['license_token']] + ['month'=>$month,'key'=>$productname,'ip'=>$params['customfields']['IP']]);
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"https://api.licenses.cc/resellerapi/register?$args");
    curl_setopt($ch,CURLOPT_USERAGENT, 'License-System');
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    if(json_decode($res)->status=="success"){
    return "success";
    }else{
    return json_decode($res)->message;
    }
}
function lplicensing_valid_input_clean($vals)
{
    $vals = explode(",", $vals);
    foreach( $vals as $k => $v ) 
    {
        $vals[$k] = trim($v, " \t\n\r");
    }
    return implode(",", $vals);
}
function lplicensing_AdminServicesTabFields($params)
{
    global $aInt;
    $license = lplicensing_get_license($params);
    if( $license ) 
    {
        $licenseId = $license->id;
        $validdomain = $license->validdomain;
        $validip = $license->validip;
        $validdirectory = $license->validdirectory;
        $reissues = $license->reissues;
        $status = $license->status;
        $lastAccess = $license->lastaccess;
        if( $lastAccess == "0000-00-00 00:00:00" ) 
        {
            $lastAccess = "Never";
        }
        else
        {
            $lastAccess = fromMySQLDate($lastAccess, "time");
        }
        $statusoptions = "<option";
        if( $status == "Active" ) 
        {
            $statusoptions .= " selected";
        }
        $statusoptions .= ">Active</option><option";
        if( $status == "Suspended" ) 
        {
            $statusoptions .= " selected";
        }
        $statusoptions .= ">Suspended</option><option";
        if( $status == "Expired" ) 
        {
            $statusoptions .= " selected";
        }
        $statusoptions .= ">Expired</option>";
        $licenseLogs = WHMCS\Database\Capsule::table("mod_licensinglog")->where("licenseid", "=", $licenseId)->orderBy("id", "DESC")->limit(10)->offset(0)->get();
        $tableData = array(  );
        foreach( $licenseLogs as $licenseLog ) 
        {
            $tableData[] = array( fromMySQLDate($licenseLog->datetime, true), $licenseLog->domain, $licenseLog->ip, $licenseLog->path, $licenseLog->message );
        }
        $aInt->sortableTableInit("nopagination");
        $recentAccessLog = $aInt->sortableTable(array( "Date", "Domain", "IP", "Path", "Result" ), $tableData);
        $fieldsArray = array(  "License Status" => "<select name=\"modulefields[3]\" id=\"licensestatus\" class=\"form-control select-inline\">" . $statusoptions . "</select>");
        return $fieldsArray;
    }
    else
    {
        return array(  );
    }
}
function lplicensing_AdminServicesTabFieldsSave($params)
{
    update_query("mod_licensing", array( "validdomain" => lplicensing_valid_input_clean($_POST["modulefields"][0]), "validip" => lplicensing_valid_input_clean($_POST["modulefields"][1]), "validdirectory" => lplicensing_valid_input_clean($_POST["modulefields"][2]), "status" => $_POST["modulefields"][3] ), array( "serviceid" => $params["serviceid"] ));
}

function lplicensing_ClientArea($params)
{

    $addonId = (array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0);
    $model = $params["model"];
    $licenseData = WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->first();
    $productName = ($model instanceof WHMCS\Service\Addon ? $model->productAddon->name : $model->product->name);
    $licenseKey = $licenseData->licensekey;
    $validDomain = $licenseData->validdomain;
    $validIp = $params['customfields']['IP'];
    if($licenseData->validdirectory != ""){
    $validDirectory = $licenseData->validdirectory;
    }else{
	$prod= array('9','10','11','12','13','14','15','16','17','18','19','20','21','22');
	$rep = array('jb5','litespeed','sitepad','jetbackup','dplesk','dcpanel','cpanel','cloudLinux','imunify360','plesk','softaculous','kernelcare','whmreseller','whmsonic');
	$productname=str_replace($prod,$rep,$params['configoption1']);
    $args = http_build_query(['token'=>$GLOBALS['license_token']]  + ['key'=>$productname,'ip'=>$params['customfields']['IP'] ]);
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"https://api.licenses.cc/resellerapi/licenseinfo?$args");
    curl_setopt($ch,CURLOPT_USERAGENT, 'License-System');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    $validDirectory= json_decode($res)->cmd;
    }
    $status = $licenseData->status;
    $licenseCk = lplicensing_get_license($params);
    if( $model instanceof WHMCS\Service\Addon ) 
    {
        $allowReissues = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption3")->first()->value;
        $allowDomainConflicts = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption4")->first()->value;
        $allowIpConflicts = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption5")->first()->value;
        $allowDirectoryConflicts = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption6")->first()->value;
    }
    else
    {
        $allowReissues = (bool) $model->product->moduleConfigOption3;
        $allowDomainConflicts = (bool) $model->product->moduleConfigOption4;
        $allowIpConflicts = (bool) $model->product->moduleConfigOption5;
        $allowDirectoryConflicts = (bool) $model->product->moduleConfigOption6;
    }
    if($licenseCk->reissues > 2){
    $allowReissues = true;
    }else{
    $allowReissues = false;   
    }
    return array( "overrideDisplayTitle" => $productName, "overrideBreadcrumb" => array( array( "clientarea.php?action=products&module=lplicensing", Lang::trans("licensingaddon.mylicenses") ), array( "clientarea.php?action=productdetails#", Lang::trans("licensingaddon.manageLicense") ) ), "tabOverviewReplacementTemplate" => "managelicense.tpl", "tabOverviewModuleOutputTemplate" => "licenseinfo.tpl", "templateVariables" => array( "licensekey" => $licenseKey, "validdomain" => $validDomain, "validip" => $validIp, "validdirectory" => $validDirectory, "status" => $status, "allowreissues" => $allowReissues, "allowDomainConflicts" => $allowDomainConflicts, "allowIpConflicts" => $allowIpConflicts, "allowDirectoryConflicts" => $allowDirectoryConflicts ) );
}
function lplicensing_ChangeIp($params){
   $license = lplicensing_get_license($params);
   
    if($license->reissues < 3){
	$prod= array('9','10','11','12','13','14','15','16','17','18','19','20','21','22');
	$rep = array('jb5','litespeed','sitepad','jetbackup','dplesk','dcpanel','cpanel','cloudLinux','imunify360','plesk','softaculous','kernelcare','whmreseller','whmsonic');
    $productname=str_replace($prod,$rep,$params['configoption1']);
    if(isset($_POST['IPc'])&&$_POST['IPc']!=""&&$params['customfields']['IP']!=$_POST['IPc']){
    $args = http_build_query(['token'=>$GLOBALS['license_token']] + ['key'=>$productname,'ip'=>$params['customfields']['IP'],'ip_new'=>$_POST['IPc']]);
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,"https://api.licenses.cc/resellerapi/changeiplicense?$args");
    curl_setopt($ch,CURLOPT_USERAGENT, 'License-System');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    $ok= json_decode($res)->status;
    if($ok=="success"){
    update_query('tblhosting', array('domain' => $_POST['IPc'],),
                array('id' => $params["serviceid"],));
    update_query('tblcustomfieldsvalues', array('value' => $_POST['IPc'],),
                array('value' => $params['customfields']['IP'], 'relid' => $params["serviceid"],));
    WHMCS\Database\Capsule::table("mod_licensing")->where("id", "=", $license->id)->increment("reissues", 1);
    return "success";
    }else{
    return json_decode($res)->message;
    }
    }else{
    return "Error!";
    }
    
    }else{
    return "Unk Ip!";
    }
}
function lplicensing_does_license_exist(array $params)
{
    $addonId = (array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0);
    return WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->count();
}
function lplicensing_get_license(array $params)
{
    $addonId = (array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0);
    return WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->first();
}
