<?php
	require_once("ta_file_manager.php");

	class Certificate {
		protected $token;
		protected $sign;

		public function __construct($serviceName = null) {

			// wsct => Web Service de Comprobantes T destinados a Servicios de Alojamiento a Turistas Extranjeros
			// wsfe => para quienes emitan comprobantes electrónicos de tipos "A" y "B". Reemplazado por "wsfev1" desde el 1-jul-2011, ver e)
			// wsfeca => Factura electronica CA
			// wsmtxca => para quienes emitan comprobantes "A" y "B" con detalle de items, CAE y CAEA)

			$serviceName = empty($serviceName) ? "wsct" : $serviceName;

			$taFileManager = new TA_File_Manager($serviceName);
			if(!$taFileManager->exist() || !$taFileManager->isValid()) {
				$isGenerated = $taFileManager->generate();
			}

			$this->token = $taFileManager->getToken();
			$this->sign = $taFileManager->getSign();

			if(empty($this->token) || empty($this->sign)) {
				throw new Exception("Error on generate certificate");
			}
		}

		public function getToken() {
			return !empty($this->token) ? $this->token : null;
		}

		public function getSign() {
			return !empty($this->sign) ? $this->sign : null;
		}
	}
?>