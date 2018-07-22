<?php
	class WSAA {

		private $certificateFile;
		private $privateKeyFile;
		private $passPhrase;
		private $traFile;
		private $TA;

		public function __construct() {
			$this->certificateFile = __DIR__ ."\sources\ghf.crt";
			$this->privateKeyFile = __DIR__ ."\sources\ghf.key";
			$this->passPhrase = "xxxxx";

			if(!file_exists($this->certificateFile) || !file_exists($this->certificateFile)) {
				throw new Exception("certificate file or private key is not found in repository");
			}
		}

		public function getTA($serviceName) {
			$this->createTra($serviceName);
			$cms = $this->signTra();
			$this->call();

			return $this->TA;
		}

		private function createTra($serviceName) {

			$traDir = __DIR__ ."\sources";
			$traFileDir = $traDir. "\TRA.xml";

			$TRA = new SimpleXMLElement(
				'<?xml version="1.0" encoding="UTF-8"?>' .
				'<loginTicketRequest version="1.0">'.
				'</loginTicketRequest>'
			);

			$TRA->addChild('header');
			$TRA->header->addChild('uniqueId',date('U'));
			$TRA->header->addChild('generationTime',date('c',date('U')-60));
			$TRA->header->addChild('expirationTime',date('c',date('U')+60));
			$TRA->addChild('service',$serviceName);
			$isGenerated = $TRA->asXML($traFileDir);

			if(!empty($isGenerated)) {
				$this->traFile = $traFileDir;
				$this->traTmpFile = $traDir. "\TRA.tmp";
			} else {
				throw new Exception("Error on generate tra file");
			}
		}

		private function signTRA() {
			$status=openssl_pkcs7_sign($this->traFile, $this->traTmpFile, "file://". $this->certificateFile,
				array("file://". $this->privateKeyFile, $this->passPhrase),
				array(),
				!PKCS7_DETACHED
			);

			if(!$status) {
				exit("ERROR generating PKCS#7 signature\n");
			}

			$inf=fopen($this->traTmpFile, "r");
			$i=0;
			$cms = "";

			while (!feof($inf)) {
				$buffer=fgets($inf);
				if($i++ >= 4) {
					$cms.=$buffer;
				}
			}

			fclose($inf);
			unlink($this->traTmpFile);
			return $cms;
		}

		private function call($cms) {
			$client = new SoapClient(__DIR__ ."\sources\wsaa.wsdl", array(
				// 'proxy_host'     => "10.20.152.112",
				// 'proxy_port'     => "80",
				// 'soap_version'   => SOAP_1_2,
				// "location"       => "https://wsaahomo.afip.gov.ar/ws/services/LoginCms",
				"trace"          => 1,
				"exceptions"     => 0
			));

			$results = $client->loginCms(array("in0" => $cms));

			file_put_contents(__DIR__ ."/sources/request-loginCms.xml",$client->__getLastRequest());
			file_put_contents(__DIR__ ."/sources/response-loginCms.xml",$client->__getLastResponse());

			if(is_soap_fault($results)) {
				exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
			}

			$this->TA = $results->loginCmsReturn;
		}
	}
?>