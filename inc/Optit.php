<?php

use Optit\Interest;
use Optit\Keyword;
use Optit\Member;
use Optit\RESTclient;
use Optit\Subscription;

class Optit {

  private $http;

  // This is an ugly buffer, but it will do the trick for drupal's pagination purposes.
  public $totalPages;
  public $currentPage;

  // This is even uglier page property, which allows me not to set page numbers in method parameters.
  private $page = 1;

  public function __construct($username, $password, $apiEndpoint) {
    $this->http = new RESTclient($username, $password, $apiEndpoint);
  }


  // ### Keywords
  // ###
  public function keywordsGet($params = null) {
    $response = $this->http->get('keywords', $params);

    $keywords = array();
    foreach ($response['keywords'] as $keyword) {
      $keywords[] = Keyword::create($keyword['keyword']);
    }

    return $keywords;
  }

  public function keywordGet($id) {
    $response = $this->http->get("keywords/{$id}");
    return Keyword::create($response['keyword']);
  }

  public function keywordExists($name) {
    $urlParams = array();
    $urlParams['keyword'] = $name;

    $response = $this->http->get("keywords/exists", $urlParams);

    return $response['keyword']['exists'];
  }

  public function keywordCreate(Keyword $keyword) {
    $keyword = $keyword->toArray();
    $keyword['keyword'] = $keyword['keyword_name'];
    unset($keyword['keyword_name']);

    $response = $this->http->post("keywords", null, $keyword);
  }

  /**
   * @todo: This call does not work, probably due to API server error!
   */
  public function keywordUpdate($id, Keyword $keyword) {
    // Prepare new keyword for being saved
    $keyword = $keyword->toArray();
    $keyword['keyword'] = $keyword['keyword_name'];
    unset($keyword['keyword_name']);

    $response = $this->http->put("keywords/{$id}", null, $keyword);
  }



  // ### Interests
  //

  /**
   * Get a list of interests
   */
  public function interestsGet($keywordId, $name = NULL) {
    $urlParams = array();

    if ($name) {
      $urlParams['name'] = $name;
    }

    $response = $this->http->get("keywords/{$keywordId}/interests", $urlParams);

    $interests = array();
    foreach ($response['interests'] as $i) {
      $interests[] = Interest::create($i['interest']);
    }

    return $interests;
  }

  /**
   * Get a list of interests filtered by phone number.
   */
  public function interestsGetByPhone($keywordId, $phone) {
    $response = $this->http->get("keywords/{$keywordId}/subscriptions/{$phone}/interests");

    $interests = array();
    foreach ($response['interests'] as $i) {
      $interests[] = Interest::create($i['interest']);
    }

    return $interests;
  }

  /**
   * Get an individual interest.
   */
  public function interestGet($interestId) {
    $response = $this->http->get("interests/{$interestId}");

    return Interest::create($response['interest']);
  }

  /**
   * Create a new interest.
   */
  public function interestCreate($keywordId, $name, $description = null) {
    // @todo: Handle http request failure.

    // Prepare params.
    $postParams = array();
    $postParams['name'] = $name;
    if ($description) {
      $postParams['description'] = $description;
    }

    // Make the request.
    $response = $this->http->post("keywords/{$keywordId}/interests", null, $postParams);

    // Return Interest object.
    return Interest::create($response['interest']);
  }


  /**
   * Get a list of subsciptions for an interest.
   * http://api.optitmobile.com/1/interests/{interest_id}/subscriptions.{format}
   *
   * @param null $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   * @param null $memberId
   *   member_id - the member_id of a member. It is the ID attribute in the Members entity and can be viewed using
   *   the Get Member method.
   * @param null $firstName
   *   first_name - first name of the member
   * @param null $lastName
   *   last_name - last name of the member
   * @param null $zip
   *   zip - zip code or postal code of the member
   * @param null $gender
   *   gender - gender of the member. Values: [male, female]
   * @param null $signupDateStart
   *   signup_date_start - yyyymmddhhmmss
   * @param null $signupDateEnd
   *   signup_date_end - yyyymmddhhmmss
   *
   * @return mixed
   *   an array of subscription entities.
   *
   * @todo: Reduce duplication of code in interestGetSubscriptions() and subscriptionsGet()
   *
   */
  public function interestGetSubscriptions($interestId, $phone = null, $memberId = null, $firstName = null, $lastName = null, $zip = null, $gender = null, $signupDateStart = null, $signupDateEnd = null) {

    // Prepare params.
    $urlParams = array();
    $urlParams['page'] = $this->getPage();
    if ($phone) {
      $urlParams['phone'] = $phone;
    }
    if ($memberId) {
      $urlParams['member_id'] = $memberId;
    }
    if ($firstName) {
      $urlParams['first_name'] = $firstName;
    }
    if ($lastName) {
      $urlParams['last_name'] = $lastName;
    }
    if ($zip) {
      $urlParams['zip'] = $zip;
    }
    if ($gender) {
      $urlParams['gender'] = $gender;
    }
    if ($signupDateStart) {
      $urlParams['signup_date_start'] = $signupDateStart;
    }
    if ($signupDateEnd) {
      $urlParams['signup_date_end'] = $signupDateEnd;
    }

    $response = $this->http->get("interests/{$interestId}/subscriptions", $urlParams);
    $this->collectStats($response);

    $subscriptions = array();
    foreach ($response['subscriptions'] as $record) {
      $subscriptions[] = Subscription::create($record['subscription']);
    }

    $this->totalPages = $response['total_pages'];

    return $subscriptions;
  }

  /**
   * Add a subscription to an interest.
   * http://api.optitmobile.com/1/interests/{interest_id}/subscriptions.{format}
   *
   * @param int $interestId
   *   ID of the keyword
   * @param null $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. (Phone or member_id is
   *   required)  Example: 12225551212
   * @param null $memberId
   *   member_id - the member_id of a member. It is the ID attribute in the Members entity and can be viewed using the
   *   Get Member method. (Phone or member_id is required)
   *
   * @return bool
   *   TRUE if successful request.
   */
  public function interestSubscribe($interestId, $phone = null, $memberId = null) {
    if (!$phone && !$memberId) {
      return false;
    }
    $postParams = array();
    if ($phone) {
      $postParams['phone'] = $phone;
    }
    if ($memberId) {
      $postParams['member_id'] = $memberId;
    }

    if ($this->http->post("interests/{$interestId}/subscriptions", null, $postParams)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Delete a subscription from interest.
   * http://api.optitmobile.com/1/interests/{interest_id}/subscriptions/{phone}.{format}
   *
   * @param int $interestId
   *   ID of the keyword
   * @param string $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   *
   *
   * @return bool
   *   TRUE if successful request.
   */
  public function interestUnsubscribe($interestId, $phone) {
    if ($this->http->delete("interests/{$interestId}/subscriptions/{$phone}")) {
      return TRUE;
    }
    return FALSE;
  }


  // ### Members
  //


  /**
   * Get a list of members.
   * http://api.optitmobile.com/1/members.{format}
   */
  public function membersGet($phone = null, $firstName = null, $lastName = null, $zip = null, $gender = null) {
    // Prepare params.
    $urlParams = array();
    $urlParams['page'] = $this->getPage();
    if ($phone) {
      $urlParams['phone'] = $phone;
    }
    if ($firstName) {
      $urlParams['first_name'] = $firstName;
    }
    if ($lastName) {
      $urlParams['last_name'] = $lastName;
    }
    if ($zip) {
      $urlParams['zip'] = $zip;
    }
    if ($gender) {
      $urlParams['gender'] = $gender;
    }

    $response = $this->http->get('members', $urlParams);
    $this->collectStats($response);

    $members = array();
    foreach ($response['members'] as $record) {
      $members[] = Member::create($record['member']);
    }

    $this->totalPages = $response['total_pages'];

    return $members;
  }


  // ##
  // ## Subscriotions

  /**
   * * Get a list of members.
   * http://api.optitmobile.com/1/keywords/{keyword_id}/subscriptions.{format}
   *
   * @param int $keywordId
   *   ID of the keyword
   * @param null $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   * @param null $memberId
   *   member_id - the member_id of a member. It is the ID attribute in the Members entity and can be viewed using
   *   the Get Member method.
   * @param null $firstName
   *   first_name - first name of the member
   * @param null $lastName
   *   last_name - last name of the member
   * @param null $zip
   *   zip - zip code or postal code of the member
   * @param null $gender
   *   gender - gender of the member. Values: [male, female]
   * @param null $signupDateStart
   *   signup_date_start - yyyymmddhhmmss
   * @param null $signupDateEnd
   *   signup_date_end - yyyymmddhhmmss
   *
   * @return mixed
   *   an array of subscription entities.
   *
   * @todo: Reduce duplication with interestGetSubscriptions().
   * @todo: Rename to keywordGetSubscriptions(), standardize!!.
   */
  public function subscriptionsGet($keywordId, $phone = null, $memberId = null, $firstName = null, $lastName = null, $zip = null, $gender = null, $signupDateStart = null, $signupDateEnd = null) {
    // Prepare params.
    $urlParams = array();
    $urlParams['page'] = $this->getPage();
    if ($phone) {
      $urlParams['phone'] = $phone;
    }
    if ($memberId) {
      $urlParams['member_id'] = $memberId;
    }
    if ($firstName) {
      $urlParams['first_name'] = $firstName;
    }
    if ($lastName) {
      $urlParams['last_name'] = $lastName;
    }
    if ($zip) {
      $urlParams['zip'] = $zip;
    }
    if ($gender) {
      $urlParams['gender'] = $gender;
    }
    if ($signupDateStart) {
      $urlParams['signup_date_start'] = $signupDateStart;
    }
    if ($signupDateEnd) {
      $urlParams['signup_date_end'] = $signupDateEnd;
    }

    $response = $this->http->get("keywords/{$keywordId}/subscriptions", $urlParams);
    $this->collectStats($response);

    $subscriptions = array();
    foreach ($response['subscriptions'] as $record) {
      $subscriptions[] = Subscription::create($record['subscription']);
    }

    $this->totalPages = $response['total_pages'];

    return $subscriptions;
  }

  /**
   * Get individual subscription
   * http://api.optitmobile.com/1/keywords/{keyword_id}/subscriptions/{phone}.{format}
   *
   * @param $keywordId
   *   ID of the keyword
   * @param $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   *
   * @return Subscription
   */
  public function subscriptionGetByPhone($keywordId, $phone) {
    $response = $this->http->get("keywords/{$keywordId}/subscriptions/{$phone}");
    dsm($response);
    return Subscription::create($response['subscription']);
  }

  /**
   * Subscribe a member to a keyword.
   * http://api.optitmobile.com/1/keywords/{keyword_id}/subscriptions.{format}
   *
   * @param int $keywordId
   *   ID of the keyword
   * @param null $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. (Phone or member_id is
   *   required)  Example: 12225551212
   * @param null $memberId
   *   member_id - the member_id of a member. It is the ID attribute in the Members entity and can be viewed using the
   *   Get Member method. (Phone or member_id is required)
   * @param null $interestId
   *   interest_id - add this user to one or many interests. For multiple interests, please comma separate the
   *   interest_ids. It is the ID attribute in the Interest entity and can be viewed using the Get Interest method.
   * @param null $firstName
   *   first_name - first name of the member
   * @param null $lastName
   *   last_name - last name of the member
   * @param null $address1
   *   address1 - address line 1 of the member
   * @param null $address2
   *   address2 - address line 2 of the member
   * @param null $city
   *   city - city of the member
   * @param null $state
   *   state - state of the member as a two character abbreviation
   * @param null $zip
   *   zip - zip code or postal code of the member
   * @param null $gender
   *   gender - gender of the member. Values: [male, female]
   * @param null $birthDate
   *   birth_date - birthdate in the format yyyymmdd
   * @param null $emailAddress
   *   email_address - email address of the member
   *
   * @return bool|Subscription
   *   false if request did not succeed. Otherwise - Subscription object.
   */
  public function subscriptionCreate($keywordId, $phone = null, $memberId = null, $interestId = null, $firstName = null, $lastName = null, $address1 = null, $address2 = null, $city = null, $state = null, $zip = null, $gender = null, $birthDate = null, $emailAddress = null) {

    // Validation step.
    if (!$phone && !$memberId) {
      return false;
    }

    // Preparing params.
    $postParams = array();
    if ($phone) {
      $postParams['phone'] = $phone;
    }
    if ($memberId) {
      $postParams['member_id'] = $memberId;
    }
    if ($interestId) {
      $postParams['interest_id'] = $interestId;
    }
    if ($firstName) {
      $postParams['first_name'] = $firstName;
    }
    if ($lastName) {
      $postParams['last_name'] = $lastName;
    }
    if ($address1) {
      $postParams['address1'] = $address1;
    }
    if ($address2) {
      $postParams['address2'] = $address2;
    }
    if ($city) {
      $postParams['city'] = $city;
    }
    if ($state) {
      $postParams['state'] = $state;
    }
    if ($zip) {
      $postParams['zip'] = $zip;
    }
    if ($gender) {
      $postParams['gender'] = $gender;
    }
    if ($birthDate) {
      $postParams['birth_date'] = $birthDate;
    }
    if ($emailAddress) {
      $postParams['email_address'] = $emailAddress;
    }

    if ($response = $this->http->post("keywords/{$keywordId}/subscriptions", null, $postParams)) {
      return new Subscription($postParams);
    }
    return false;
  }

  /**
   * Unsubscribe user from all keywords.
   * http://api.optitmobile.com/1/subscription/{phone}.{format}
   *
   * @param $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   *
   * @return bool
   */
  public function subscriptionsCancelAllKeywords($phone) {
    if ($response = $this->http->delete("subscription/{$phone}")) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Unsubscribe member from one keyword.
   * http://api.optitmobile.com/1/keywords/{keyword_id}/subscription/{phone}.{format}
   *
   * @param string $phone
   *   phone - mobile phone number of the member with country code - 1 for U.S. phone numbers. Example: 12225551212
   * @param int $keywordId
   *   ID of the keyword
   *
   * @return bool
   */
  public function subscriptionCancelByKeyword($phone, $keywordId) {
    if ($response = $this->http->delete("keywords/{$keywordId}/subscription/{$phone}")) {
      return TRUE;
    }
    return FALSE;
  }


  /**
   * Send a message to one or more users
   * http://api.optitmobile.com/1/sendmessage/keywords.{format}
   *
   * @param string $phone
   *   Single or multiple comma separated phone numbers.
   * @param int $keywordId
   *   ID of the keyword.
   * @param string $title
   *   Title of the message.
   * @param string $message
   *   Message to be set to subscribers.
   *
   * @return bool
   */
  public function messagePhone($phone, $keywordId, $title, $message) {
    $postParams = array();
    $postParams['phone'] = $phone;
    $postParams['keyword_id'] = $keywordId;
    $postParams['title'] = $title;
    $postParams['message'] = $message;
    if ($response = $this->http->post("sendmessage", null, $postParams)) {
      return TRUE;
    }
    return FALSE;
  }


  /**
   * Send a message to all users subscribed to a given keyword
   * http://api.optitmobile.com/1/sendmessage/keywords.{format}
   *
   * @param int $keywordId
   *   ID of the keyword
   * @param string $title
   *   Title of the message
   * @param string $message
   *   Message to be set to subscribers
   *
   * @return bool
   */
  public function messageKeyword($keywordId, $title, $message) {
    $postParams = array();
    $postParams['keyword_id'] = $keywordId;
    $postParams['title'] = $title;
    $postParams['message'] = $message;
    if ($response = $this->http->post("sendmessage/keywords", null, $postParams)) {
      return TRUE;
    }
    return FALSE;
  }


  /**
   * Send a message to all users subscribed to a given keyword
   * http://api.optitmobile.com/1/sendmessage/keywords.{format}
   *
   * @param int $interestId
   *   ID of the interest
   * @param string $title
   *   Title of the message
   * @param string $message
   *   Message to be set to subscribers
   *
   * @return bool
   */
  public function messageInterest($interestId, $title, $message) {
    $postParams = array();
    $postParams['interest_id'] = $interestId;
    $postParams['title'] = $title;
    $postParams['message'] = $message;
    if ($response = $this->http->post("sendmessage/interests", null, $postParams)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Opt It SMS Bulk Send Message.
   * http://api.optitmobile.com/1/sendmessage/bulk.{format}
   *
   * @param array $array
   *   Array of all keywords, with a sub-array of all messages, with a sub-sub-array of keywords associated to these
   *   messages. It is a mess. @todo: Create a bulk messaging method which accepts Message objects and handles the rest.
   *
   * @return bool
   */
  public function messageBulkArray($array) {
    // Prepare XML document.
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><keywords/>');
    foreach ($array as $keywordId => $messages) {
      $keywordObj = $xml->addChild('keyword');
      $keywordObj->addAttribute('id', $keywordId);
      $messagesObj = $keywordObj->addChild('messages');
      foreach ($messages as $message) {
        $messageObj = $messagesObj->addChild('message');
        $messageObj->addAttribute('title', $message['title']);
        $messageObj->addAttribute('text', $message['message']);
        $recipientsObj = $messageObj->addChild('recipients');
        foreach ($message['phones'] as $phone) {
          $recipientsObj->addChild('phone', $phone);
        }
      }
    }

    // Prepare a request.
    $postParams = array();
    $postParams['data'] = $xml->asXML();
    $options = array('headers' => array('Content-Type' => 'text/xml'));

    // Talk to the API.
    if ($response = $this->http->post("sendmessage/bulk", null, $postParams, $options)) {
      return TRUE;
    }
    return FALSE;
  }


  public function setPage($page) {
    $this->page = $page;
    return $this;
  }

  /**
   * This method makes sure that once used, page gets reset to initial value - 1. So that next query does not get polluted
   * with previous query's pagination. Ugly, but efficient.
   */
  public function getPage() {
    $page = $this->page;
    $this->page = 1;
    return $page;
  }

  /**
   * A little bit ugly method and logic in general. It collects current page and total pages from the response and
   * populates temporary properties. This was the least invasive way of getting stats to Drupal's paginator.
   */
  private function collectStats($response) {
    $this->totalPages = $response['total_pages'];
    $this->currentPage = $response['current_page'];
  }
}
