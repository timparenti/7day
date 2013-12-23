<html>
<head>
<style>
  body { background-color: #333; font-family: Arial; color: #fff; margin: 0; }
  td { line-height: 1em; }
  /*abbr { border-bottom: 0; }*/
  
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
  .sunDate { color: #999; font-size: 20pt; text-transform: uppercase; margin: 0 0.2em 0 0.5em; text-shadow: 0.03em 0.03em 0.03em #222; }
  .sunriseTime { color: #f93; font-weight: bold; font-size: 24pt; margin-right: 0.2em; text-shadow: 0.03em 0.03em 0.03em #222; line-height: 0.9em; }
  .sunsetTime { color: #39f; font-weight: bold; font-size: 24pt; margin-right: 0.2em; text-shadow: 0.03em 0.03em 0.03em #222; line-height: 0.9em; }
  .sunDayLength { color: #ccc; font-weight: bold; font-size: 16pt; margin-right: 1.5em; text-shadow: 0.03em 0.03em 0.03em #222; }
  
  #copyright, #copyright a { color: #666; font-size: 8.5pt; font-family: Tahoma, Arial; line-height: 1.1em; }
  #copyright a:hover { color: #ccc; }
  .paramControl { margin: 0 0.5em; }
  .buildDate { font-size: 7pt; }
  
</style>
<title>7-Day Weather Forecast</title>
</head>
<body>
<?php

require_once("locale.php");
require_once("lib/xml2array.php");
date_default_timezone_set(MY_TIMEZONE);

echo "<meta http-equiv=refresh content=\"2700\">";   # refresh this every 45 minutes

# ----------------------------------------
# load data

$restURL = "http://www.weather.gov/forecasts/xml/SOAP_server/ndfdXMLclient.php?whichClient=NDFDgenLatLonList&lat=&lon=&listLatLon=".MY_GEO_LAT."%2C".MY_GEO_LON."&lat1=&lon1=&lat2=&lon2=&resolutionSub=&listLat1=&listLon1=&listLat2=&listLon2=&resolutionList=&endPoint1Lat=&endPoint1Lon=&endPoint2Lat=&endPoint2Lon=&listEndPoint1Lat=&listEndPoint1Lon=&listEndPoint2Lat=&listEndPoint2Lon=&zipCodeList=&listZipCodeList=&centerPointLat=&centerPointLon=&distanceLat=&distanceLon=&resolutionSquare=&listCenterPointLat=&listCenterPointLon=&listDistanceLat=&listDistanceLon=&listResolutionSquare=&citiesLevel=&listCitiesLevel=&sector=&gmlListLatLon=&featureType=&requestedTime=&startTime=&endTime=&compType=&propertyName=&product=time-series&maxt=maxt&mint=mint&temp=temp&qpf=qpf&pop12=pop12&snow=snow&dew=dew&wspd=wspd&wdir=wdir&sky=sky&wx=wx&icons=icons&rh=rh&appt=appt&wwa=wwa&wgust=wgust&Submit=Submit";

$restData = file_get_contents($restURL);
$xml = xml2ary($restData);

$clim84URL = "http://cdo.ncdc.noaa.gov/climatenormals/clim84/".MY_CLIM84.".txt";
$clim84Raw = file_get_contents($clim84URL);

# ----------------------------------------
# parse data

$dataTimestamp    = strtotime($xml['dwml']['_c']['head']['_c']['product']['_c']['creation-date']['_v']);
$timeLayoutsArray = $xml['dwml']['_c']['data']['_c']['time-layout'];
$data             = $xml['dwml']['_c']['data']['_c']['parameters']['_c'];

for ($i = 0; $i < count($timeLayoutsArray); $i++) {
  $keyName              = $timeLayoutsArray[$i]['_c']['layout-key']['_v'];
  $timeLayout[$keyName] = $timeLayoutsArray[$i]['_c']['start-valid-time'];
}

#echo "<pre>";
#print_r($timeLayout['k-p24h-n7-1']);
#echo "</pre>";

foreach ($data as $type => $datum) {
  if ($type != "temperature" && $type != "weather" && $type != "conditions-icon") {
    # Not doing anything with the other data for now
    continue;
  }
  #echo "<pre><b>".$type."</b> ";
  #print_r($datum);
  #echo "</pre>";
  if (!isset($datum[0])) { $datum[0] = $datum; }
  for ($i = 0; $i < count($datum); $i++) {
    
    # load time layout
    if (isset($datum[$i]) && isset($timeLayout[$datum[$i]['_a']['time-layout']])) {
      for ($j = 0; $j < count($timeLayout[$datum[$i]['_a']['time-layout']]); $j++) {
        $times[$j] = strtotime($timeLayout[$datum[$i]['_a']['time-layout']][$j]['_v']);
        #$times[$j] =           $timeLayout[$datum[$i]['_a']['time-layout']][$j]['_v'] ;
      }
    }
    # assign data to time layout
    if (isset($datum[$i]['_a']['type'])) {
      $subtype = $datum[$i]['_a']['type'];
      
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
    }
    
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
  
  if (isset($info['conditions-icon']['forecast-NWS'][$periodTime])) {
    $forecast[$i]['icon'] = $info['conditions-icon']['forecast-NWS'][$periodTime];
  }
  elseif ($i == 0) {
    # time for first period has passed, so use first available icon
    $iconTimes = array_keys($info['conditions-icon']['forecast-NWS']);
    $forecast[$i]['icon'] = $info['conditions-icon']['forecast-NWS'][$iconTimes[0]];
  }
  
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

if (!isset($_GET['units'])) {
  $_GET['units'] = 'F';
}
elseif ($_GET['units'] != 'F' && $_GET['units'] != 'C') {
  exit("Unsupported units...");
}

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
          echo "<div style=\"display: block; position: absolute; bottom: ".$columnLocMax."; left: 0; width: 100%; padding-right: 905px;\" class=normalHigh><span title=\"Normal High for ".date("j M")."... ".displayTempAlt($normalMax)."\">".displayTempDeg($normalMax)."</span></div>";
          echo "<div style=\"display: block; position: absolute; bottom: ".$columnLocMin."; left: 0; width: 100%; padding-right: 905px;\" class=normalLow><span title=\"Normal Low for ".date("j M")."... ".displayTempAlt($normalMin)."\">".displayTempDeg($normalMin)."</span></div>";
            
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
            
            switch ($periodType[$i]) {
              case "D":  # daytime period
                $dateName = date("D j M",$periodTime);
                echo "<div style=\"display: block; position: absolute; bottom: ".$columnLoc."; width: 250%; margin: 0 -75%;\"><span class=".$forecast[$i]['temp-type']." title=\"High for ".$dateName."... ".displayTempAlt($forecast[$i]['temp'])."\">".displayTemp($forecast[$i]['temp'])."</span></div>";
              break;   
              case "N":  # nighttime period
                $dateName = date("D j M",$periodTime);
                echo "<div style=\"display: block; position: absolute; bottom: ".$columnLoc."; width: 250%; margin: 0 -75%;\"><span class=".$forecast[$i]['temp-type']." title=\"Low for ".$dateName."... ".displayTempAlt($forecast[$i]['temp'])."\">".displayTemp($forecast[$i]['temp'])."</span></div>";
              break;
            }
          echo "</div>";
          
        echo "</td>\n\n";
      } # ...for each period
      
      echo "<!-- END FORECAST -->\n\n";
      
    echo "</tr>";
    
    echo "\n\n<!-- BEGIN SUN DATA -->\n\n";
    
    echo "<tr height=65>";
      echo "<td colspan=1>&nbsp;</td>";
      echo "<td colspan=14 id=sunColumn>";
      
      # generate the data...
      for ($i = 0; $i <= 2; $i++) {
        $sunTimes[$i]['rise'] = date_sunrise(strtotime("+".$i." days",time()),SUNFUNCS_RET_TIMESTAMP,MY_GEO_LAT,MY_GEO_LON);
        $sunTimes[$i]['set'] = date_sunset(strtotime("+".$i." days",time()),SUNFUNCS_RET_TIMESTAMP,MY_GEO_LAT,MY_GEO_LON);
        $sunTimes[$i]['dayLength'] = $sunTimes[$i]['set'] - $sunTimes[$i]['rise'];
      }
      
      # start showing data...
      $j = 0;  # number of sun days shown thusfar
      $jMax = 2;  # max to show
      $timeDelay = 60*90;  # quit showing 90 min after sun event
      
      echo "<table><tr>";
      # for each day...
      for ($i = 0; $i <= 2; $i++) {
        if (time() <= $sunTimes[$i]['set'] + $timeDelay && $j < $jMax) {
          echo "\n\n<!-- ".date("l, j F Y",$sunTimes[$i]['rise'])." -->\n";
          echo "<td valign=middle>";
            echo "<span class=sunDate title=\"".date("D j M",$sunTimes[$i]['rise'])."\">".date("D",$sunTimes[$i]['rise'])."</span>";
          echo "</td>";
          echo "<td valign=middle>";
            echo "<span class=sunriseTime title=\"".date("D j M, H:i:s T",$sunTimes[$i]['rise'])."\">".date("H:i",$sunTimes[$i]['rise'])."</span>";
            echo "<br>";
            echo "<span class=sunsetTime title=\"".date("D j M, H:i:s T",$sunTimes[$i]['set'])."\">".date("H:i",$sunTimes[$i]['set'])."</span>";
          echo "</td>";
          echo "<td valign=middle>";
            echo "<span class=sunDayLength title=\"Day length for ".date("D j M",$sunTimes[$i]['rise'])."... ".gmdate("G \h, i \m, s \s",$sunTimes[$i]['dayLength'])."\">".gmdate("G:i\'s\"",$sunTimes[$i]['dayLength'])."</span>";
          echo "</td>";
          $j++;
        }
      }
      echo "</tr></table>";
      
      echo "</td>";
    echo "</tr>";
    
    echo "\n\n<!-- END SUN DATA -->\n\n";
    
  echo "</table>";

  # =======================================================================
  # =======================================================================
  echo "<span id=copyright>";
    define("LAUNCH_YEAR",2010);
    echo "Forecast data and weather icons from the <a href=\"http://www.weather.gov/forecasts/xml/SOAP_server/ndfdXML.htm\" target=\"nws_ndfd\">National Digital Forecast Database</a>, courtesy of the <a href=\"http://www.weather.gov/\" target=\"nws_main\">National Weather Service</a>.";
    echo "  ";
    echo "Forecast data presented is for <b><abbr title=\"".MY_GEO_LAT.", ".MY_GEO_LON."\">".MY_LOCALE."</abbr>,</b> loaded ".date("D j M Y, H:i T",$dataTimestamp)." (".gmdate("d/Hi",$dataTimestamp)."Z).";
    echo "<br>";
    echo "Seasonal normal temperatures courtesy of the <a href=\"http://www.ncdc.noaa.gov/oa/ncdc.html\" target=\"ncdc_main\">National Climatic Data Center</a>.";
    echo "  ";
    echo "Sun data calculated from geographical location.";
    echo "  ";
    echo "Graphical representation <b>&copy;";
    if (date("Y") != LAUNCH_YEAR) { echo LAUNCH_YEAR."&ndash;".date("Y"); } else { echo LAUNCH_YEAR; }
    echo ",</b> <a href=\"http://www.timparenti.com/\">Timothy J Parenti</a>; all rights reserved.";
    echo "<br>";
    echo "<span class=paramControl><b>Location:</b> <a href=\"./?zip=15213&units=".$_GET['units']."\">Pittsburgh, PA</a> | <a href=\"./?zip=16417&units=".$_GET['units']."\">Girard, PA</a></span>";
    echo "<span class=paramControl><b>Units:</b> <a href=\"./?zip=".$_GET['zip']."&units=F\">&deg;F</a> | <a href=\"./?zip=".$_GET['zip']."&units=C\">&deg;C</a></span>";
    echo "<div class=buildDate>rev. ".date("Y-m-d H:i:s T",filemtime("index.php"))."</div>";
  echo "</span>";

echo "<table>";



# FUNCTIONS ============================================================

function formatTempNum($t) {
  if ($t >= 0) {
    return $t;
  }
  else {
    return "&ndash;".abs($t);
  }
}

function formatTempDeg($t) {
  return formatTempNum($t)."&deg;";
}

function formatTempFC($f) {
  return formatTempDeg($f)."F (".formatTempDeg(convertTempFC($f))."C)";
}

function convertTempFC($f) {
  $c = round(($f - 32) / 1.8, 0);
  return $c;
}

function displayTemp($f, $degreeSymbol=false, $alternateUnits=false) {
  switch ($_GET['units']) {
    case 'C':
      $temp['main']['value'] = convertTempFC($f);
      $temp['main']['units'] = 'C';
      $temp['alt']['value'] = $f;
      $temp['alt']['units'] = 'F';
      break;
    case 'F':
    default:
      $temp['main']['value'] = $f;
      $temp['main']['units'] = 'F';
      $temp['alt']['value'] = convertTempFC($f);
      $temp['alt']['units'] = 'C';
      break;
  }
  
  # Use minus sign for negative values
  if ($temp['main']['value'] < 0) {
    $temp['main']['value'] = "&minus;".abs($temp['main']['value']);
  }
  if ($temp['alt']['value'] < 0) {
    $temp['alt']['value'] = "&minus;".abs($temp['alt']['value']);
  }
  
  if (!$degreeSymbol && !$alternateUnits) {
    return $temp['main']['value'];
  }
  if ($degreeSymbol && !$alternateUnits) {
    return $temp['main']['value']."&deg;";
  }
  if ($alternateUnits) {
    return $temp['main']['value']." &deg;".$temp['main']['units']." (".$temp['alt']['value']." &deg;".$temp['alt']['units'].")";
  }
}

function displayTempDeg($f) {
  return displayTemp($f, true, false);
}

function displayTempAlt($f) {
  return displayTemp($f, true, true);
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
