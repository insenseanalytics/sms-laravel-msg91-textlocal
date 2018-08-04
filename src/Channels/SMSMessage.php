<?php

namespace InsenseSMS\Channels;

class SMSMessage {

	/**
	 * Mobile number of sms recipient
	 *
	 * @var string
	 */
	public $to = null;

	/**
	 * The message content.
	 *
	 * @var string
	 */
	public $content;
	
	/**
	 * Flag that indicates if message content is unicode or text
	 *
	 * @var bool
	 */
	public $unicode = false;
	
	/**
	 * Flag that indicates if message is promotional or transactional
	 *
	 * @var bool
	 */
	public $transactional = true;
	
	/**
	 * Timestamp if message is to be sent at a scheduled time
	 *
	 * @var \Carbon\Carbon
	 */
	public $scheduleTime = null;

	/**
	 * Create a new message instance.
	 * 
	 * @param string $content
	 * @param string $to
	 * @param bool $unicode
	 * @param bool $transactional
	 * @param string $scheduleTime
	 */
	public function __construct($content = '', $to = null, $unicode = false, $transactional = true, $scheduleTime = null) {
		$this->content = $content;
		$this->to = $to;
		$this->unicode = $unicode;
		$this->transactional = $transactional;
		$this->scheduleTime = $scheduleTime ? new \Carbon\Carbon($scheduleTime) : null;
	}

	/**
	 * Set the message recipient.
	 *
	 * @param string $to
	 * @return $this
	 */
	public function to($to) {
		$this->to = $to;
		return $this;
	}
	
	/**
	 * Set the message content.
	 *
	 * @param  string  $content
	 * @return $this
	 */
	public function content($content) {
		$this->content = $content;
		return $this;
	}

	/**
	 * Set the message content type (unicode/text).
	 *
	 * @param bool $isUnicode set to true for unicode message
	 * @return $this
	 */
	public function unicode($isUnicode = true) {
		$this->unicode = $isUnicode;
		return $this;
	}
	
	/**
	 * Set the message type (promotional/transactional).
	 *
	 * @param bool $isPromotional set to true for promotional message
	 * @return $this
	 */
	public function promotional($isPromotional = true) {
		$this->transactional = !$isPromotional;
		return $this;
	}
	
	/**
	 * Set the message type (promotional/transactional).
	 *
	 * @param bool $isTransactional set to true for transactional message
	 * @return $this
	 */
	public function transactional($isTransactional = true) {
		$this->transactional = $isTransactional;
		return $this;
	}
	
	/**
	 * Set the scheduled time of the message.
	 *
	 * @param  \Carbon\Carbon $scheduleTime
	 * @return $this
	 */
	public function scheduledAt(\Carbon\Carbon $scheduleTime = null) {
		$this->scheduleTime = $scheduleTime;
		return $this;
	}
}