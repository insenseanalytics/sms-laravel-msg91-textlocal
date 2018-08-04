<?php

namespace InsenseSMS\Channels;

use InsenseSMS\Channels\Contracts\SMSChannelDriver;
use GuzzleHttp\Client as HttpClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use InsenseSMS\Events\SMSSentEvent;
use InsenseSMS\Events\SMSDeliveryEvent;

class Msg91 extends SMSChannelDriver
{

    /** @var HttpClient HTTP Client */
    protected $http;

    /** @var null|string API Key */
    protected $apiKey = null;

    /** @var null|string Endpoint */
    protected $endpoint = null;
    
    /** @var null|string transactional sender code */
    protected $transSender = null;
    
    /** @var null|string promotional sender code */
    protected $promoSender = null;

    /** @var array errors */
    protected $errors = [];

    /** @var array warnings */
    protected $warnings = [];

    /** @var array last request parameters */
    protected $lastRequest = [];

    const REQUEST_TIMEOUT = 60;
    
    const ROUTE_PROMOTIONAL = 1;
    const ROUTE_TRANSACTIONAL = 4;

    public function __construct($apiKey = null, $endpoint = null, $transSender = null, $promoSender = null, HttpClient $httpClient = null)
    {
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
        $this->transSender = $transSender;
        $this->promoSender = $promoSender;
        $this->http = $httpClient;
    }

    /**
     * Get HttpClient.
     *
     * @return HttpClient
     */
    protected function httpClient()
    {
        return $this->http ?: $this->http = new HttpClient();
    }

    protected function appendBasicParams(array &$params)
    {
        $params['authkey'] = $this->apiKey;
        $params['response'] = 'json';
        return $params;
    }

    /**
     * Private function to construct and send the request and handle the response
     * @param       $command
     * @param array $params
     * @return array|mixed
     * @throws Exception
     */
    protected function api($command, $params = [], $method = 'GET', $parseErrors = true)
    {
        $this->lastRequest = $this->appendBasicParams($params);

        $reponse = $this->sendRequest($command, $params, $method);
        $body = $reponse->getBody();

        $result = json_decode($body);
        //		var_dump($result);
        
        if ($parseErrors) {
            $this->parseErrors($result);
        }

        return $result;
    }

    protected function parseErrors($result)
    {
        if (is_array($result)) {
            $lastResult = end($result);
            if (isset($lastResult->msgType) || isset($lastResult->msg_type) || isset($lastResult->type)) {
                $this->parseErrors($lastResult);
            }
        } elseif (!isset($result->msgType) && !isset($result->msg_type) && !isset($result->type)) {
            throw new Exception('msg type not set');
        } else {
            if (isset($result->msgType)) {
                $msgType = $result->msgType;
            } elseif (isset($result->msg_type)) {
                $msgType = $result->msg_type;
            } else {
                $msgType = $result->type;
            }
            
            if ($msgType != "success") {
                if (isset($result->msg)) {
                    $msg = $result->msg;
                } elseif (isset($result->message)) {
                    $msg = $result->message;
                } else {
                    $msg = "Unable to parse error message";
                }
                throw new Exception($msg);
            }
        }
    }

    /**
     * Guzzle request handler
     * @param $command API Command
     * @param $params Request Params
     * @param $method GET or POST
     * @return mixed
     */
    protected function sendRequest($command, $params, $method = 'GET')
    {
        $url = $this->endpoint . $command;

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'query' => $params
        ];

        return $this->httpClient()->request($method, $url, $options);
    }

    protected function resolveReports($result, array $numbers, $message, $transactional, $unicode, $scheduleTime, $now)
    {
        $requestId = $result->message;
        $reports = [];
        foreach ($numbers as $number) {
            $reports[] = (new SMSMessageStatusReport($message))->withCustom($requestId)
                    ->requestedAt($now)->scheduledAt($scheduleTime)->to($number)
                    ->transactional($transactional)->unicode($unicode);
        }
        return $reports;
    }
    
    protected function resolveSender($transactional = false)
    {
        if ($transactional) {
            return $this->transSender;
        } else {
            return $this->promoSender;
        }
    }
    
    protected function resolveRoute($transactional = false)
    {
        if ($transactional) {
            return self::ROUTE_TRANSACTIONAL;
        } else {
            return self::ROUTE_PROMOTIONAL;
        }
    }

    protected function validateArgs(array $args)
    {
        if (array_key_exists('message', $args) && empty($args['message'])) {
            throw new Exception('Empty message');
        }
        
        if (array_key_exists('sender', $args) && empty($args['sender'])) {
            throw new Exception('Empty sender name');
        }
        
        if (array_key_exists('numbers', $args) && empty($args['numbers'])) {
            throw new Exception('No phone numbers provided');
        }
    }
    
    protected function getTransformedStatus($status)
    {
        $convertedStatus = SMSMessageStatusReport::STATUS_PENDING;
        switch ($status) {
            case 1:
                $convertedStatus = SMSMessageStatusReport::STATUS_DELIVERED;
                break;
            case 2:
                $convertedStatus = SMSMessageStatusReport::STATUS_FAILED;
                break;
            case 16:
                $convertedStatus = SMSMessageStatusReport::STATUS_REJECTED;
                break;
            default:
                break;
        }
        return $convertedStatus;
    }

    protected function resolveDeliveryReport($requestParams)
    {
        $reports = [];
        $jsonData =	json_decode($requestParams['data'], true);
        foreach ($jsonData as $data) {
            $requestId = $data['requestId'];

            foreach ($data['report'] as $report) {
                $transformedStatus = $this->getTransformedStatus($report['status']);
                $date = new Carbon($report['date']);
                $row = (new SMSMessageStatusReport(null))->to($report['number'])->withCustom($requestId)
                        ->status($transformedStatus)->deliveredAt($date);
                if ($transformedStatus === SMSMessageStatusReport::STATUS_REJECTED) {
                    $row->unsubscribe(true);
                }
                $reports[] = $row;
            }
        }
        return $reports;
    }

    /**
     * Get last request's parameters
     * @return array
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Send an SMS to one or more comma separated numbers
     * @param       $numbers
     * @param       $message
     * @param bool  $transactional
     * @param bool  $unicode
     * @param array $options country, flash, ignoreNdnc, campaign
     * @return array|mixed
     * @throws Exception
     */
    public function sendSms(array $numbers, $message = null, $transactional = false, $unicode = false, Carbon $scheduleTime = null, array $options = [])
    {
        $sender = $this->resolveSender($transactional);
        $route = $this->resolveRoute($transactional);
        
        $this->validateArgs(compact('numbers', 'message', 'sender'));

        $params = [
            'message' => rawurlencode($message),
            'mobiles' => implode(',', $numbers),
            'sender' => rawurlencode($sender),
            'route' => $route,
            'unicode' => $unicode,
        ];
        
        if (!is_null($scheduleTime)) {
            $params['schtime'] = $scheduleTime->toDateTimeString();
        }
        $now = Carbon::now();
        $result = $this->api('sendhttp.php', array_merge($options, $params));
        $reports = $this->resolveReports($result, $numbers, $message, $transactional, $unicode, $scheduleTime, $now);
        event(new SMSSentEvent($reports));
        
        return $reports;
    }
    
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
    public function sendSmsGroup($groupId, $message = null, $transactional = false, $unicode = false, Carbon $scheduleTime = null, array $options = [])
    {
        $sender = $this->resolveSender($transactional);
        $route = $this->resolveRoute($transactional);
        
        $this->validateArgs(compact('groupId', 'message', 'sender'));

        $params = [
            'message' => rawurlencode($message),
            'group_id' => $groupId,
            'sender' => rawurlencode($sender),
            'route' => $route,
            'unicode' => $unicode,
        ];
        
        if (!is_null($scheduleTime)) {
            $params['schtime'] = $scheduleTime->toDateTimeString();
        }
        $now = Carbon::now();
        $requestId = $this->api('sendhttp.php', array_merge($options, $params));
        $numbers = collect($this->getContacts($groupId))
                ->where('number', '!=', null)->pluck('number')->all();

        $reports = $this->resolveReports($requestId, $numbers, $message, $transactional, $unicode, $scheduleTime, $now);
        event(new SMSSentEvent($reports));

        return $reports;
    }
    
    /**
     * Create a new contact group
     * @param $group_name
     * @return string group id
     */
    public function createGroup($group_name)
    {
        $params = compact('group_name');
        $result = $this->api('add_group.php', $params);
        if (!isset($result->msgType) || $result->msgType != "success"
                || !isset($result->grpId)) {
            throw new \RuntimeException("Failed to create group");
        }
        return $result->grpId;
    }
    
    /**
     * Get contacts from a group - Group IDs can be retrieved with the getGroups() function
     * @param     $groupId
     * @return array SMSContacts
     */
    public function getContacts($groupId = null)
    {
        $this->validateArgs(compact('groupId'));
        
        $params = [];
        
        if (!is_null($groupId)) {
            $params['group'] = $groupId;
        }
        
        $results = $this->api('list_contact.php', $params);
        
        $contacts = [];
        
        foreach ($results as $result) {
            $number = isset($result->number) ? $result->number : null;
            $name = isset($result->name) ? $result->name : "";
            $contacts[] = new SMSContact($number, $name);
        }
        
        return $contacts;
    }
    
    /**
     * Create one or more number-only contacts in a specific group
     * @param        $numbers
     * @param string $groupid
     * @return bool true on success
     */
    public function createContacts($numbers, $groupid)
    {
        if (is_array($numbers)) {
            foreach ($numbers as $number) {
                $this->createContact($number, $groupid);
            }
        } else {
            return $this->createContact($numbers, $groupid);
        }

        return true;
    }
    
    /**
     * Create one or more number-only contacts in a specific group
     * @param        $number
     * @param string $groupid
     * @return bool true on success
     */
    public function createContact($number, $groupid)
    {
        $params = ["group" => $groupid];

        if (is_array($number)) {
            return $this->createContacts($number, $groupid);
        } else {
            $params['mob_no'] = $number;
        }

        $this->api('add_contact.php', $params);
        return true;
    }
    
    /**
     * Get a list of groups and group IDs
     * @return array SMSGroups
     */
    public function getGroups()
    {
        $results = $this->api('list_group.php');
        
        $groups = [];
        
        foreach ($results as $result) {
            $groupId = isset($result->id) ? $result->id : "";
            $name = isset($result->name) ? $result->name : "";
            $count = isset($result->count) ? $result->count : 0;
            $groups[] = new SMSGroup($groupId, $name, $count);
        }
        
        return $groups;
    }
    
    /**
     * Get Credit Balances
     * @return array
     */
    public function getBalance()
    {
        $promotionalBalance = $this->api(
            'balance.php',
                ['type' => self::ROUTE_PROMOTIONAL],
            'GET',
            false
        );
        
        $transactionalBalance = $this->api(
        
            'balance.php',
                ['type' => self::ROUTE_TRANSACTIONAL],
        
            'GET',
        
            false
        
        );
        
        $totalSms = $promotionalBalance + $transactionalBalance;
        
        return [
            'transactional' => $transactionalBalance,
            'promotional'	=> $promotionalBalance,
            'sms'			=> $totalSms,
        ];
    }
    
    /**
     * Delete a contact based on contact ID
     * @param     $contactId
     * @return mixed
     */
    public function deleteContact($contactId)
    {
        $options = ['contact_id' => $contactId];
        return $this->api('delete_contact.php', $options);
    }
    
    /**
     * Delete a group - Be careful, we can not recover any data deleted by mistake
     * @param $groupid
     * @return bool true on success
     */
    public function deleteGroup($groupid)
    {
        $options = ['group_id' => $groupid];
        $this->api('delete_group.php', $options);
        return true;
    }

    public function sendMessage(SMSMessage $smsMessage)
    {
        $content = trim($smsMessage->content);
        $numbers = [$smsMessage->to];
        $this->sendSms($numbers, $content, $smsMessage->transactional, $smsMessage->unicode, $smsMessage->scheduleTime);
    }

    public function deliverReport(Request $request)
    {
        $requestParams = $request->all();
        
        $reports = $this->resolveDeliveryReport($requestParams);
        
        event(new SMSDeliveryEvent($reports));
    }
}
