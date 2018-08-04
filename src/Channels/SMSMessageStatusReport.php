<?php

namespace InsenseSMS\Channels;

class SMSMessageStatusReport extends SMSMessage {
	
	/**
	 * Delivery status of sms
	 *
	 * @var int
	 */
	public $status = self::STATUS_PENDING;
	
	/**
	 * Delivery time and date
	 *
	 * @var \Carbon\Carbon
	 */
	public $deliveryTimestamp = null;
	
	/**
	 * Request time and date
	 *
	 * @var \Carbon\Carbon
	 */
	public $requestTimestamp = null;
	
	/**
	 * Batch ID of SMS message
	 *
	 * @var string
	 */
	public $batchId = null;
	
	/**
	 * Custom ID of SMS message
	 *
	 * @var string
	 */
	public $customId = null;
	
	/**
	 * Unsubscribe SMS Message
	 *
	 * @var bool
	 */
	public $unsubscribe = false;

	/**
	 * Provider identifier of SMS message
	 *
	 * @var string
	 */
	public $messageId = null;
	
	const STATUS_PENDING = 'pending';
	const STATUS_DELIVERED = 'delivered';
	const STATUS_FAILED = 'failed';
	const STATUS_REJECTED = 'rejected';

	/**
	 * Delivery error message
	 *
	 * @var string
	 */
	public $errorMessage = null;
	
	/**
	 * Set the delivery timestamp of the SMS message.
	 * 
	 * @param \Carbon\Carbon $deliveryTimestamp
	 * @return $this
	 */
	public function deliveredAt(\Carbon\Carbon $deliveryTimestamp) {
		$this->deliveryTimestamp = $deliveryTimestamp;
		return $this;
	}
	
	/**
	 * Set the request timestamp of the SMS message.
	 * 
	 * @param \Carbon\Carbon $requestTimestamp
	 * @return $this
	 */
	public function requestedAt(\Carbon\Carbon $requestTimestamp) {
		$this->requestTimestamp = $requestTimestamp;
		return $this;
	}
	
	/**
	 * Set the batch ID of the SMS message.
	 * 
	 * @param string $batchId
	 * @return $this
	 */
	public function batch($batchId) {
		$this->batchId = $batchId;
		return $this;
	}
	
	/**
	 * Set the provider identifier of the SMS message.
	 * 
	 * @param string $messageId
	 * @return $this
	 */
	public function identifedBy($messageId) {
		$this->messageId = $messageId;
		return $this;
	}
	
	/**
	 * Set the status of the SMS message.
	 * 
	 * @param int $status
	 * @return $this
	 */
	public function status($status) {
		$this->status = $status;
		return $this;
	}
	
	/**
	 * Set the different fields of the SMS message.
	 * 
	 * @param string $customId
	 * @return $this
	 */
	public function withCustom($customId) {
		$this->customId = $customId;
		return $this;
	}

	/**
	 * Set the delivery error message of the SMS message.
	 * 
	 * @param string $errorMessage
	 * @return $this
	 */
	public function error($errorMessage) {
		$this->errorMessage = $errorMessage;
		return $this;
	}
	
	/**
	 * Unsubscribe from messaging for a number
	 * 
	 * @param bool $isUnsubscribe
	 * @return $this
	 */
	public function unsubscribe($isUnsubscribe = false) {
		$this->unsubscribe = $isUnsubscribe;
		return $this;
	}

}