<?php

return [
	
	/*
	 |--------------------------------------------------------------------------
	 | Driver
	 |--------------------------------------------------------------------------
	 |
	 | Determine the SMS driver / provider
	 |
	*/
	'driver' => env('SMS_DRIVER'), 
	
	/*
	 |--------------------------------------------------------------------------
	 | Driver Configuration
	 |--------------------------------------------------------------------------
	 |
	 | Set the public API keys and other configurations as required by the SMS Provider
	 | The array key is the driver's name
	 |
	*/
	'textlocal'	=> [
		'apiKey' => env('TEXTLOCAL_APIKEY'),
		'endpoint' => env('TEXTLOCAL_ENDPOINT'),
		'username' => env('TEXTLOCAL_USERNAME'),
		'hash' => env('TEXTLOCAL_HASH'),
		'trans_sender' => env('TEXTLOCAL_TRANSSENDER'),
		'promo_sender' => env('TEXTLOCAL_PROMOSENDER'),
                'test' => env('TEXTLOCAL_TEST')
	],
	
	'msg91'	=> [
		'apiKey' => env('MSG91_APIKEY'),
		'endpoint' => env('MSG91_ENDPOINT'),
		'trans_sender' => env('MSG91_TRANSSENDER'),
		'promo_sender' => env('MSG91_PROMOSENDER'),
	],
	
	
	
];
