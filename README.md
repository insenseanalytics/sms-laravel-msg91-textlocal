# Insense/Laravel SMS

A package for the Laravel Framework for sending sms. This package currently supports Textlocal and MSG91 APIs but can be easily extended for others as well. We are open to PRs to extend this other services.

This package provides a wrapper Facade around the internal sms implementation of the supported sms APIs.

##### Basic Example

```php
$recipient1 = "XXXXXXXXXX";
$recipient2 = "XXXXXXXXXX";
$recipients = [$recipient1, $recipient2];
$msgText = "Your SMS text should be typed here";
SMS::driver()->sendSms($recipients, $msgText, $isTransactional);	
```

## Version Compatibility

This package currently supports Laravel 5.1 and up.

## Installation

Install the package via composer

```bash
composer require insense/laravel-sms
```

If using Laravel 5.1 to 5.4, Register the ServiceProvider and (optionally) the Facade

```php
// config/app.php

'providers' => [
    ...
    \Insense\LaravelSMS\Providers\SMSChannelServiceProvider::class,

];

...

'aliases' => [
	...
    'SMS' => \Insense\LaravelSMS\Facades\SMS::class,
],
```

Next, publish the config file with the following `artisan` command.<br />

```bash
php artisan vendor:publish --provider="Insense\LaravelSMS\Providers\SMSChannelServiceProvider" --tag="config"
```

or if using Laravel 5.5+ <br />

```bash
php artisan vendor:publish
```
Now, run migrations 

```bash
php artisan migrate
```

After publishing, add and fill the next values to your [`.env` file](https://laravel.com/docs/configuration#environment-configuration)

```bash
# Default SMS Driver value can be from "msg91" or "textlocal"
SMS_DRIVER=

# msg91 (private) API key
MSG91_APIKEY=

# msg91 api end point, this can be changed check same from msg91 api doc
MSG91_ENDPOINT=https://api.msg91.com/api/

# msg91 promotional message sender code to be shown to user
MSG91_PROMOSENDER=777777

# msg91 transactional message sender code to be shown to user
MSG91_TRANSSENDER=INSMS

# Now add Text Local APIs configurations

# TextLocal (private) API key
TEXTLOCAL_APIKEY=

# TextLocal Api registration username
TEXTLOCAL_USERNAME=

# TextLocal Api Hascode 
TEXTLOCAL_HASH=

# textlocal api end point, this can be changed check same from textlocal api doc
TEXTLOCAL_ENDPOINT=https://api.textlocal.in/

# textlocal promotional message sender code to be shown to user
TEXTLOCAL_TRANSSENDER=TXTLCL

# textlocal transactional message sender code to be shown to user
TEXTLOCAL_PROMOSENDER=TXTLCL
```

You can also configure the package in your `config/sms.php`.

Add this in you models package, to save SMS delivery reports

```php
class SMSReport extends BaseModel
{
	/**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'sms_report_id';
	
	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "sms_reports";
}
```
Add appropriate listners of SMS events in your `EventServiceProvider` 

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    // to listen for Delivery Report of SMS, populate `SMSReport` Model
    'Insense\LaravelSMS\Events\SMSDeliveryEvent' => [
        'App\Listeners\YourListener1',
    ],
    // to listen for sms sent api triggered, client can now wait for delivery report
    'Insense\LaravelSMS\Events\SMSSentEvent' => [
        'App\Listeners\YourListener2',
    ],
    // to listen for unsubscribed any user(Laravel User) from application because of incorrect number
    'Insense\LaravelSMS\Events\SMSUnsubscribeEvent' => [
        'App\Listeners\YourListener3',
    ],
];
```



### HTTP Client Dependency : Guzzle Http
Guzzle Http client is used to send requests to API, It must be installed in application, if not installed, it will be installed automatically.

## Usage

The Insense SMS package offers most of the functionality to send group SMS and batch SMS

The `SMS::sendSms()` method may be used to send a sms message:

```php
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
SMS::sendSms(array $numbers, $message = null, $transactional = false, $unicode = false, Carbon $scheduleTime = null, array $options = []);
```

The `SMS::sendSmsGroup()` method may be used to send a sms message:

```php
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
SMS::sendSmsGroup($groupId, $message = null, $transactional = false, $unicode = false, Carbon $scheduleTime = null, array $options = []);
```

The `SMS::createGroup()` method may be used to create a sms group:

```php
/**
 * Create a new contact group
 * @param $group_name
 * @return string group id
*/
SMS::createGroup($group_name);
```
The `SMS::getContacts()` method may be used to get contacts sms group:

```php
/**
 * Get contacts from a group - Group IDs can be retrieved with the getGroups() function
 * @param     $groupId
 * @return array SMSContacts
*/
SMS::getContacts($groupId = null);
```
<br>
<br/>

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
