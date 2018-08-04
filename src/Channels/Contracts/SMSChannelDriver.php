<?php

namespace InsenseSMS\Channels\Contracts;
use Carbon\Carbon;
use InsenseSMS\Channels\SMSChannelManager;
use Illuminate\Contracts\Container\Container;
use Closure;

abstract class SMSChannelDriver {
	
	/**
	 * The array of callbacks to be run after the sms is sent.
	 *
	 * @var array
	 */
	protected $afterCallbacks = [];

	/**
	 * Send an SMS to one or more comma separated numbers
	 * @param       $numbers
	 * @param       $message
	 * @param bool  $transactional
	 * @param bool  $unicode
	 * @param array $options specific to SMS provider
	 * @return array|mixed
	 * @throws Exception
	 */
	abstract public function sendSms(array $numbers, $message = null, $transactional = false, $unicode = false, Carbon $scheduleTime = null, array $options = []);
	
	/**
	 * Send an SMS to a Group of contacts - group IDs can be retrieved from getGroups()
	 * @param       $groupId
	 * @param       $message
	 * @param bool  $transactional
	 * @param bool  $unicode
	 * @param array $options test, receipt_url, custom, optouts, validity
	 * @return array|mixed
	 * @throws Exception
	 */
	abstract public function sendSmsGroup($groupId, $message = null, $transactional = false, $unicode = false, Carbon $scheduleTime = null, array $options = []);
	
	abstract public function sendMessage(\InsenseSMS\Channels\SMSMessage $message);
	
	/**
	 * Create a new contact group
	 * @param $group_name
	 * @return string group id
	 */
	abstract public function createGroup($group_name);
	
	/**
	 * Get contacts from a group - Group IDs can be retrieved with the getGroups() function
	 * @param     $groupId
	 * @return array SMSContacts
	 */
	abstract public function getContacts($groupId); 
	
	/**
	 * Create one or more number-only contacts in a specific group
	 * @param        $numbers
	 * @param string $groupid
	 * @return bool true on success
	 */
	abstract public function createContacts($numbers, $groupid);
	
	/**
	 * Create one number-only contacts in a specific group
	 * @param        $number
	 * @param string $groupid
	 * @return bool true on success
	 */
	abstract public function createContact($number, $groupid);
	
	/**
	 * Get a list of groups and group IDs
	 * @return array SMSGroups
	 */
	abstract public function getGroups();
	
	/**
	 * Get Credit Balances
	 * @return array with key sms and value total sms remaining
	 */
	abstract public function getBalance();
	
	/**
	 * Delete a group - Be careful, we can not recover any data deleted by mistake
	 * @param $groupid
	 * @return bool true on success
	 */
	abstract public function deleteGroup($groupid);

    /**
	 * Register a callback to be called after the sms is sent.
	 *
	 * @param  \Closure  $callback
	 * @return $this
	 */
	public function after(Closure $callback) {
		$this->afterCallbacks[] = $callback;

		return $this;
	}

	/**
	 * Call all of the "after" callbacks for the sms.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @return void
	 */
	public function callAfterCallbacks(array $params, Container $container = null) {
		if(count($this->afterCallbacks) > 0 && is_null($container)) {
			$container = app()->make(Container::class);
		}
		
		foreach ($this->afterCallbacks as $callback) {
			$container->call($callback, $params);
		}
	}

	public function __call($method, $parameters) {
		$manager = app()->make(SMSChannelManager::class);
		if(method_exists($manager, $method)) {
			return call_user_func_array([$manager, $method], $parameters);
		}
		
		$className = static::class;

        throw new \BadMethodCallException("Call to undefined method {$className}::{$method}()");
	}
	
}
