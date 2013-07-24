<?php

  $mysql_user = "root";
  $mysql_password = "";

  $mysql_host = "localhost";
  $mysql_db = "chat_test";
  $mysql_table = $client['client']."_usable_emails";


  // store new message in the file
  $msg = isset($_GET['msg']) ? $_GET['msg'] : '';
  $user = isset($_GET['user']) ? $_GET['user'] : '';
  $time = isset($_GET['time']) ? $_GET['time'] : '';
  if ($msg != '') {
    try { 
      //$options = array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION);
      $DBH = new PDO("mysql:host=$mysql_host;dbname=$mysql_db", $mysql_user, $mysql_password);
    } catch(PDOException $e) {
      echo $e->getMessage();
      exit();
    }
    try {
      $STM = $DBH->prepare("INSERT INTO messages (user, message, `time`) VALUES (:user, :message, :time)");
      $STM->bindParam(':user', $user);
      $STM->bindParam(':message', $msg);
      $STM->bindParam(':time', $time);
      $STM->execute();
    } catch(PDOException $e) {
      echo $e->getMessage();
      exit();
    }
    $DBH = null;
    die();
  }

  // infinite loop until the data file is not modified
  $lastmod    = $_GET['time'];
  //40 second timeout
  $refresh = time() + 40;
  try { 
      //$options = array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION);
      $DBH = new PDO("mysql:host=$mysql_host;dbname=$mysql_db", $mysql_user, $mysql_password);
    } catch(PDOException $e) {
      echo $e->getMessage();
      exit();
    }
  do // check if the data file has been modified
  {
    try {
      $STM = $DBH->prepare("SELECT user, message, time, `time_sent` FROM messages WHERE time > :time");
      $STM->bindParam(':time', $lastmod);
      $STM->execute();
      $row = $STM->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
      //echo $e->getMessage();
      //exit();
    }
    if($refresh < time()) {
      $DBH = null;
      echo json_encode(array('timeout' => 'true'));
      exit();
    }
    usleep(1000000); // sleep 500ms to unload the CPU
  } while ($STM->rowCount() < 1);

  $next_stamp = 0;
  for($i=0; $i < $STM->rowCount(); $i++) {
    if($row[$i]['time'] > $next_stamp) {
      $next_stamp = $row[$i]['time'];
    }
    $row[$i]['time_sent'] = date('H:i:s',strtotime($row[$i]['time_sent']));
  }

  $response['messages'] = $row;
  $response['timestamp'] = $next_stamp;

  // return a json array
  $response = json_encode($response);
  echo $response;
  flush();

?>