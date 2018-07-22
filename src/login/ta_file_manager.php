<?php
	require_once  "wsaa.php";

	class TA_File_Manager {

		protected $serviceName;
		protected $file;
		private $token;
		private $sign;

		public function __construct($serviceName) {
			if(empty($serviceName)) {
				throw new Exception("Service name not defined to set TA");
			}

			$this->serviceName = $serviceName;
			$fileName = sprintf("TA%s.xml", $this->serviceName);

			if(file_exists(__DIR__ ."/sources/". $fileName)) {
				$stringTAFile = file_get_contents(__DIR__ ."/sources/". $fileName);
				$this->file = simplexml_load_string($stringTAFile);
			}
		}

		public function exist() {
			return !empty($this->file);
		}

		public function isValid() {
			$headerIsValid = $this->validateHeader();
			$credentialIsValid = $this->validateCredentials();

			return $headerIsValid && $credentialIsValid;
		}

		public function getToken() {
			return !empty($this->token) ? $this->token : null;
		}

		public function getSign() {
			return !empty($this->sign) ? $this->sign : null;
		}

		private function validateHeader() {

			$header = $this->file->xpath("header");

			if(empty($header) || empty($header[0])) {
				return false;
			}

			$header = $header[0];

			$expirationTime = !empty($header->expirationTime->__toString()) ? $header->expirationTime->__toString() : null;
			$expirationTime = !empty($expirationTime) ? strtotime($expirationTime) : null;

			return !empty($expirationTime) && $expirationTime > date("U");
		}

		private function validateCredentials() {

			$credentials = $this->file->xpath("credentials");

			if(empty($credentials) || empty($credentials[0])) {
				return false;
			}

			$credentials = $credentials[0];

			$this->token = !empty($credentials->token->__toString()) ? $credentials->token->__toString() : null;
			$this->sign = !empty($credentials->sign->__toString()) ? $credentials->sign->__toString() : null;

			return !empty($this->token) && !empty($this->sign);
		}

		public function generate() {

			$wsaa = new WSAA();
			$stringTA = $wsaa->getTA($this->serviceName);

			if(!empty($stringTA)) {
				$isGenerated = file_put_contents(__DIR__ ."\sources\TA.xml", $stringTA);
			}

			if(!$isGenerated) {
				throw new Exception("Error on generate TA file");
			}
		}
	}
?>