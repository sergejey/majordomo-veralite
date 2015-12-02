<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  if (IsSet($this->device_id)) {
   $device_id=$this->device_id;
   $qry.=" AND DEVICE_ID='".$this->device_id."'";
  } else {
   global $device_id;
  }
  //searching 'TITLE' (varchar)
  global $title;
  if ($title!='') {
   $qry.=" AND TITLE LIKE '%".DBSafe($title)."%'";
   $out['TITLE']=$title;
  }
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['veraproperties_qry'];
  } else {
   $session->data['veraproperties_qry']=$qry;
  }
  if (!$qry) $qry="1";
  // FIELDS ORDER
  global $sortby_veraproperties;
  if (!$sortby_veraproperties) {
   $sortby_veraproperties=$session->data['veraproperties_sort'];
  } else {
   if ($session->data['veraproperties_sort']==$sortby_veraproperties) {
    if (Is_Integer(strpos($sortby_veraproperties, ' DESC'))) {
     $sortby_veraproperties=str_replace(' DESC', '', $sortby_veraproperties);
    } else {
     $sortby_veraproperties=$sortby_veraproperties." DESC";
    }
   }
   $session->data['veraproperties_sort']=$sortby_veraproperties;
  }
  if (!$sortby_veraproperties) $sortby_veraproperties="TITLE";
  $out['SORTBY']=$sortby_veraproperties;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM veraproperties WHERE $qry ORDER BY ".$sortby_veraproperties);
  if ($res[0]['ID']) {
   colorizeArray($res);
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
