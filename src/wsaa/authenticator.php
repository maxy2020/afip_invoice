<?php
	class authenticator {

		private $certificate;
		private $privateKey;
		private $passPhrase;
		private $traFile;

		public function __construct() {
			$this->certificate = __DIR__ ."\sources\ghf.crt";
			$this->privateKey = __DIR__ ."\sources\ghf.key";
			$this->passPhrase = "xxxxx";

			if(!file_exists($this->certificate) || !file_exists($this->certificate)) {
				throw new Exception("certificate file or private key is not found in repository");
			}
		}

		public function createTra($serviceName = null) {

			// wsct => Web Service de Comprobantes T destinados a Servicios de Alojamiento a Turistas Extranjeros
			// wsfe => para quienes emitan comprobantes electrónicos de tipos "A" y "B". Reemplazado por "wsfev1" desde el 1-jul-2011, ver e)
			// wsfeca => Factura electronica CA
			// wsmtxca => para quienes emitan comprobantes "A" y "B" con detalle de items, CAE y CAEA)

			$serviceName = empty($serviceName) ? "wsct" : $serviceName;

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

		public function SignTRA() {
			$status=openssl_pkcs7_sign($this->traFile, $this->traTmpFile, "file://". $this->certificate,
				array("file://". $this->privateKey, $this->passPhrase),
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

		public function CallWSAA($cms) {
			$client = new SoapClient(__DIR__ ."\sources\wsaa.wsdl", array(
				// 'proxy_host'     => "10.20.152.112",
				// 'proxy_port'     => "80",
				// 'soap_version'   => SOAP_1_2,
				// "location"       => "https://wsaahomo.afip.gov.ar/ws/services/LoginCms",
				"trace"          => 1,
				"exceptions"     => 0
			));

			$results = $client->loginCms(array("in0" => $cms));

			file_put_contents("request-loginCms.xml",$client->__getLastRequest());
			file_put_contents("response-loginCms.xml",$client->__getLastResponse());

			if(is_soap_fault($results)) {
				exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
			}

			$this->TA = $results->loginCmsReturn;
		}

		public function generateTAFile() {
			if(!empty($this->TA))
				file_put_contents(__DIR__ ."\sources\TA.xml", $this->TA);
		}
	}
?>