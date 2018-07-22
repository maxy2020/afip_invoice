<?php
	/**
	 *
	 */
	class Afip_Instance {

		static $instance;

		protected function __construct() {}

		public static function getInstance() {
			if(!empty(static::$instance)) {
				return static::$instance;
			}

			static::$instance = new static();
			return static::$instance;
		}


	}
?>