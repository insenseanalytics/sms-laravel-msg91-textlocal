<?php

namespace Insense\LaravelSMS\Channels;
use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class SMSGroup implements ArrayAccess, JsonSerializable, Arrayable, Jsonable {

	public $groupId;
	public $name;
	public $count;

	/**
	 * Structure of a contact object
	 * @param        $number
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $custom1
	 * @param string $custom2
	 * @param string $custom3
	 */
	function __construct($groupId = '', $name = '', $count = 0) {
		$this->groupId = $groupId;
		$this->name = $name;
		$this->count = $count;
	}

	public function toArray() {
		return [
			'groupId'	=> $this->groupId,
			'name'		=> $this->name,
			'count'		=> $this->count,
		];
	}
	
	public function jsonSerialize() {
		return $this->toArray();
	}

	public function offsetExists($offset) {
		return isset($this->$offset);
	}

	public function offsetGet($offset) {
		return $this->$offset;
	}

	public function offsetSet($offset, $value) {
		$this->$offset = $value;
	}

	public function offsetUnset($offset) {
		unset($this->$offset);
	}

	public function toJson($options = 0) {
		$json = json_encode($this->jsonSerialize(), $options);

		if (JSON_ERROR_NONE !== json_last_error()) {
			throw new \RuntimeException("Unable to json encode object of class " . static::class);
		}

		return $json;
	}

}
