<?php

    require_once("base.php");

    class GPXExporter extends Exporter
    {

        public function export($showbearings)
        {
            // Temporarily cache local variables like $db as long as it isn't using $this
            // A future patch is removing the usage anyway so a more through change is not
            // necessary.
            $db = $this->db;
            $userid = $this->userid;
            $datefrom = $this->datefrom;
            $dateto = $this->dateto;
            $tripname = $this->tripname;

    // Condition
    $cond = ""; 
            if (is_null($this->tripid))
      $cond = "WHERE FK_Trips_ID is null AND A1.FK_USERS_ID='$userid' ";
            else if ($this->tripid !== true)
      $cond = "INNER JOIN trips A2 ON A1.FK_Trips_ID=A2.ID AND A2.Name='$tripname' WHERE A1.FK_USERS_ID='$userid' ";
    else
      $cond = "LEFT JOIN trips A2 ON A1.FK_Trips_ID=A2.ID WHERE A1.FK_USERS_ID='$userid' ";     

    if ( $datefrom != "" )
      $cond .=" and DateOccurred>='$datefrom' ";
    if ( $dateto != "" )
      $cond .=" and DateOccurred<='$dateto' ";
      
    $cond .=" order by dateoccurred desc";

    // Main query
      $sql = "select DateOccurred,latitude, longitude,speed,altitude,fk_icons_id as customicon, A1.comments,A1.imageurl,A1.angle from positions A1 ";

    $sql = $sql.$cond;
    $result = $db->exec_sql($sql);

    $n=0;
    $bounds_lat_min = 0;
    $bounds_lat_max = 0;
    $bounds_lon_min = 0;
    $bounds_lon_max = 0;
    $wptdata="";
    $trkptdata="<trk>\n";
    $trkptdata.="<trkseg>\n";
    while( $row=$result->fetch() )
    {
      if(($row['latitude']<$bounds_lat_min && $bounds_lat_min!=0) || $bounds_lat_min==0) { $bounds_lat_min = $row['latitude']; }
      if(($row['latitude']>$bounds_lat_max && $bounds_lat_max!=0) || $bounds_lat_max==0) { $bounds_lat_max = $row['latitude']; }
      if(($row['longitude']<$bounds_lon_min && $bounds_lon_min!=0) || $bounds_lon_min==0) { $bounds_lon_min = $row['longitude']; }
      if(($row['longitude']>$bounds_lon_max && $bounds_lon_max!=0) || $bounds_lon_max==0) { $bounds_lon_max = $row['longitude']; }
      $speedMPH = number_format($row['speed']*2.2369362920544,2);
      $speedKPH = number_format($row['speed']*3.6,2);   
      $altitudeFeet = number_format($row['altitude']*3.2808399,2);
      $altitudeM = number_format($row['altitude'],2);     
      $angle = number_format($row['angle'],2);
      /*
      $wptdata.="<wpt lat=\"" . $row['latitude'] . "\" lon=\"" . $row['longitude'] . "\">\n";
      $wptdata.=" <ele>" . $row['altitude'] . "</ele>\n";
      $wptdata.=" <time>".date('Y-m-d',$row['DateOccured'])."T".date('H:i:s',$row['DateOccured'])."Z</time>\n";
      $wptdata.=" <name><![CDATA[".date('Y-m-d',$row['DateOccured'])."-".str_pad($n,3,"0", STR_PAD_LEFT)."]]></name>\n";
      //$wptdata.=" <cmt><![CDATA[".$row['comment']."]]></cmt>\n";
      //$wptdata.=" <desc><![CDATA[Speed: ".$speedMPH." MPH (".$speedKPH." km/h)]]></desc>\n";
      //$wptdata.=" <sym>Dot</sym>\n";
      //$wptdata.=" <type><![CDATA[Dot]]></type>\n";
      $wptdata.="</wpt>\n";*/
        $row["DateOccured"] = strtotime($row["DateOccurred"]);
      $trkptdata.="<trkpt lat=\"" . $row['latitude'] . "\" lon=\"" . $row['longitude'] . "\">\n";
      $trkptdata.=" <ele>" . $altitudeM . "</ele>\n";
      $trkptdata.=" <time>".date('Y-m-d',$row['DateOccured'])."T".date('H:i:s',$row['DateOccured'])."Z</time>\n";
      $trkptdata.=" <desc><![CDATA[Lat.=" . $row['latitude'] . ", Long.=" . $row['logitude'] . ", Alt.=" . $altitudeM . ", Speed=".$speedKPH."Km/h, Course=" . $angle . "deg.]]></desc>\n";
      $trkptdata.="</trkpt>\n";
      $n++;
    }
    $trkptdata.="</trkseg>\n</trk>\n</gpx>";
    $header="<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n";
    $header.="<gpx version=\"1.1\" creator=\"GPX-Exporter by Ulrich Wolf - http://wolf-u.li\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"http://www.topografix.com/GPX/1/1\" xsi:schemaLocation=\"http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd\">\n";
    $header.="<metadata>\n";
    $header.="  <name>".$this->tripname."</name>\n";
    $header.="  <desc>GPX-Track of TrackMe</desc>\n";
    $header.="  <author>\n";
    $header.="    <name>Ulrich Wolf</name>\n";
    $header.="    <link href=\"http://wolf-u.li\">\n";
    $header.="      <text>wolf-u.li</text>\n";
    $header.="    </link>\n";
    $header.="  </author>\n";
    $header.="  <time>".date('Y-m-d')."T".date('H:i:s')."Z</time>\n";
    $header.="  <keywords><![CDATA[Geocaching,Geotagging,GPS]]></keywords>\n";
    $header.="<bounds minlat=\"" . $bounds_lat_min . "\" minlon=\"" . $bounds_lon_min . "\" maxlat=\"" . $bounds_lat_max . "\" maxlon=\"" . $bounds_lon_max . "\"/>\n";
    $header.="</metadata>\n";

            return $header.$wptdata.$trkptdata;
        }
    }

?>
