<?php

require "resources/config.php";
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
$raw = json_decode(file_get_contents('php://input'));
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
    case 'addStudent':
      echo addStudent();
      break;
    case 'updateStudent':
      echo updateStudent();
      break;
    case 'getSections':
      echo getSections();
      break;
    default :
      echo json_encode(array(
        'status'  => false,
        'data'    => array(),
        'message' => 'Invalid method'
      ));
      break;
  }
}else if($raw){
    if($raw->method == 'takeAttendance'){
       echo takeAttendance($raw);
    }else{
    echo json_encode(array(
      'status'  => false,
      'data'    => array(),
      'message' => 'Invalid method'
      ));
    }	
  }
  
  
  


?>
