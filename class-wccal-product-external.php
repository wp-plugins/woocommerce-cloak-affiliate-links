<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Wccal_Product_External extends WC_Product_External {
	public function get_product_url() {
		$base = Wccal::get_affiliate_base();
		if ( get_option( 'permalink_structure' ) ) {	
			return get_site_url() . '/' . $base . '/' . $this->id;
		} else {
			return get_site_url() . '/index.php?' . $base . '=' . $this->id;
		}
	}
}