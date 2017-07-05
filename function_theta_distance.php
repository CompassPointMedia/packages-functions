<?php
//Height and Width box of the contiguous 48 states
$contigHeight=1590;
$contigWidth=2700;

$widthMilesPerDegree=69.16862789;
$widthDegreesPerMile=0.014457421;
$heightMilesPerDegree=47.22442797;
$heightDegreesPerMile=0.021175481;
	

//latitude is the height, longitude is the width
$functionVersions['theta_distance']=1.00;
function theta_distance($lat1, $lon1, $lat2, $lon2, $unit='M') { 

  $theta = $lon1 - $lon2; 
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
  $dist = acos($dist); 
  $dist = rad2deg($dist); 
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

  if ($unit == "K") {
    return ($miles * 1.609344); 
  } else if ($unit == "N") {
      return ($miles * 0.8684);
    } else {
        return $miles;
      }
}
?>