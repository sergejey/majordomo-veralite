<?php
/**
* Veralite 
*
* Veralite
*
* @package project
* @author Serge J. <jey@tut.by>
* @copyright http://www.atmatic.eu/ (c)
* @version 0.1 (wizard, 11:12:03 [Dec 02, 2015])
*/
Define('DEF_TYPE_OPTIONS', 'sensor|actuator'); // options for 'TYPE'
//
//
class veralite extends module {
/**
* veralite
*
* Module class constructor
*
* @access private
*/
function veralite() {
  $this->name="veralite";
  $this->title="Z-Wave Vera";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  if (IsSet($this->device_id)) {
   $out['IS_SET_DEVICE_ID']=1;
  }
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }

 $this->getConfig();
 $out['ZWAVE_API_URL']=$this->config['ZWAVE_API_URL'];

 if (!$out['ZWAVE_API_URL']) {
  $out['ZWAVE_API_URL']='http://';
 }

 if ($this->view_mode=='update_settings') {
   global $zwave_api_url;

   if (!preg_match('/\/$/', $zwave_api_url)) {
    $zwave_api_url=$zwave_api_url.'/';
   }
   $this->config['ZWAVE_API_URL']=$zwave_api_url;
   $this->saveConfig();
   $this->view_mode='rescan';
   //SQLExec("DELETE FROM veradevices");
   //SQLExec("DELETE FROM veraproperties");
 }

 $out['API_STATUS']=$this->connect();

 if ($this->view_mode=='rescan') {
  $this->scanNetwork();
  $this->redirect("?scanned=1");
 }


 if ($this->data_source=='veradevices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_veradevices') {
   $this->search_veradevices($out);
  }
  if ($this->view_mode=='edit_veradevices') {
   $this->edit_veradevices($out, $this->id);
  }
  if ($this->view_mode=='delete_veradevices') {
   $this->delete_veradevices($this->id);
   $this->redirect("?data_source=veradevices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='veraproperties') {
  if ($this->view_mode=='' || $this->view_mode=='search_veraproperties') {
   $this->search_veraproperties($out);
  }
 }
}

 function propertySetHandle($object, $property, $value) {
   $zwave_properties=SQLSelect("SELECT ID FROM veraproperties WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($zwave_properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     $this->setProperty($zwave_properties[$i]['ID'], $value);
    }
   }
 }


 /**
 * Title
 *
 * Description
 *
 * @access public
 */
 function api_request($id, $params='') {

  if (!$this->config['ZWAVE_API_URL']) {
   $this->getConfig();
  }

  $url=$this->config['ZWAVE_API_URL'].'data_request?output_format=json&id='.$id.'&'.$params;
  DebMes("Veralite API request: ".$url);
  $data=getURL($url);
  return $data;
 }

 function connect() {
  if ($this->config['ZWAVE_API_URL']) {
   $data=$this->api_request('alive');
   if ($data=='OK') {
    return 1;
   }
  }
  return 0;
 }

 function pollDevice($id, $states_data='') {
  $dev_rec=SQLSelectOne("SELECT * FROM veradevices WHERE ID='".$id."'");

  if (!is_array($states_data)) {
   $data=$this->api_request('status', 'DeviceNum='.$dev_rec['DEVICE_NUM']);
   $json=json_decode($data, true);
   $states_data=$json['Device_Num_'.$dev_rec['DEVICE_NUM']]['states'];
   if (!is_array($states_data)) {
    return 0;
   }
  }

  /*
   $data=$this->api_request('invoke', 'DeviceNum='.$dev_rec['DEVICE_NUM']);
   echo $data;exit;
   $json=json_decode($data, true);
   print_r($json);exit;
   */

     $totals=count($states_data);
     for($is=0;$is<$totals;$is++) {
      $state_id=$states_data[$is]['id'];


      $property=SQLSelectOne("SELECT * FROM veraproperties WHERE DEVICE_ID='".$dev_rec['ID']."' AND VARIABLE LIKE '".DBSafe($states_data[$is]['variable'])."'");

      $old_value=$property['VALUE'];

      if (!$property['ID']) {
       $property=array();
       $property['DEVICE_ID']=$dev_rec['ID'];
       $property['STATE_ID']=(int)$state_id;
       $property['VARIABLE']=$states_data[$is]['variable'];
       $property['ID']=SQLInsert('veraproperties', $property);
      }
      $property['SERVICE']=$states_data[$is]['service'];
      $property['VALUE']=$states_data[$is]['value'];
      $property['UPDATED']=date('Y-m-d H:i:s');
      SQLUpdate('veraproperties', $property);


     $validated=true;
     if ($property['LINKED_OBJECT'] && $property['LINKED_PROPERTY'] && $validated) {
      $old_value=getGlobal($property['LINKED_OBJECT'].'.'.$property['LINKED_PROPERTY']);
      if ($prop['VALUE']!=$old_value) {
       setGlobal($property['LINKED_OBJECT'].'.'.$property['LINKED_PROPERTY'], $property['VALUE'], array($this->name=>'0'));
      }
     }

     if ($property['LINKED_OBJECT'] && $property['LINKED_METHOD'] && $validated && ($property['VALUE']!=$old_value || (!$property['LINKED_PROPERTY']))) {
      $params=array();
      $params['VALUE']=$property['VALUE'];
      callMethod($property['LINKED_OBJECT'].'.'.$property['LINKED_METHOD'], $params);
     }

    }

 }


 function scanNetwork() {
  $data=$this->api_request('user_data');
  $json=json_decode($data, true);

 //$tmp=$this->api_request('user_data');
 //   $json=json_decode($tmp, true);
 //   print_r($json);exit;


  if (is_array($json['devices'])) {
   $devices=$json['devices'];
   $total=count($devices);
   for($i=0;$i<$total;$i++) {
    $device_id=$devices[$i]['id'];
    $dev_rec=SQLSelectOne("SELECT * FROM veradevices WHERE DEVICE_NUM='".$device_id."'");
    $dev_rec['TITLE']=$devices[$i]['name'];
    if (!$dev_rec['TITLE']) {
     $dev_rec['TITLE']='Device '.$devices[$i]['device_type'];
    }
    $dev_rec['TYPE']=$devices[$i]['device_type'];
    $dev_rec['UID']=$devices[$i]['local_udn'];


    if (!$dev_rec['ID']) {
     $dev_rec['DEVICE_NUM']=$device_id;
     $dev_rec['ID']=SQLInsert('veradevices', $dev_rec);
    } else {
     SQLUpdate('veradevices', $dev_rec);
    }


    if (is_array($devices[$i]['states'])) {
     $this->pollDevice($dev_rec['ID'], $devices[$i]['states']);
    }

   }
  }
  //print_r($json);exit;
 }


/**
* Title
*
* Description
*
* @access public
*/
 function setProperty($property_id, $value) {
  $property=SQLSelectOne("SELECT * FROM veraproperties WHERE ID='".$property_id."'");
  $device=SQLSelectOne("SELECT * FROM veradevices WHERE ID='".$property['DEVICE_ID']."'");
  if ($property['SERVICE']=='urn:upnp-org:serviceId:Dimming1' && $property['VARIABLE']=='LoadLevelTarget') {
   $result=$this->api_request('action', 'DeviceNum='.$device['DEVICE_NUM'].'&serviceId='.$property['SERVICE'].'&action=SetLoadLevelTarget&newLoadlevelTarget='.$value);
  }
  if ($property['SERVICE']=='urn:upnp-org:serviceId:SwitchPower1' && $property['VARIABLE']=='Status') {
   $result=$this->api_request('action', 'DeviceNum='.$device['DEVICE_NUM'].'&serviceId='.$property['SERVICE'].'&action=SetTarget&newTargetValue='.$value);
  }
  $this->pollDevice($device['ID']);
 }

/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* veradevices search
*
* @access public
*/
 function search_veradevices(&$out) {
  require(DIR_MODULES.$this->name.'/veradevices_search.inc.php');
 }
/**
* veradevices edit/add
*
* @access public
*/
 function edit_veradevices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/veradevices_edit.inc.php');
 }
/**
* veradevices delete record
*
* @access public
*/
 function delete_veradevices($id) {
  $rec=SQLSelectOne("SELECT * FROM veradevices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM veradevices WHERE ID='".$rec['ID']."'");
 }
/**
* veraproperties search
*
* @access public
*/
 function search_veraproperties(&$out) {
  require(DIR_MODULES.$this->name.'/veraproperties_search.inc.php');
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS veradevices');
  SQLExec('DROP TABLE IF EXISTS veraproperties');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall() {
/*
veradevices - Veralite devices
veraproperties - Veralite properties
*/
  $data = <<<EOD

 veradevices: ID int(10) unsigned NOT NULL auto_increment
 veradevices: TITLE varchar(255) NOT NULL DEFAULT ''
 veradevices: UID varchar(255) NOT NULL DEFAULT ''
 veradevices: DEVICE_NUM varchar(255) NOT NULL DEFAULT ''
 veradevices: TYPE varchar(255) NOT NULL DEFAULT ''

 veraproperties: ID int(10) unsigned NOT NULL auto_increment
 veraproperties: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 veraproperties: STATE_ID int(10) NOT NULL DEFAULT '0'
 veraproperties: TITLE varchar(255) NOT NULL DEFAULT ''
 veraproperties: SERVICE varchar(255) NOT NULL DEFAULT ''
 veraproperties: VARIABLE varchar(255) NOT NULL DEFAULT ''
 veraproperties: VALUE varchar(255) NOT NULL DEFAULT ''
 veraproperties: UPDATED datetime
 veraproperties: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 veraproperties: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
 veraproperties: LINKED_METHOD varchar(255) NOT NULL DEFAULT ''

EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgRGVjIDAyLCAyMDE1IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
