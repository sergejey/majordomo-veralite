<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='veradevices';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");

  if ($this->mode=='poll') {
   $this->pollDevice($rec['ID']);
   $this->redirect("?id=".$rec['ID']."&view_mode=".$this->view_mode);
  }


  if ($this->mode=='update') {
   $ok=1;
  //updating 'TITLE' (varchar, required)
  /*
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }
  //updating 'UID' (varchar, required)
   global $uid;
   $rec['UID']=$uid;
   if ($rec['UID']=='') {
    $out['ERR_UID']=1;
    $ok=0;
   }
  //updating 'TYPE' (select)
   global $type;
   $rec['TYPE']=$type;
   */
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  //options for 'TYPE' (select)
  $tmp=explode('|', DEF_TYPE_OPTIONS);
  foreach($tmp as $v) {
   if (preg_match('/(.+)=(.+)/', $v, $matches)) {
    $value=$matches[1];
    $title=$matches[2];
   } else {
    $value=$v;
    $title=$v;
   }
   $out['TYPE_OPTIONS'][]=array('VALUE'=>$value, 'TITLE'=>$title);
   $type_opt[$value]=$title;
  }
  for($i=0;$i<count($out['TYPE_OPTIONS']);$i++) {
   if ($out['TYPE_OPTIONS'][$i]['VALUE']==$rec['TYPE']) {
    $out['TYPE_OPTIONS'][$i]['SELECTED']=1;
    $out['TYPE']=$out['TYPE_OPTIONS'][$i]['TITLE'];
    $rec['TYPE']=$out['TYPE_OPTIONS'][$i]['TITLE'];
   }
  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);


  $properties=SQLSelect("SELECT * FROM veraproperties WHERE DEVICE_ID='".$rec['ID']."'");

  $total=count($properties);
  for($i=0;$i<$total;$i++) {

    if ($this->mode=='update') {
      global ${'linked_object'.$properties[$i]['ID']};
      global ${'linked_property'.$properties[$i]['ID']};
      global ${'linked_method'.$properties[$i]['ID']};

      $properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
      $properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
      $properties[$i]['LINKED_METHOD']=trim(${'linked_method'.$properties[$i]['ID']});


      SQLUpdate('veraproperties', $properties[$i]);

      $old_linked_object=$properties[$i]['LINKED_OBJECT'];
      $old_linked_property=$properties[$i]['LINKED_PROPERTY'];

      if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
       //DebMes("Removing linked property ".$old_linked_object.".".$old_linked_property);
      }
      if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
       //DebMes("Adding linked property ".$properties[$i]['LINKED_OBJECT'].".".$properties[$i]['LINKED_PROPERTY']);
      }
     }

   if ($properties[$i]['SERVICE']=='urn:micasaverde-com:serviceId:ZWaveDevice1') {
    $properties[$i]['READ_ONLY']=1;
   }


  }


  $out['PROPERTIES']=$properties;