<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Connector readiness is private configuration. A connector is eligible for
 * public rendering only when public_enabled is true, both locale URLs are
 * valid HTTPS destinations, and an acceptance receipt has been recorded.
 */
return array(
	'wolt' => array(
		'label'              => 'Wolt',
		'public_enabled'     => true,
		'url_he'             => 'https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete',
		'url_en'             => 'https://wolt.com/en/isr/tel-aviv/restaurant/sabich-complete',
		'merchant_verified'  => true,
		'availability_check' => true,
		'acceptance_receipt' => 'verified-public-menu-2026-07-31',
	),
	'tenbis' => array(
		'label'              => 'תן ביס',
		'public_enabled'     => false,
		'url_he'             => '',
		'url_en'             => '',
		'merchant_verified'  => false,
		'availability_check' => false,
		'acceptance_receipt' => '',
	),
	'cibus' => array(
		'label'              => 'Cibus',
		'public_enabled'     => false,
		'url_he'             => '',
		'url_en'             => '',
		'merchant_verified'  => false,
		'availability_check' => false,
		'acceptance_receipt' => '',
	),
	'spareeat' => array(
		'label'              => 'SpareEat',
		'public_enabled'     => false,
		'url_he'             => '',
		'url_en'             => '',
		'merchant_verified'  => false,
		'availability_check' => false,
		'acceptance_receipt' => '',
	),
);
