<?php

namespace InsenseSMS\Channels;

use InsenseSMS\Channels\Contracts\SMSChannelDriver;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Validator;
use Exception;
use InsenseSMS\Events\SMSSentEvent;
use InsenseSMS\Events\SMSDeliveryEvent;
use InsenseSMS\Events\SMSUnsubscribeEvent;

class Textlocal extends SMSChannelDriver
{

    /** @var HttpClient HTTP Client */
    protected $http;

    /** @var null|string API Key */
    protected $apiKey = null;

    /** @var null|string Endpoint */
    protected $endpoint = null;

    /** @var null|string Username */
    protected $username = null;

    /** @var null|string Hash */
    protected $hash = null;

    /** @var null|string transactional sender code */
    protected $transSender = null;

    /** @var null|string promotional sender code */
    protected $promoSender = null;

    /** @var true|false Set this field to true to enable test mode */
    protected $test = true;

    /** @var array errors */
    protected $errors = [];

    /** @var array warnings */
    protected $warnings = [];

    /** @var array last request parameters */
    protected $lastRequest = [];

    const REQUEST_TIMEOUT = 60;
    const GROUPID_CONTACTS = 5;
    const GROUPID_OPTOUTS = 6;
    //send_sms
    const ERROR_RESPONSE_NO_COMMAND = 1;
    const ERROR_RESPONSE_UNRECOGNIZED_COMMAND = 2;
    const ERROR_RESPONSE_INVALID_LOGIN = 3;
    const ERROR_RESPONSE_NO_RECIPIENT = 4;
    const ERROR_RESPONSE_NO_MESSAGE = 5;
    const ERROR_RESPONSE_MESSAGE_TOO_LONG = 6;
    const ERROR_RESPONSE_INSUFFICIENT_CREDITS = 7;
    const ERROR_RESPONSE_INVALID_SCHEDULE_DATE = 8;
    const ERROR_RESPONSE_LAPSED_SCHEDULE_DATE = 9;
    //send_sms + get_contact + create_contact + bulk_create + delete_group
    const ERROR_RESPONSE_INVALID_GROUP_ID = 10;
    const ERROR_RESPONSE_EMPTY_GROUP = 11;
    //create_group
    const ERROR_RESPONSE_NO_GROUP_NAME = 12;
    const ERROR_RESPONSE_GROUP_NAME_TOO_LONG = 13;
    const ERROR_RESPONSE_GROUP_NAME_ALREADY_EXISTS = 14;
    //bulk_json + bulk_create
    const ERROR_RESPONSE_INVALID_JSON_STRING = 15;
    //message_status
    const ERROR_RESPONSE_NO_MESSAGE_ID = 16;
    const ERROR_RESPONSE_INVALID_MESSAGE_ID = 17;
    //get_contact + create_contact + bulk_create + delete_group
    const ERROR_RESPONSE_NO_GROUP_ID = 25;
    //batch_status
    const ERROR_RESPONSE_INVALID_BATCH_ID = 26;
    const ERROR_RESPONSE_NO_BATCH_ID = 27;
    //send_sms + bulk_json
    const ERROR_RESPONSE_INVALID_NUMBER_FORMAT = 32;
    //send_sms + bulk_json + bulk_create
    const ERROR_RESPONSE_NUMBER_LIMIT_EXCEED = 33;
    //send_sms + bulk_json
    const ERROR_RESPONSE_BOTH_GROUP_ID_NUMBER_SPECIFIED = 34;
    const ERROR_RESPONSE_INVALID_SENDER = 43;
    const ERROR_RESPONSE_EMPTY_SENDER = 44;
    //get_message + single_message_history + group_message_history + sms_message_history + get_history_api
    const ERROR_RESPONSE_INVALID_SORT_FIELD = 45;
    const ERROR_RESPONSE_INVALID_LIMIT_VALUE = 46;
    const ERROR_RESPONSE_INVALID_SORT_DIRECTION = 47;
    const ERROR_RESPONSE_INVALID_TIMESTAMP = 48;
    //get_survey_details + get_survey_results
    const ERROR_RESPONSE_INVALID_SURVEY_ID = 49;
    //get_surveys
    const ERROR_RESPONSE_NO_SURVEY_FOUND = 50;
    //send_sms + bulk_json + create_contact
    const ERROR_RESPONSE_NO_VALID_NUMBER = 51;
    //get_scheduled
    const ERROR_RESPONSE_NO_SCHEDULED_MESSAGE = 52;
    //get_message
    const ERROR_RESPONSE_INVALID_INBOX_ID = 53;
    const ERROR_RESPONSE_NO_INBOX_ID_SPECIFIED = 54;
    //create_contact
    const ERROR_RESPONSE_NO_NUMBERS_SPECIFIED = 55;
    const ERROR_RESPONSE_INVALID_NUMBERS_SPECIFIED = 56;
    //get_survey_details + get_survey_results
    const ERROR_RESPONSE_NO_SURVEY_ID_SPECIFIED = 58;
    //cancel_scheduled
    const ERROR_RESPONSE_NO_SENT_ID = 59;
    const ERROR_RESPONSE_INVALID_SENT_ID = 60;
    //delete_contact
    const ERROR_RESPONSE_SPECIFIED_NUMBER_NOT_FOUND = 61;
    //delete_group
    const ERROR_RESPONSE_CANNOT_DELETE_SPECIFIED_GROUP = 62;
    //get_survey_results
    const ERROR_RESPONSE_INVALID_START_SPECIFIED = 63;
    //check_keyword
    const ERROR_RESPONSE_NO_KEYWORD_SPECIFIED = 74;
    const ERROR_RESPONSE_KEYWORD_UNAVAILABLE = 75;
    const ERROR_RESPONSE_KEYWORD_TOO_SHORT = 76;
    const ERROR_RESPONSE_KEYWORD_TOO_LONG = 77;
    //short_url
    const ERROR_RESPONSE_NO_URL_SPECIFIED = 185;
    const ERROR_RESPONSE_INVALID_URL_SPECIFIED = 186;
    //send_sms + bulk_json
    const ERROR_RESPONSE_INVALID_SCHEDULE_TIME = 191;
    const ERROR_RESPONSE_CURRENT_TIME_NOT_ALLOWED = 192;
    //get_ticket_details
    const ERROR_RESPONSE_INVALID_TICKET_ID = 220;
    const ERROR_RESPONSE_NO_TICKET_ID = 221;
    //bulk_json
    const ERROR_RESPONSE_MESSAGE_FAILED_TO_SEND = 314;

    public function __construct($apiKey = null, $endpoint = null, $username = null, $hash = null, $transSender = null, $promoSender = null, HttpClient $httpClient = null)
    {
        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->hash = $hash;
        $this->transSender = $transSender;
        $this->promoSender = $promoSender;
        $this->http = $httpClient;
        $this->test = (env('TEXTLOCAL_TEST', true) == false ? false : true);
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
        if ($this->apiKey && !empty($this->apiKey)) {
            $params['apiKey'] = $this->apiKey;
        } else {
            $params['hash'] = $this->hash;
        }
        // Create request string
        $params['username'] = $this->username;

        return $params;
    }

    /**
     * Private function to construct and send the request and handle the response
     * @param       $command
     * @param array $params
     * @return array|mixed
     * @throws Exception
     */
    protected function api($command, $params = [], $method = 'POST')
    {
        $this->lastRequest = $this->appendBasicParams($params);

        $reponse = $this->sendRequest($command, $params, $method);
        $body = $reponse->getBody();

        $result = json_decode($body);
        //		var_dump($result);

        $this->parseErrors($result);

        return $result;
    }

    protected function parseErrors($result)
    {
        if (isset($result->errors)) {
            if (count($result->errors) > 0) {
                foreach ($result->errors as $error) {
                    switch ($error->code) {
                        case self::ERROR_RESPONSE_NO_VALID_NUMBER: throw new InvalidNumberException($error->message);
                        default:
                            throw new Exception($error->message);
                    }
                }
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
    protected function sendRequest($command, $params, $method = 'POST')
    {
        $url = $this->endpoint . $command . '/';

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'form_params' => $params
        ];

        return $this->httpClient()->request($method, $url, $options);
    }

    protected function resolveReports($numbers, $result, $message, $transactional, $unicode, $scheduleTime, $now, $customId)
    {
        if (!isset($result->batch_id)) {
            throw new \RuntimeException("Delivery report not found!");
        }

        $warnings = [];
        if (isset($result->warnings)) {
            foreach ($result->warnings as $warning) {
                $warnNumbers = explode(",", $warning->numbers);
                foreach ($warnNumbers as $number) {
                    $warnings[$number] = $warning->message;
                }
            }
        }
        if (isset($result->messages)) {
            $messages = collect($result->messages);
        }
        $reports = [];
        $batchId = $result->batch_id;
        foreach ($numbers as $number) {
            $report = null;
            if (isset($result->messages)) {
                $id = $messages->where('recipient', $number)->pluck('id');
                $report = (new SMSMessageStatusReport($message))->to($number)
                                ->identifedBy($id)->batch($batchId)->transactional($transactional)
                                ->unicode($unicode)->scheduledAt($scheduleTime)->requestedAt($now)->withCustom($customId);
            } else {
                $report = (new SMSMessageStatusReport($message))->to($number)
                                ->batch($batchId)->transactional($transactional)
                                ->unicode($unicode)->scheduledAt($scheduleTime)->requestedAt($now)->withCustom($customId);
            }
            if (isset($result->inDND) && in_array($number, $result->inDND)) {
                $report->status(SMSMessageStatusReport::STATUS_REJECTED)->unsubscribe(true);
            } elseif (array_key_exists($number, $warnings)) {
                $report->error($warnings[$number])->status(SMSMessageStatusReport::STATUS_FAILED);
            }
            $reports[] = $report;
        }

        return $reports;
    }

    protected function resolveDeliveryReport($number, $customId, $status, Carbon $deliveryTimestamp)
    {
        $reports = (new SMSMessageStatusReport(null))->to($number)->withCustom($customId)
                        ->status($status)->deliveredAt($deliveryTimestamp);
        if ($status === SMSMessageStatusReport::STATUS_REJECTED) {
            $reports->unsubscribe(true);
        }
        return [$reports];
    }

    protected function resolveSender($transactional = false)
    {
        if ($transactional) {
            return $this->transSender;
        } else {
            return $this->promoSender;
        }
    }

    protected function validateArgs(array $args)
    {
        if (array_key_exists('groupId', $args) && !is_numeric($args['groupId'])) {
            throw new Exception('Invalid group ID format. Must be a numeric group ID');
        }

        if (array_key_exists('message', $args) && empty($args['message'])) {
            throw new Exception('Empty message');
        }

        if (array_key_exists('sender', $args) && empty($args['sender'])) {
            throw new Exception('Empty sender name');
        }

        if (array_key_exists('numbers', $args) && empty($args['numbers'])) {
            throw new Exception('No phone numbers provided');
        }

        if (array_key_exists('fileSource', $args) && empty($args['fileSource'])) {
            throw new Exception('Empty file source');
        }
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
     * @param Carbon $scheduleTime
     * @param array $options test, receipt_url, custom, optouts, validity
     * @return array SMSMessageStatusReports
     * @throws Exception
     */
    public function sendSms(array $numbers, $message = null, $transactional = false, $unicode = false, Carbon $scheduleTime = null, array $options = [])
    {
        $sender = $this->resolveSender($transactional);

        $this->validateArgs(compact('numbers', 'message', 'sender'));

        $params = [
            'message' => rawurlencode($message),
            'numbers' => implode(',', $numbers),
            'sender' => rawurlencode($sender),
            'unicode' => $unicode,
        ];

        if (!is_null($scheduleTime)) {
            $params['schedule_time'] = $scheduleTime->timestamp;
        }

        $now = Carbon::now();
        $customId = SMSUtils::getCustomUniqueId($now);
        $options['custom'] = $customId;
        $result = $this->api('send', array_merge($options, $params));
        $reports = $this->resolveReports($numbers, $result, $message, $transactional, $unicode, $scheduleTime, $now, $customId);
        event(new SMSSentEvent($reports));

        return $reports;
    }

    protected function getTransformedStatus($status)
    {
        $convertedStatus = SMSMessageStatusReport::STATUS_PENDING;
        switch ($status) {
            case 'D':
                $convertedStatus = SMSMessageStatusReport::STATUS_DELIVERED;
                break;
            case 'U':
                $convertedStatus = SMSMessageStatusReport::STATUS_FAILED;
                break;
            case 'B':
                $convertedStatus = SMSMessageStatusReport::STATUS_REJECTED;
                break;
            default:
                break;
        }
        return $convertedStatus;
    }

    protected function validateDeliveryParams(array $requestParams)
    {
        $validator = Validator::make([
                    'status' => $requestParams['status'],
                    'number' => $requestParams['number'],
                    'custom_id' => $requestParams['customID'],
                    'delivery_timestamp' => $requestParams['datetime']
                        ], [
                    'status' => 'required|in:D,U,P,I,E,?,B',
                    'number' => 'required|exists:sms_reports,recipient',
                    'custom_id' => 'required|exists:sms_reports,custom_id',
                    'delivery_timestamp' => 'required',
        ]);
        if ($validator->fails()) {
            throw new Exception(json_encode($validator->errors()));
        }
    }

    /**
     * Send an SMS to a Group of contacts - group IDs can be retrieved from getGroups()
     * @param       $groupId
     * @param       $message
     * @param bool  $transactional
     * @param bool  $unicode
     * @param Carbon $scheduleTime
     * @param array $options test, receipt_url, custom, optouts, validity
     * @return array|mixed
     * @throws Exception
     */
    public function sendSmsGroup($groupId, $message = null, $transactional = false, $unicode = false, Carbon $scheduleTime = null, array $options = [])
    {
        $sender = $this->resolveSender($transactional);

        $this->validateArgs(compact('groupId', 'message', 'sender'));

        $params = [
            'message' => rawurlencode($message),
            'group_id' => $groupId,
            'sender' => rawurlencode($sender),
            'unicode' => $unicode,
        ];

        if (!is_null($scheduleTime)) {
            $params['schedule_time'] = $scheduleTime->timestamp;
        }

        $now = Carbon::now();
        $customId = SMSUtils::getCustomUniqueId($now);
        $options['custom'] = $customId;
        $options['receipt_url'] = route('sms.deliveryReport', 'textlocal');
        $result = $this->api('send', array_merge($options, $params));

        $numbers = collect($this->getContacts($groupId))
                        ->where('number', '!=', null)->pluck('number')->all();
        $reports = $this->resolveReports($numbers, $result, $message, $transactional, $unicode, $scheduleTime, $now, $customId);
        event(new SMSSentEvent($reports));

        return $reports;
    }

    /**
     * Send an MMS to a one or more comma separated contacts
     * @param       $numbers
     * @param       $fileSource - either an absolute or relative path, or http url to a file.
     * @param       $message
     * @param Carbon $scheduleTime
     * @param false $test
     * @param false $optouts
     * @return array|mixed
     * @throws Exception
     */
    public function sendMms(array $numbers, $fileSource = null, $message = null, Carbon $scheduleTime = null, $test = false, $optouts = false)
    {
        $this->validateArgs(compact('numbers', 'message', 'fileSource'));

        $params = [
            'message' => rawurlencode($message),
            'numbers' => implode(',', $numbers),
            'test' => $test,
            'optouts' => $optouts
        ];

        if (!is_null($scheduleTime)) {
            $params['schedule_time'] = $scheduleTime->timestamp;
        }

        /** Local file. POST to service */
        if (is_readable($fileSource)) {
            $params['file'] = '@' . $fileSource;
        } else {
            $params['url'] = $fileSource;
        }

        return $this->api('send_mms', $params);
    }

    /**
     * Send an MMS to a group - group IDs can be
     * @param       $groupId
     * @param       $fileSource
     * @param       $message
     * @param Carbon $scheduleTime
     * @param false $test
     * @param false $optouts
     * @return array|mixed
     * @throws Exception
     */
    public function sendMmsGroup($groupId, $fileSource = null, $message = null, Carbon $scheduleTime = null, $test = false, $optouts = false)
    {
        $this->validateArgs(compact('groupId', 'message', 'fileSource'));

        $params = [
            'message' => rawurlencode($message),
            'group_id' => $groupId,
            'test' => $test,
            'optouts' => $optouts
        ];

        if (!is_null($scheduleTime)) {
            $params['schedule_time'] = $scheduleTime->timestamp;
        }

        /** Local file. POST to service */
        if (is_readable($fileSource)) {
            $params['file'] = '@' . $fileSource;
        } else {
            $params['url'] = $fileSource;
        }

        return $this->api('send_mms', $params);
    }

    /*     * Get templates from an account * */

    public function getTemplates()
    {
        return $this->api('get_templates');
    }

    /** Check the availability of a keyword
     * @param $keyword
     * return array|mixed
     */
    public function checkKeyword($keyword)
    {
        $params = compact('keyword');
        return $this->api('check_keyword', $params);
    }

    /**
     * Create a new contact group
     * @param $name
     * @return string group id
     */
    public function createGroup($name)
    {
        $params = compact('name');
        $result = $this->api('create_group', $params);
        if (!isset($result->status) || $result->status != "success" || !isset($result->group->id)) {
            throw new \RuntimeException("Failed to create group");
        }
        return strval($result->group->id);
    }

    /**
     * Get contacts from a group - Group IDs can be retrieved with the getGroups() function
     * @param     $groupId
     * @return array SMSContacts
     */
    public function getContacts($groupId = self::GROUPID_CONTACTS)
    {
        $this->validateArgs(compact('groupId'));

        $params = [
            'group_id' => $groupId,
        ];

        $results = $this->api('get_contacts', $params);
        $contacts = [];

        if (!isset($results->contacts)) {
            throw new \RuntimeException("Contacts not found!");
        }

        foreach ($results->contacts as $result) {
            $number = isset($result->number) ? $result->number : null;
            $firstName = isset($result->first_name) ? $result->first_name : "";
            $lastName = isset($result->last_name) ? $result->last_name : "";
            $name = $firstName . $lastName;
            $contacts[] = new SMSContact($number, $name);
        }

        return $contacts;
    }

    /**
     * Create one number-only contacts in a specific group, defaults to 'My Contacts'
     * @param        $number
     * @param string $groupid
     * @return bool true on success
     */
    public function createContact($number, $groupid = self::GROUPID_CONTACTS)
    {
        return $this->createContacts($number, $groupid);
    }

    /**
     * Create one or more number-only contacts in a specific group, defaults to 'My Contacts'
     * @param        $numbers
     * @param string $groupid
     * @return bool true on success
     */
    public function createContacts($numbers, $groupid = self::GROUPID_CONTACTS)
    {
        $params = ["group_id" => $groupid];

        if (is_array($numbers)) {
            $params['numbers'] = implode(',', $numbers);
        } else {
            $params['numbers'] = $numbers;
        }

        $this->api('create_contacts', $params);
        return true;
    }

    /**
     * Create bulk contacts - with name and custom information from an array of:
     * [first_name] [last_name] [number] [custom1] [custom2] [custom3]
     *
     * @param array  $contacts array of TextLocalContact classes
     * @param string $groupid
     * @return array|mixed
     */
    public function createContactsBulk(array $contacts, $groupid = self::GROUPID_CONTACTS)
    {
        // JSON & URL-encode array
        $contacts = rawurlencode(json_encode($contacts));

        $params = [
            "group_id" => $groupid,
            "contacts" => $contacts
        ];

        return $this->api('create_contacts_bulk', $params);
    }

    /**
     * Get a list of groups and group IDs
     * @return array SMSGroups
     */
    public function getGroups()
    {
        $results = $this->api('get_groups');
        $groups = [];

        if (!isset($results->groups)) {
            throw new \RuntimeException("Groups not found!");
        }

        foreach ($results->groups as $result) {
            $groupId = isset($result->id) ? $result->id : "";
            $name = isset($result->name) ? $result->name : "";
            $count = isset($result->size) ? $result->size : 0;
            $groups[] = new SMSGroup($groupId, $name, $count);
        }

        return $groups;
    }

    /**
     * Get the status of a message based on the Message ID - this can be taken from sendSMS or from a history report
     * @param $messageid
     * @return array|mixed
     */
    public function getMessageStatus($messageid)
    {
        $params = ["message_id" => $messageid];
        return $this->api('status_message', $params);
    }

    /**
     * Get the status of a message based on the Batch ID - this can be taken from sendSMS or from a history report
     * @param $batchid
     * @return array|mixed
     */
    public function getBatchStatus($batchid)
    {
        $params = ["batch_id" => $batchid];
        return $this->api('status_batch', $params);
    }

    /**
     * Get sender names
     * @return array|mixed
     */
    public function getSenderNames()
    {
        return $this->api('get_sender_names');
    }

    /**
     * Get inboxes available on the account
     * @return array|mixed
     */
    public function getInboxes()
    {
        return $this->api('get_inboxes');
    }

    /**
     * Get Credit Balances
     * @return array
     */
    public function getBalance()
    {
        $result = $this->api('balance');
        return [
            'sms' => isset($result->balance->sms) ? $result->balance->sms : 0,
            'mms' => isset($result->balance->mms) ? $result->balance->mms : 0,
        ];
    }

    /**
     * Get messages from an inbox - The ID can be retrieved from getInboxes()
     * @param $inbox
     * @return array|bool|mixed
     */
    public function getMessages($inbox)
    {
        if (!isset($inbox)) {
            return false;
        }

        $options = ['inbox_id' => $inbox];
        return $this->api('get_messages', $options);
    }

    /**
     * Cancel a scheduled message based on a message ID from getScheduledMessages()
     * @param $id
     * @return array|bool|mixed
     */
    public function cancelScheduledMessage($id)
    {
        if (!isset($id)) {
            return false;
        }

        $options = ['sent_id' => $id];
        return $this->_sendRequest('cancel_scheduled', $options);
    }

    /**
     * Get Scheduled Message information
     * @return array|mixed
     */
    public function getScheduledMessages()
    {
        return $this->api('get_scheduled');
    }

    /**
     * Delete a contact based on telephone number from a group
     * @param     $number
     * @param int $groupid
     * @return array|bool|mixed
     */
    public function deleteContact($number, $groupid = self::GROUPID_CONTACTS)
    {
        if (!isset($number)) {
            return false;
        }

        $options = ['number' => $number, 'group_id' => $groupid];
        return $this->api('delete_contact', $options);
    }

    /**
     * Delete a group - Be careful, we can not recover any data deleted by mistake
     * @param $groupid
     * @return bool true on success
     */
    public function deleteGroup($groupid)
    {
        $options = ['group_id' => $groupid];
        $this->api('delete_group', $options);
        return true;
    }

    /**
     * Generic function to provide validation and the request method for getting all types of history
     * @param $type
     * @param $start
     * @param $limit
     * @param $min_time
     * @param $max_time
     * @return array|bool|mixed
     */
    public function getHistory($type, $start, $limit, $min_time, $max_time)
    {
        if (!isset($start) || !isset($limit) || !isset($min_time) || !isset($max_time)) {
            return false;
        }

        $options = compact('start', 'limit', 'min_time', 'max_time');
        return $this->api($type, $options);
    }

    /**
     * Get single SMS history (single numbers, comma seperated numbers when sending)
     * @param $start
     * @param $limit
     * @param $min_time             Unix timestamp
     * @param $max_time             Unix timestamp
     * @return array|bool|mixed
     */
    public function getSingleMessageHistory($start, $limit, $min_time, $max_time)
    {
        return $this->getHistory('get_history_single', $start, $limit, $min_time, $max_time);
    }

    /**
     * Get API SMS Message history
     * @param $start
     * @param $limit
     * @param $min_time             Unix timestamp
     * @param $max_time             Unix timestamp
     * @return array|bool|mixed
     */
    public function getAPIMessageHistory($start, $limit, $min_time, $max_time)
    {
        return $this->getHistory('get_history_api', $start, $limit, $min_time, $max_time);
    }

    /**
     * Get Email to SMS History
     * @param $start
     * @param $limit
     * @param $min_time             Unix timestamp
     * @param $max_time             Unix timestamp
     * @return array|bool|mixed
     */
    public function getEmailToSMSHistory($start, $limit, $min_time, $max_time)
    {
        return $this->getHistory('get_history_email', $start, $limit, $min_time, $max_time);
    }

    /**
     * Get group SMS history
     * @param $start
     * @param $limit
     * @param $min_time             Unix timestamp
     * @param $max_time             Unix timestamp
     * @return array|bool|mixed
     */
    public function getGroupMessageHistory($start, $limit, $min_time, $max_time)
    {
        return $this->getHistory('get_history_group', $start, $limit, $min_time, $max_time);
    }

    /**
     * Get a list of surveys
     * @return array|mixed
     */
    public function getSurveys()
    {
        return $this->api('get_surveys');
    }

    /**
     * Get a details of a survey
     * @return array|mixed
     */
    public function getSurveyDetails($surveyId)
    {
        $options = ['survey_id' => $surveyId];
        return $this->api('get_survey_details', $options);
    }

    /**
     * Get a the results of a given survey
     * @return array|mixed
     */
    public function getSurveyResults($surveyid, $start, $end)
    {
        $options = [
            'survey_id' => $surveyid,
            'start_date' => $start,
            'end_date' => $end,
        ];
        return $this->api('get_survey_results', $options);
    }

    public function sendDummyMessage(SMSMessage $smsMessage, array $options = ['test' => true])
    {
        try {
            $content = trim($smsMessage->content);
            $numbers = [intval($smsMessage->to)];
            $this->sendSms($numbers, $content, $smsMessage->transactional, $smsMessage->unicode, $smsMessage->scheduleTime, $options);
        } catch (InvalidNumberException $e) {
            $this->unsubscribeNumber($smsMessage->to);
        }
    }

    public function sendMessage(SMSMessage $smsMessage)
    {
        if ($this->test == true) {
            $this->sendDummyMessage($smsMessage);
        } else {
            try {
                $content = trim($smsMessage->content);
                $numbers = [intval($smsMessage->to)];
                $this->sendSms($numbers, $content, $smsMessage->transactional, $smsMessage->unicode, $smsMessage->scheduleTime);
            } catch (InvalidNumberException $e) {
                $this->unsubscribeNumber($smsMessage->to);
            }
        }
    }

    public function unsubscribeNumber($number)
    {
        $reports[] = (new SMSMessageStatusReport(null))->to($number);
        event(new SMSUnsubscribeEvent($reports));
    }

    public function deliverReport(Request $request)
    {
        $requestParams = $request->all();
        $this->validateDeliveryParams($requestParams);
        $deliveryTimestamp = new Carbon($requestParams['datetime']);
        $status = $this->getTransformedStatus($requestParams['status']);
        $reports = $this->resolveDeliveryReport($requestParams['number'], $requestParams['customID'], $status, $deliveryTimestamp);
        event(new SMSDeliveryEvent($reports));
    }
}
