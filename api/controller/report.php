<?php
// Include Deals Model
require_once "model/report.php";
require_once "model/login.php";
class REPORT extends REPORTMODEL {
	public function reportCtrl($request, $tokenParms) {
		try {
			// Object for Login Model
			$loginAuthendicate = new LOGINMODEL();
			// Token check for all the service
			$token = $loginAuthendicate->tokenCheck($tokenParms);


			if (!empty($token)) {
				$response = $this->processList($request, $token);
				echo $this->json($response);
				exit();
			} else {
				throw new Exception("Unauthorized Login");
			}
		} catch (Exception $e) {
			echo $this->json(array(
				"result" => "401",
				"message" => $e->getMessage(),
			));
			exit();
		}
	}
}

// Initiate controller & Response method
$classActivate = new REPORT();
// Reponse for the request
$classActivate->reportCtrl($data, $token);

?>