<html>
<head>
<style>
  body { background-color: #333; font-family: Arial; color: #fff; margin: 0; }
  td { line-height: 1em; }
  abbr { border-bottom: 0; }
  
  #dayName { display: block; font-weight: bold; text-transform: uppercase; font-size: 24pt; margin: 10px; text-shadow: 0.03em 0.03em 0.03em #222; }
  .weekend { color: #ff3; }
  
  #dayColumn { background-color: #444; }
  .tempHigh { font-weight: bold; font-size: 48pt; color: #ff3; text-shadow: 0.03em 0.03em 0.03em #222; height: 1em; text-align: center; vertical-align: middle; }
  .tempLow { font-weight: bold; font-size: 36pt; color: #66f; text-shadow: 0.03em 0.03em 0.03em #ccc; height: 1em; text-align: center; vertical-align: middle; }
  
  .normals { font-size: 16pt; color: #fff; text-shadow: 0.03em 0.03em 0.03em #222; margin: -0.25em 0; height: 1em; text-align: center; vertical-align: middle; }
  .normalHigh, .normalLow { font-weight: bold; font-size: 24pt; color: #fff; text-shadow: 0.03em 0.03em 0.03em #222; height: 0.5em; filter: alpha(opacity=60); -moz-opacity:.60; opacity:.60; }
  .normalHigh { background-color: #991; }
  .normalLow { background-color: #44a; }
  
  #sunColumn { background-color: #555; }
  .sunriseDate { color: #999; font-size: 20pt; text-transform: uppercase; margin: 0 0.2em 0 0.5em; text-shadow: 0.03em 0.03em 0.03em #222; height: 1em; }
  .sunsetDate { color: #999; font-size: 20pt; text-transform: uppercase; margin: 0 0.2em 0 0.5em; text-shadow: 0.03em 0.03em 0.03em #222; height: 1em; }
  .sunriseTime { color: #f93; font-weight: bold; font-size: 36pt; height: 1em; margin-right: 0.5em; text-shadow: 0.03em 0.03em 0.03em #222; height: 1em; }
  .sunsetTime { color: #39f; font-weight: bold; font-size: 36pt; height: 1em; margin-right: 0.5em; text-shadow: 0.03em 0.03em 0.03em #222; height: 1em; }
  
</style>
<title>Weather</title>
</head>
<body>
<?php

require_once("lib/my_locale.php");
require_once("../lib/xml2array.php");
putenv("TZ=".MY_TIMEZONE);

echo "<meta http-equiv=refresh content=\"2700\">";   # refresh this every 45 minutes

# ----------------------------------------
# load data

$restURL = "http://www.weather.gov/forecasts/xml/SOAP_server/ndfdXMLclient.php?whichClient=NDFDgenLatLonList&lat=&lon=&listLatLon=".MY_GEO_LAT."%2C".MY_GEO_LON."&lat1=&lon1=&lat2=&lon2=&resolutionSub=&listLat1=&listLon1=&listLat2=&listLon2=&resolutionList=&endPoint1Lat=&endPoint1Lon=&endPoint2Lat=&endPoint2Lon=&listEndPoint1Lat=&listEndPoint1Lon=&listEndPoint2Lat=&listEndPoint2Lon=&zipCodeList=&listZipCodeList=&centerPointLat=&centerPointLon=&distanceLat=&distanceLon=&resolutionSquare=&listCenterPointLat=&listCenterPointLon=&listDistanceLat=&listDistanceLon=&listResolutionSquare=&citiesLevel=&listCitiesLevel=&sector=&gmlListLatLon=&featureType=&requestedTime=&startTime=&endTime=&compType=&propertyName=&product=time-series&maxt=maxt&mint=mint&temp=temp&qpf=qpf&pop12=pop12&snow=snow&dew=dew&wspd=wspd&wdir=wdir&sky=sky&wx=wx&icons=icons&rh=rh&appt=appt&wwa=wwa&wgust=wgust&Submit=Submit";

$xml = xml2ary(file_get_contents($restURL));

$clim84URL = "http://cdo.ncdc.noaa.gov/climatenormals/clim84/".MY_CLIM84.".txt";
$clim84Raw = file_get_contents($clim84URL);

# ----------------------------------------
# parse data

$timeLayoutsArray = $xml['dwml']['_c']['data']['_c']['time-layout'];
$data             = $xml['dwml']['_c']['data']['_c']['parameters']['_c'];

for ($i = 0; $i < count($timeLayoutsArray); $i++) {
  $keyName              = $timeLayoutsArray[$i]['_c']['layout-key']['_v'];
  $timeLayout[$keyName] = $timeLayoutsArray[$i]['_c']['start-valid-time'];
}

echo "<pre>";
#print_r($timeLayout['k-p24h-n7-1']);
echo "</pre>";

foreach ($data as $type => $datum) {
  #echo "<pre><b>".$type."</b> ";
  #print_r($datum);
  #echo "</pre>";
  if (!isset($datum[0])) { $datum[0] = $datum; }
  for ($i = 0; $i < count($datum); $i++) {
    
    $subtype = $datum[$i]['_a']['type'];
    # load time layout
    for ($j = 0; $j < count($timeLayout[$datum[$i]['_a']['time-layout']]); $j++) {
      $times[$j] = strtotime($timeLayout[$datum[$i]['_a']['time-layout']][$j]['_v']);
      #$times[$j] =           $timeLayout[$datum[$i]['_a']['time-layout']][$j]['_v'] ;
    }
    # assign data to time layout
    switch ($type) {
      case 'weather':
        for ($j = 0; $j < count($datum[$i]['_c']['weather-conditions']); $j++) {
          $info[$type][$subtype][$times[$j]] = $datum[$i]['_c']['weather-conditions'][$j]['_c']['value'];
        } break;
      case 'conditions-icon':
        for ($j = 0; $j < count($datum[$i]['_c']['icon-link']); $j++) {
          $info[$type][$subtype][$times[$j]] = $datum[$i]['_c']['icon-link'][$j]['_v'];
        } break;
      case 'hazards':
        for ($j = 0; $j < count($datum[$i]['_c']['hazard-conditions']); $j++) {
          $info[$type][$subtype][$times[$j]] = $datum[$i]['_c']['hazard-conditions'][$j]['_v'];
        } break;
      default:
        for ($j = 0; $j < count($datum[$i]['_c']['value']); $j++) {
          $info[$type][$subtype][$times[$j]] = $datum[$i]['_c']['value'][$j]['_v'];
        }
    } # switch()
    
  } # for($i)
} # foreach($data)


$periodTimes = array_merge( array_keys($info['temperature']['maximum']),array_keys($info['temperature']['minimum']) );
sort($periodTimes);


foreach ($periodTimes as $i => $periodTime) {
  
  if (isset($info['temperature']['maximum'][$periodTime])) {
    $forecast[$i]['temp'] = $info['temperature']['maximum'][$periodTime];
    $forecast[$i]['temp-type'] = "tempHigh";
  }
  else {
    $forecast[$i]['temp'] = $info['temperature']['minimum'][$periodTime];
    $forecast[$i]['temp-type'] = "tempLow";
  }
  
  $forecast[$i]['icon'] = $info['conditions-icon']['forecast-NWS'][$periodTime];
  
}

# deal with CLIM84 (normal temps)

$clim84Lines = explode("\n",$clim84Raw);

for ($m = 1; $m <= 12; $m++) {
  $normalMaxLine = $clim84Lines[$m+27];
  $normalMinLine = $clim84Lines[$m+10];
  for ($d = 1; $d <= 31; $d++) {
    $normalMaxArray[$m][$d] = substr($normalMaxLine,1+(3*$d),3);
    $normalMinArray[$m][$d] = substr($normalMinLine,1+(3*$d),3);
    if ($normalMaxArray[$m][$d] != "   ") { $normalMaxArray[$m][$d] = intval($normalMaxArray[$m][$d]); }
      else { unset($normalMaxArray[$m][$d]); }
    if ($normalMinArray[$m][$d] != "   ") { $normalMinArray[$m][$d] = intval($normalMinArray[$m][$d]); }
      else { unset($normalMinArray[$m][$d]); }
    
  }
}
# normals for 29 February aren't given, so use 28 February data
$normalMaxArray[2][29] = $normalMaxArray[2][28];
$normalMinArray[2][29] = $normalMinArray[2][28];

#echo "<pre>";
#print_r($clim84Lines);
#print_r($normalMaxArray);
#print_r($normalMinArray);
#echo "</pre>";

$normalMax = $normalMaxArray[date("n")][date("j")];
$normalMin = $normalMinArray[date("n")][date("j")];

#echo "<pre>";
#print_r($info);
#echo "</pre>";


# ----------------------------------------
# display data

echo "<table cellpadding=0 cellspacing=0 width=100% height=100%><tr><td align=center valign=middle>";

  echo "<table cellpadding=0 cellspacing=0 align=center valign=middle>";
    echo "<tr>\n\n";
      
      # occasionally the source data shows 15 periods...
      while (count($periodTimes) > 14) {
        unset($info['temperature']['maximum'][$periodTimes[0]]);
        unset($info['temperature']['minimum'][$periodTimes[0]]);
        unset($periodTimes[0]);
      }
      
      # --------------more data massaging--------------
      # load all forecast temps
      $allRelevantTemps = array_merge( $info['temperature']['maximum'],$info['temperature']['minimum'] );
      # add in the normals (so they still affect the scale if the forecast is WAY outside this range)
      $allRelevantTemps[] = $normalMax;
      $allRelevantTemps[] = $normalMin;
      
      # find the relevant range
      $maxRelevantTemp = max($allRelevantTemps);
      $minRelevantTemp = min($allRelevantTemps);
      # --------------data massaging done--------------
      
      $columnHeight = 400;
      $mincolumnLoc = 20;
      $maxcolumnLoc = 355;
      echo "<td align=center valign=middle width=70>";
        echo "<div id=dayName>&nbsp;</div>\n";
        echo "<img src=\"spacer.png\" width=70 height=93>";
        echo "<div style=\"width: 100%; height: ".$columnHeight."px; position: relative;\">";
          
          $columnLocMax = interpolate($minRelevantTemp,$maxRelevantTemp,$mincolumnLoc,$maxcolumnLoc,$normalMax);
          $columnLocMin = interpolate($minRelevantTemp,$maxRelevantTemp,$mincolumnLoc,$maxcolumnLoc,$normalMin);
          #$columnLocMid = ($columnLocMax + $columnLocMin) / 2;
          #echo "<div style=\"display: block; position: absolute; bottom: ".$columnLocMid."; right: 0; width: 100%; margin: 0;\" class=normals><center>Norms</center></div>";
          echo "<div style=\"display: block; position: absolute; bottom: ".$columnLocMax."; left: 0; width: 100%; padding-right: 905px;\" class=normalHigh><span>".temp($normalMax)."</span></div>";
          echo "<div style=\"display: block; position: absolute; bottom: ".$columnLocMin."; left: 0; width: 100%; padding-right: 905px;\" class=normalLow><span>".temp($normalMin)."</span></div>";
            
        echo "</div>";
      echo "</td>";
      
      echo "\n\n<!-- BEGIN FORECAST -->\n\n";
      
      # ... occasionally the source data only shows 13 periods
      if (count($periodTimes) < 14) {
        echo "<td align=center valign=middle width=36>";
          echo "<div id=dayName>&nbsp;</div>\n";
          echo "<img src=\"spacer.png\" width=36 height=93>\n";
          echo "<div style=\"width: 100%; height: ".$columnHeight."px; position: relative;\"></div>";
        echo "</td>\n\n";
      }
      
      # for each period...
      foreach ($periodTimes as $i => $periodTime) {
        if (date("Gi",$periodTime) < 0600 || date("Gi",$periodTime) >= 1800) { $periodType[$i] = "N"; }
          else { $periodType[$i] = "D"; }
        
        switch ($periodType[$i]) {
          case "D":  # daytime period
            echo "<!-- ".strtoupper(date("l, j F Y",$periodTime))." -->\n";
            echo "<td align=center valign=middle width=93 id=dayColumn>";
              echo "<div id=dayName";
                if (date("N",$periodTime) >= 6) { echo " class=weekend"; }
              echo " title=\"".date("D j M",$periodTime)."\">".date("D",$periodTime)."</div>\n";
              echo "<img src=\"".$forecast[$i]['icon']."\" width=93 height=93>\n";
          break;
          case "N":  # nighttime period
            echo "<!-- ".date("l",$periodTime)." night, ".date("j F Y",$periodTime)." -->\n";
            echo "<td align=center valign=middle width=36>";
              echo "<div id=dayName>&nbsp;</div>\n";
              echo "<img src=\"spacer.png\" width=36 height=93>\n";
          break;
        }
        
          echo "<div style=\"width: 100%; height: ".$columnHeight."px; position: relative;\">";
            
            $columnLoc = interpolate($minRelevantTemp,$maxRelevantTemp,$mincolumnLoc,$maxcolumnLoc,$forecast[$i]['temp']);
              echo "<div style=\"display: block; position: absolute; bottom: ".$columnLoc."; width: 250%; margin: 0 -75%;\"><span class=".$forecast[$i]['temp-type'].">".$forecast[$i]['temp']."</span></div>";
            
          echo "</div>";
        echo "</td>\n\n";
      } # ...for each period
      
      echo "<!-- END FORECAST -->\n\n";
      
    echo "</tr>";
    
    echo "\n\n<!-- BEGIN SUN DATA -->\n\n";
    
    echo "<tr height=55>";
      echo "<td colspan=1>&nbsp;</td>";
      echo "<td colspan=14 id=sunColumn>";
      
      for ($i = 0; $i <= 2; $i++) {
        $sunTimes[$i]['rise'] = date_sunrise(strtotime("+".$i." days",time()),SUNFUNCS_RET_TIMESTAMP,MY_GEO_LAT,MY_GEO_LON);
        $sunTimes[$i]['set'] = date_sunset(strtotime("+".$i." days",time()),SUNFUNCS_RET_TIMESTAMP,MY_GEO_LAT,MY_GEO_LON);
      }
      
      $j = 0;  # number of sun times shown thusfar
      $jMax = 3;  # max to show
      for ($i = 0; $i <= 2; $i++) {
          if (time() <= $sunTimes[$i]['rise'] + 60*90 && $j < $jMax) {  # quit showing 90 min after sunrise
            echo "<span class=sunriseDate>".date("D",$sunTimes[$i]['rise'])."</span><span class=sunriseTime>".date("H:i",$sunTimes[$i]['rise'])."</span>";
            $j++;
          }
          if (time() <= $sunTimes[$i]['set'] + 60*90 && $j < $jMax) {  # quit showing 90 min after sunset
            echo "<span class=sunsetDate>".date("D",$sunTimes[$i]['set'])."</span><span class=sunsetTime>".date("H:i",$sunTimes[$i]['set'])."</span>";
            $j++;
          }
      }
      
      echo "</td>";
    echo "</tr>";
    
    echo "\n\n<!-- END SUN DATA -->\n\n";
    
  echo "</table>";

echo "<table>";



# FUNCTIONS ============================================================

function temp($t) {
  return $t."&deg;";
}
function interpolate($src1,$src2,$plot1,$plot2,$srcRef) {
    # will "erroneously" EXTRApolate if given a reference outside the bounds, so be careful!
    $srcDiff = $src2 - $src1;
    $plotDiff = $plot2 - $plot1;
    
    $frac = ($srcRef - $src1) / $srcDiff;
    $plotRef = $plot1 + ($frac * $plotDiff);
    
    return $plotRef;
  }


?>

</body>
</html>
