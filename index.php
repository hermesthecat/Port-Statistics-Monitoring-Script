<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Simple monitoring tool using SNMP v2c</title>
</head>

<body>
  <div id="body">
    <?php
    // Include configuration file
    require_once 'config.php';

    // Check if IP is allowed (if security is enabled)
    if (REQUIRE_AUTH && !isIpAllowed($_SERVER['REMOTE_ADDR'])) {
      die('Access denied');
    }

    try {
      $pdo = getDatabaseConnection();
    } catch (PDOexception $e) {
      $errorMsg = $errorMessages['db_connection_failed'] . ": " . $e->getMessage();
      echo $errorMsg;
      logError($errorMsg);
      exit;
    }

    try {
      $stmt = $pdo->prepare("SELECT deviceid, ipaddress FROM devices");
      $stmt->execute();
      $data = array();
      $deviceid = array();
      if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch()) {
          $deviceid[] = $row['deviceid'];
          $data[]   = $row['ipaddress'];
        }
        $result = array_combine($deviceid, $data);
        foreach ($result as $key => $value) {
          $id = $key;
          try {
            $session = new SNMP(SNMP_VERSION, "$value", SNMP_COMMUNITY);
            $session->valueretrieval = SNMP_VALUE_PLAIN;
            $ifhost   = $session->get($snmpOIDs['ifDescr'] . ".234889216", TRUE);
            $ifname   = $session->walk($snmpOIDs['ifName'], TRUE);
          } catch (Exception $e) {
            $errorMsg = $errorMessages['snmp_connection_failed'] . " ($value): " . $e->getMessage();
            echo $errorMsg . "<br>";
            logError($errorMsg);
            continue;
          }
          foreach ($ifname as $naam => $ioid) {
            echo "" . $naam . " -/- ";
            echo "" . $ioid . "</br>";
            try {
              $stmt = $pdo->prepare("INSERT INTO ports (devicename, interfacename, interfaceoid, deviceid) VALUES (:devicename, :interfacename, :interfaceoid, :deviceid)
                                              ON DUPLICATE KEY UPDATE interfaceoid = :test");
              $stmt->execute(array(":devicename" => $ifhost, ":interfacename" => $ioid, ":interfaceoid" => $naam, ":test" => $naam, ":deviceid" => $id));
            } catch (PDOException $e) {
              $errorMsg = $errorMessages['ports_insert_error'] . ": " . $e->getMessage();
              echo $errorMsg . "<br>";
              logError($errorMsg);
            }
            $session->close();
          }

          try {
            $stmt = $pdo->prepare("SELECT interfaceoid FROM ports WHERE deviceid = :deviceid");
            $stmt->execute(array(":deviceid" => $id));
            $combi = [];
            if ($stmt->rowCount() > 0) {
              while ($row = $stmt->fetch()) {
                if (!isset($combi[$value])) {
                  $combi[$value] = [];
                }
                $combi[$value][] = $row['interfaceoid'];
              }

              foreach ($combi as $ip => $oid) {
                try {
                  $session = new SNMP(SNMP_VERSION, "$ip", SNMP_COMMUNITY);
                  $session->valueretrieval = SNMP_VALUE_PLAIN;

                  foreach ($oid as $bla) {
                    $ifinerrorsoid      = $snmpOIDs['ifInErrors'] . ".$bla";
                    $queryifinerrors    = $session->get("$ifinerrorsoid", TRUE);
                    $ifhighspeedoid     = $snmpOIDs['ifHighSpeed'] . ".$bla";
                    $queryifhighspeed   = $session->get("$ifhighspeedoid", TRUE);
                    try {
                      $datum = date("Y-m-d H:i:s");
                      $stmt = $pdo->prepare("INSERT INTO statistics (erroroid, interfaceerror, highspeedoid, ifhighspeed, time, portid) VALUES (:erroroid, :interfaceerror, :highspeedoid, :ifhighspeed, :time, (SELECT id FROM ports WHERE deviceid = :deviceid AND interfaceoid = :interfaceoid))");
                      $stmt->execute(array(":erroroid" => $ifinerrorsoid, ":interfaceerror" => $queryifinerrors,  ":highspeedoid" => $ifhighspeedoid, ":ifhighspeed" => $queryifhighspeed, ":time" => $datum, ":deviceid" => $id, ":interfaceoid" => $bla));
                    } catch (PDOException $e) {
                      $errorMsg = $errorMessages['statistics_insert_error'] . ": " . $e->getMessage();
                      echo $errorMsg . "<br>";
                      logError($errorMsg);
                    }
                  }
                  $session->close();
                } catch (Exception $e) {
                  $errorMsg = $errorMessages['snmp_connection_failed'] . " ($ip): " . $e->getMessage();
                  echo $errorMsg . "<br>";
                  logError($errorMsg);
                }
              }
            }
          } catch (PDOException $e) {
            $errorMsg = $errorMessages['interface_select_error'] . ": " . $e->getMessage();
            echo $errorMsg . "<br>";
            logError($errorMsg);
          }
        }
      } else {
        echo $errorMessages['no_devices_found'];
      }
    } catch (PDOException $e) {
      $errorMsg = "Something went wrong: " . $e->getMessage();
      echo $errorMsg . "<br>";
      logError($errorMsg);
    }
    ?>
  </div>
</body>

</html>