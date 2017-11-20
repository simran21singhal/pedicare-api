<?php

/********************************HELPER FUNCTIONS***********************************/

function query($sql){
  global $connection;
  return mysqli_query($connection, $sql);
}

function autocommit($f){
  global $connection;
  mysqli_autocommit($connection,$f);
}

function commit(){
  global $connection;
  mysqli_commit($connection);
}

function rollback(){
  global $connection;
  mysqli_rollback($connection);
}

function rows($sql){
  return mysqli_num_rows($sql);
}

function affectedRows(){
  global $connection;
  return mysqli_affected_rows($connection);
}

function lastId(){
  global $connection;
  return mysqli_insert_id($connection);
}

function confirm($result){
  global $connection;
  $data['message'] = 'Success.';
  $data['status'] = true;
  if(!$result){
    $data['message'] = "QUERY FAILED: ".mysqli_error($connection);
    $data['status'] = false;
    return $data;
  }else{
    return $data;
  }
}

function escape_string($string){
  global $connection;
  return mysqli_real_escape_string($connection, $string);
}

function fetch_array($result){
  return mysqli_fetch_array($result);
}

function checkToken($reg_id, $token) {
  $status = false;
  $message = '';
  $data = array();
  $query = query("SELECT token from tokens where reg_id = {$reg_id} order by timestamp desc limit 1");
  $query_status = confirm($query);
  if($query_status['status']){
    if(rows($query)) {
      $db_token = fetch_array($query)['token'];
      if($token == $db_token) {
        $message = 'Token found';
        $status = true;
      }else{
        $message = 'Token expired. Please login again.';
      }
    }else{
      $message = 'Token not found.';
    }
  }else{
    $message = $query_status['message'];
  }
  return array(
    'status'  => $status,
    'data'    => $data,
    'message' => $message
  );
}


function datediffInWeeks($date){
  // if($date1 > $date2) return datediffInWeeks($date2, $date1);
  $first = DateTime::createFromFormat('d/m/Y', $date);
  $second = DateTime::createFromFormat('d/m/Y', date('d/m/Y'));
  return floor($first->diff($second)->days/7);
}

// echo datediffInWeeks('17/10/2017');

/********************************* API FUNCTIONS ************************************/

function registration() {
  $status = false;
  $message = '';
  $data = array();
  if(isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['type'])) {
    $name = escape_string($_POST['name']);
    $email = escape_string($_POST['email']);
    $password = escape_string($_POST['password']);
    $type = escape_string($_POST['type']);
    if(!empty($name) && !empty($email) && !empty($password) && !empty($type)){
      $password = md5($password);
      $query = query("SELECT email from registration where email = '{$email}'");
      $query_status = confirm($query);
      if($query_status['status']){
        if(rows($query) == 0){
          $query = query("INSERT INTO registration(name, email, password, type) VALUES('{$name}', '{$email}', '{$password}', '{$type}')");
          $query_status = confirm($query);
          if($query_status['status']){
            $message = 'Account successfully created';
            $status = true;
          }else{
            $message = $query_status['message'];
          }
        }else{
          $message = 'Email is already registered!';
        }
      }else{
        $message = $query_status['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  if(count($data)){
    return json_encode(array(
      'status'  => $status,
      'data'    => $data,
      'message' => $message
    ));
  }else{
    return json_encode(array(
      'status'  => $status,
      'message' => $message
    ));
  }
}

function login() {
  $status = false;
  $message = '';
  $data = array();
  if(isset($_POST['password']) && isset($_POST['email'])) {
    $email = escape_string($_POST['email']);
    $password = escape_string($_POST['password']);
    if(!empty($email) && !empty($password)){
      $password = md5($password);
      $query = query("SELECT email from registration where email = '{$email}'");
      $query_status = confirm($query);
      if($query_status['status']){
        if(rows($query)){
          $query = query("SELECT id, type from registration where email = '{$email}' AND password = '{$password}'");
          $query_status = confirm($query);
          if($query_status['status']){
            if(rows($query)){
              $row = fetch_array($query);
              $id = $row['id']; 
              $type = $row['type'];              
              $query = query("SELECT * from child where parent_id = {$id}"); 
              $query_status = confirm($query);
              if($query_status['status']){
                if(rows($query)){
                  $data['child'] = array();                  
                  while($row = fetch_array($query)){
                    $name = $row['name'];
                    $child_id = $row['id'];
                    $dob = $row['dob'];
                    $gender = $row['gender'];
                    array_push($data['child'], array(
                      'child_id' => $child_id,
                      'name' => $name,
                      'dob' => $dob,
                      'gender' => $gender
                    ));
                  }
                }else{
                  // $data['child'] = array();                                    
                }
                $token = bin2hex(openssl_random_pseudo_bytes(16));
                $query = query("INSERT INTO tokens(reg_id, token) VALUES({$id}, '{$token}')");
                $query_status = confirm($query);
                if($query_status['status']){
                  $message = 'Login Successfull';
                  $status = true;
                  $data['reg_id'] = $id;
                  $data['token'] = $token;
                  $data['type'] = $type;
                }else{
                  $message = 'Something went wrong.';
                }
              }else{
                $message = $query_status['message'];                
              }             
            }else{
              $message = 'Wrong credentials.';
            }            
          }else{
            $message = $query_status['message'];
          }
        }else{
          $message = 'User not found.';
        }
      }else{
        $message = $query_status['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  if(count($data)){
    return json_encode(array(
      'status'  => $status,
      'data'    => $data,
      'message' => $message
    ));
  }else{
    return json_encode(array(
      'status'  => $status,
      'message' => $message
    ));
  }
}

function getChildList() {
  $status = false;
  $message = '';
  $data = array();
  $parent_id = '';
  if(isset($_POST['reg_id']) && isset($_POST['token'])) {
    $token = escape_string($_POST['token']);
    $reg_id = escape_string($_POST['reg_id']);    
    if(isset($_POST['parent_id'])){
      $parent_id = $_POST['parent_id'];
      if(!empty($parent_id)){
        $parent_id = escape_string($_POST['parent_id']);
      }else{
        return json_encode(array(
          'status'  => $status,
          'message' => "Parent id is empty."
        ));
      }
    }else{
      $parent_id = $reg_id;     
    }
    if(!empty($reg_id) && !empty($token)){
      $res = checkToken($reg_id, $token);
      if($res['status']){
        $query = query("SELECT * from child where parent_id = {$parent_id}");
        $query_status = confirm($query);
        if($query_status['status']){
          if(rows($query)){                  
            while($row = fetch_array($query)){
              $name = $row['name'];
              $child_id = $row['id'];
              $dob = $row['dob'];
              $gender = $row['gender'];
              array_push($data, array(
                'child_id' => $child_id,
                'name' => $name,
                'dob' => $dob,
                'gender' => $gender
              ));
            }
            $message = rows($query)." child found.";
            $status = true;
          }else{
            $message = 'No child found.';
          }
        }else{
          $message = $query_status['message'];
        }
      }else{
        $message = $res['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  if(count($data)){
    return json_encode(array(
      'status'  => $status,
      'data'    => $data,
      'message' => $message
    ));
  }else{
    return json_encode(array(
      'status'  => $status,
      'message' => $message
    ));
  }
}

function addChild(){
  $status = false;
  $message = '';
  $data = array();
  $count = 0;
  $f = true;
  if(isset($_POST['reg_id']) && isset($_POST['token']) && isset($_POST['name']) && isset($_POST['dob']) && isset($_POST['gender'])) {
    $token = escape_string($_POST['token']);
    $reg_id = escape_string($_POST['reg_id']);
    $name = escape_string($_POST['name']);
    $dob = escape_string($_POST['dob']);
    $gender = escape_string($_POST['gender']);
    if(!empty($reg_id) && !empty($token) && !empty($name) && !empty($dob) && !empty($gender)){
      $res = checkToken($reg_id, $token);
      if($res['status']){
        autocommit(false);        
        $query = query("INSERT into child(parent_id, name, dob, gender) VALUES({$reg_id}, '{$name}', '{$dob}', '{$gender}')");
        $query_status = confirm($query);
        if($query_status['status']){
          if(affectedRows()){
            $child_id = lastId();
            $child_age_in_weeks = datediffInWeeks($dob);
            $query = query("SELECT id from vaccines where duration >= {$child_age_in_weeks}");
            $query_status = confirm($query);
            if($query_status['status']){
              if(rows($query)){
                while($row = fetch_array($query)){
                  $vaccine_id = $row['id'];
                  $query1 = query("INSERT INTO child_vaccine (`child_id`, `vaccine_id`, `given_on`, `due_date`) VALUES($child_id, {$vaccine_id}, 'dd/mm/yyyy', 'dd/mm/yyyy')");
                  $query1_status = confirm($query1);
                  if($query1_status['status']){
                    if(affectedRows() == 0){
                      $f = false;
                      break;
                    }else{
                      ++$count;
                    }
                  }else{
                    $f = false;
                    break;
                  }
                } //while end
                if($f){
                  commit();
                  $status = true;
                  $message = 'Child added successfully.';
                }else{
                  rollback();
                  $message = 'There was some error. Try again';
                }
              }else{
                //If no vaccines found.
                commit();
                $status = true;
                $message = 'Child added successfully.';
              }
            }else{
              rollback();
              $message = 'Something went wrong. Please try again.';
            }
          }else{
            $message = 'Something went wrong. Please try again.';
          }
        }else{
          $message = $query_status['message'];
        }
      }else{
        $message = $res['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  autocommit(true);          
  if(count($data)){
    return json_encode(array(
      'status'  => $status,
      'data'    => $data,
      'message' => $message
    ));
  }else{
    return json_encode(array(
      'status'  => $status,
      'message' => $message
    ));
  }
}

function getChildVaccineList() {
  $status = false;
  $message = '';
  $data = array();
  
  if(isset($_POST['reg_id']) && isset($_POST['child_id']) && isset($_POST['token']) && isset($_POST['all'])) {
    $token = escape_string($_POST['token']);
    $child_id = escape_string($_POST['child_id']); 
    $reg_id = escape_string($_POST['reg_id']); 
    $all = escape_string($_POST['all']);
    $parent_id = $reg_id;
    if(isset($_POST['parent_id'])){
      $parent_id = $_POST['parent_id'];
      if(!empty($parent_id)){
        $parent_id = escape_string($_POST['parent_id']);
      }else{
        return json_encode(array(
          'status'  => $status,
          'message' => "Parent id is empty."
        ));
      }
    }
    if(!empty($reg_id) && !empty($token) && !empty($child_id) && !empty($all)){
      $res = checkToken($reg_id, $token);
      if($res['status']){
        $query = query("SELECT id from child where parent_id = {$parent_id} AND id = {$child_id}");
        $query_status = confirm($query);
        if($query_status['status']){
          if(rows($query)){
            if($all == "true"){
              $query = query("SELECT v.name, c.given_on, c.flag, c.due_date, c.vaccine_id FROM `child_vaccine` as c inner join vaccines as v WHERE v.id = c.vaccine_id AND c.child_id = {$child_id}");
              $query_status = confirm($query);
            }else{
              $query = query("SELECT v.name, c.given_on, c.due_date, c.vaccine_id, c.flag FROM `child_vaccine` as c inner join vaccines as v WHERE v.id = c.vaccine_id AND c.child_id = {$child_id} AND flag = 0");
              $query_status = confirm($query);
            }
            if($query_status['status']){
              if(rows($query)){
                $status = true;
                $message = "Total ".rows($query)." vaccine/s found.";
                while($row = fetch_array($query)){
                  $name = $row['name'];
                  $given_on = $row['given_on'];
                  $due_date = $row['due_date'];
                  $vaccine_id = $row['vaccine_id'];
                  $flag = $row['flag'];
                  array_push($data, array(
                    'name' => $name,
                    'given_on' => $given_on,
                    'due_date' => $due_date,
                    'vaccine_id' => $vaccine_id,
                    'flag' => $flag
                  ));
                }
              }else{
                $message = 'No vaccines found';
              }
            }else{
              $message = $query_status['message'];
            }
          }else{
            $message = 'Child not found.';
          }
        }else{
          $message = $query_status['message'];
        }
      }else{
        $message = $res['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  if(count($data)){
    return json_encode(array(
      'status'  => $status,
      'data'    => $data,
      'message' => $message
    ));
  }else{
    return json_encode(array(
      'status'  => $status,
      'message' => $message
    ));
  }
}

function updateVaccineChart(){
  $status = false;
  $message = '';
  $data = array();
  if(isset($_POST['child_id']) && isset($_POST['vaccine_id']) && isset($_POST['token']) && isset($_POST['reg_id']) && isset($_POST['given_on']) && isset($_POST['due_date'])) {
    $token = escape_string($_POST['token']);
    $vaccine_id = escape_string($_POST['vaccine_id']); 
    $reg_id = escape_string($_POST['reg_id']);
    $child_id = escape_string($_POST['child_id']);
    $given_on = escape_string($_POST['given_on']);
    $due_date = escape_string($_POST['due_date']);    
    if(!empty($token) && !empty($vaccine_id) && !empty($child_id) && !empty($reg_id) && !empty($given_on) && !empty($due_date)){
      $res = checkToken($reg_id, $token);
      if($res['status']){
        $query = query("SELECT id from child where parent_id = {$reg_id} AND id = {$child_id}");
        $query_status = confirm($query);
        if($query_status['status']){
          if(rows($query)){
            $query = query("UPDATE child_vaccine set given_on = '{$given_on}', due_date = '{$due_date}', flag = 1 WHERE child_id = {$child_id} AND vaccine_id = {$vaccine_id}");
            $query_status = confirm($query);
            if($query_status['status']){
              if(affectedRows()){
                $status = true;
                $message = "Vaccine chart updated successfully.";
              }else{
                $message = 'Either vaccine or child not found.';
              }
            }else{
              $message = $query_status['message'];
            }
          }else{
            $message = 'Child not found.';            
          }
        }else{
          $message = $query_status['message'];          
        }
      }else{
        $message = $res['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  if(count($data)){
    return json_encode(array(
      'status'  => $status,
      'data'    => $data,
      'message' => $message
    ));
  }else{
    return json_encode(array(
      'status'  => $status,
      'message' => $message
    ));
  }
}

function addVaccines(){
  $status = false;
  $message = '';
  $data = array();
  if(isset($_POST['child_id']) && isset($_POST['vaccine_id']) && isset($_POST['token']) && isset($_POST['reg_id']) && isset($_POST['given_on']) && isset($_POST['due_date'])) {
    $token = escape_string($_POST['token']);
    $vaccine_id = escape_string($_POST['vaccine_id']); 
    $reg_id = escape_string($_POST['reg_id']);
    $child_id = escape_string($_POST['child_id']);
    $given_on = escape_string($_POST['given_on']);
    $due_date = escape_string($_POST['due_date']);    
    if(!empty($token) && !empty($vaccine_id) && !empty($child_id) && !empty($reg_id) && !empty($given_on) && !empty($due_date)){
      $res = checkToken($reg_id, $token);
      if($res['status']){
        $query = query("UPDATE child_vaccine set given_on = '{$given_on}', due_date = '{$due_date}' WHERE child_id = {$child_id} AND vaccine_id = {$vaccine_id}");
        $query_status = confirm($query);
        if($query_status['status']){
          if(affectedRows()){
            $status = true;
            $message = "Vaccine chart updated successfully.";
          }else{
            $message = 'Either vaccine or child not found.';
          }
        }else{
          $message = $query_status['message'];
        }
      }else{
        $message = $res['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  if(count($data)){
    return json_encode(array(
      'status'  => $status,
      'data'    => $data,
      'message' => $message
    ));
  }else{
    return json_encode(array(
      'status'  => $status,
      'message' => $message
    ));
  }
}

function addStudent(){
  $status = false;
  $message = '';
  $data = array();
  if(isset($_POST['t_id']) && isset($_POST['token']) && isset($_POST['enroll_no']) && isset($_POST['section_id']) && isset($_POST['name']) && isset($_POST['mac_id'])) {
    $token = escape_string($_POST['token']);
    $t_id = escape_string($_POST['t_id']);
    $name = escape_string($_POST['name']);
    $section_id = escape_string($_POST['section_id']);
    $mac_id = escape_string($_POST['mac_id']);
    $enroll_no = escape_string($_POST['enroll_no']);
    if(!empty($t_id) && !empty($token) && !empty($name) && !empty($mac_id) && !empty($enroll_no) && !empty($section_id)){
      $res = checkToken($t_id, $token);
      if($res['status']){
      $query = query("SELECT section_id from sections where section_id = {$section_id}");
        $query_status = confirm($query);
        if($query_status['status']){
          if(rows($query)){
            $query = query("INSERT INTO students(enroll_no, name, mac_id, section_id) VALUES('{$enroll_no}', '{$name}', '{$mac_id}', {$section_id})");
            $query_status = confirm($query);
            if($query_status['status']){
              if(affectedRows()){
                $status = true;
                $message = 'Student details are added.';
              }else{
                $message = 'Something went wrong, Please try again.';
              }
            }else{
              $message = $query_status['message'];
            }
          }else{
            $message = 'Section not found.';
          }
        }else{
          $message = $query_status['message'];
        }
      }else{
        $message = $res['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  return json_encode(array(
    'status'  => $status,
    'data'    => $data,
    'message' => $message
  ));
}


function getSections(){
  $status = false;
  $message = '';
  $data = array();
  if(isset($_POST['t_id']) && isset($_POST['token'])) {
    $token = escape_string($_POST['token']);
    $t_id = escape_string($_POST['t_id']);
    if(!empty($t_id) && !empty($token)){
      $res = checkToken($t_id, $token);
      if($res['status']){
        $query = query("SELECT sec.name, sec.year, sec.section_id, b.branch_name from sections as sec inner join branches as b where sec.b_id = b.b_id");
        $query_status = confirm($query);
        if($query_status['status']){
          if(rows($query)){
            $status = true;
            $message = "Total ".rows($query)." section/s found.";
            while($row = fetch_array($query)){
              $name = $row['name'];
              $branch_name = $row['branch_name'];
              $year = $row['year'];
              $section_id = $row['section_id'];
              array_push($data, array(
                'section_name' => $name,
                'branch_name' => $branch_name,
                'year' => $year,
                'section_id' => $section_id
              ));
            }
          }else{
            $message = 'No sections are found.';
          }
        }else{
          $message = $query_status['message'];
        }
      }else{
        $message = $res['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  return json_encode(array(
    'status'  => $status,
    'data'    => $data,
    'message' => $message
  ));
}

function updateStudent(){
  $status = false;
  $message = '';
  $data = array();
  if(isset($_POST['t_id']) && isset($_POST['token']) && isset($_POST['enroll_no']) && isset($_POST['section_id']) && isset($_POST['name']) && isset($_POST['mac_id'])) {
    $token = escape_string($_POST['token']);
    $t_id = escape_string($_POST['t_id']);
    $name = escape_string($_POST['name']);
    $section_id = escape_string($_POST['section_id']);
    $mac_id = strtoupper(escape_string($_POST['mac_id']));
    $enroll_no = escape_string($_POST['enroll_no']);
    if(!empty($t_id) && !empty($token) && !empty($name) && !empty($mac_id) && !empty($enroll_no) && !empty($section_id)){
      $res = checkToken($t_id, $token);
      if($res['status']){
      $query = query("SELECT section_id from sections where section_id = {$section_id}");
        $query_status = confirm($query);
        if($query_status['status']){
          if(rows($query)){
          $query = query("UPDATE students SET name = '{$name}', mac_id = '{$mac_id}' WHERE enroll_no = '{$enroll_no}'");
            $query_status = confirm($query);
            if($query_status['status']){
              if(affectedRows()){
                $status = true;
                $message = 'Student details are updated.';
              }else{
                $message = 'Either student not found or details are same.';
              }
            }else{
              $message = $query_status['message'];
            }
          }else{
            $message = 'Section not found.';
          }
        }else{
          $message = $query_status['message'];
        }
      }else{
        $message = $res['message'];
      }
    }else{
      $message = 'All fields are mandatory';
    }
  }else{
    $message = 'Insufficient parameters!';
  }
  return json_encode(array(
    'status'  => $status,
    'data'    => $data,
    'message' => $message
  ));
}

?>
