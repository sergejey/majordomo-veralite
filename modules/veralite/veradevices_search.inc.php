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
  //searching 'TITLE' (varchar)
  global $title;
  if ($title!='') {
   $qry.=" AND TITLE LIKE '%".DBSafe($title)."%'";
   $out['TITLE']=$title;
  }
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['veradevices_qry'];
  } else {
   $session->data['veradevices_qry']=$qry;
  }
  if (!$qry) $qry="1";
  // FIELDS ORDER
  global $sortby_veradevices;
  if (!$sortby_veradevices) {
   $sortby_veradevices=$session->data['veradevices_sort'];
  } else {
   if ($session->data['veradevices_sort']==$sortby_veradevices) {
    if (Is_Integer(strpos($sortby_veradevices, ' DESC'))) {
     $sortby_veradevices=str_replace(' DESC', '', $sortby_veradevices);
    } else {
     $sortby_veradevices=$sortby_veradevices." DESC";
    }
   }
   $session->data['veradevices_sort']=$sortby_veradevices;
  }
  if (!$sortby_veradevices) $sortby_veradevices="TITLE";
  $out['SORTBY']=$sortby_veradevices;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM veradevices WHERE $qry ORDER BY ".$sortby_veradevices);
  if ($res[0]['ID']) {
   colorizeArray($res);
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
