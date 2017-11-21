<?php

require "resources/config.php";
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
if(isset($_POST['method']) && !empty($_POST['method'])){
  $method = $_POST['method'];
  switch($method){
    case 'registration':
      echo registration();
      break;
    case 'login':
      echo login();
      break;
    case 'getChildList':
      echo getChildList();
      break;
    case 'addChild':
      echo addChild();
      break;
    case 'getChildVaccineList':
      echo getChildVaccineList();
      break;
    case 'updateVaccineChart':
      echo updateVaccineChart();
      break;
    case 'addVaccine':
      echo addVaccine();
      break;
    case 'updateVaccine':
      echo updateVaccine();
      break;
    case 'getVaccineList':
      echo getVaccineList();
      break;
    case 'getConsumptionByDate':
      echo getConsumptionByDate();
      break;
    case 'getRecentChildVaccines':
      echo getRecentChildVaccines();
      break;
    case 'getUpcomingChildVaccineList':
      echo getUpcomingChildVaccineList();
      break;
    default :
      echo json_encode(array(
        'status'  => false,
        'data'    => array(),
        'message' => 'Invalid method'
      ));
      break;
  }
}
  
?>
