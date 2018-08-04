<?php

namespace InsenseSMS\Channels;

class SMSUtils {

	public static function getCustomUniqueId($now) {
		return $now->timestamp . uniqid();
	}

}