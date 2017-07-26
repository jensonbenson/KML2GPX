<?php 
if ($_SERVER['REQUEST_METHOD'] == 'GET') { 
  die ('No Direct Access Allowed');
} else {
  // Use some HEX-colors from material.io as colors for the waypoints. Find more under "https://material.io/guidelines/style/color.html#color-color-palette"
  $color_list = array('F44336', 'E91E63', '9C27B0', '673AB7', '3F51B5', '2196F3', '03A9F4', '00BCD4', '009688', '4CAF50', '8BC34A', 'CDDC39', 'FFEB3B', 'FFC107', 'FF9800', 'FF5722', '795548', '9E9E9E', '607D8B' );
  
  // only accept .kml and text/xml files
  $allowed =  array('kml');
  
  function CheckFiletype ($file, $allowed) {
    $filename = $file['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if(!in_array($ext, $allowed) ) {
        return false;
    }
    return true;
  }
  
  if (isset($_FILES['file']) && ($_FILES['file']['error'] == UPLOAD_ERR_OK) && CheckFileType($_FILES['file'],$allowed)) {
        
    // LOAD KML CONTENTS
    $kml = simplexml_load_file($_FILES['file']['tmp_name']);
    
    // GENERATE NEW XML DOCUMENT FOR GPX
    $document = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><gpx></gpx>');
    $document ->addAttribute('version', '1.1');
    $document ->addAttribute('creator', 'kml2gpx');
    $document ->addAttribute('xmlns', 'http://www.topografix.com/GPX/1/1');
    $document ->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $document ->addAttribute('xsi:xsi:schemaLocation', 'http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd');
    
    // Get the folders with the waypoints
    $folders = $kml->Document->Folder;
    
    $i = 0;
    foreach($folders as $folder) {
      $folder_name = htmlspecialchars($folder->name);
      $folder_waypoints = $folder->Placemark;

      foreach($folder_waypoints as $wp) {
        $wp_name = htmlspecialchars($wp->name);
        $c_temp = explode(',',$wp->Point->coordinates);

        $coordinates['lon'] = trim(str_replace('&#10;','',$c_temp[0]));
        $coordinates['lat'] = trim(str_replace('&#10;','',$c_temp[1]));
        
        $wpt = $document->addChild('wpt','');
          $wpt->addAttribute('lat', $coordinates['lat']);
          $wpt->addAttribute('lon', $coordinates['lon']);
        
          $name = $wpt->addChild('name', $wp_name);
          $type = $wpt->addChild('type', $folder_name);
          $extensions = $wpt->addChild('extensions');
            $extensions = $extensions->addChild('color','#B4'.$color_list[$i]);
      }
      $i++;
    }
    
    // Make this file look pretty
    $SimpleXML = $document;
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($SimpleXML->asXML());
        
    // Direct download the converted file
    Header('Content-type: text/xml');
    Header('Content-Disposition: attachment; filename="favourites.gpx"');
    echo $dom->saveXML();

  } else {
    print "No valid file uploaded.";
  }
}
?>