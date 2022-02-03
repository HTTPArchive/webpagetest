<?php declare(strict_types=1);

require_once(__DIR__ . '/../common.inc');

use Respect\Validation\Rules;
use Respect\Validation\Exceptions\NestedValidationException;

use WebPageTest\Template;
use WebPageTest\Util;
use WebPageTest\ValidatorPatterns;
use WebPageTest\RequestContext;

(function(RequestContext $request) {
  $request_method = strtoupper($_SERVER['REQUEST_METHOD']);

  if ($request_method === 'POST') {
    $csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
    if ($csrf_token !== $_SESSION['csrf_token']) {
      echo "WRONG. Your token should be {$csrf_token} but is instead {$_SESSION['csrf_token']}";
      exit;
    }

    $symphonyValidator = new Rules\AllOf(
      new Rules\Regex('/' . ValidatorPatterns::$symphony . '/'),
      new Rules\Length(0, 32)
    );

    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

    if ($type == 'contact_info') {
      $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
      $first_name = filter_input(INPUT_POST, 'first-name');
      $last_name = filter_input(INPUT_POST, 'last-name');
      $company_name = filter_input(INPUT_POST, 'company-name');

      try {
        $symphonyValidator->assert($first_name);
        $symphonyValidator->assert($last_name);
        $symphonyValidator->assert($company_name);
      } catch (NestedValidationException $e) {
        print_r($e->getMessages([
          'regex' => 'input cannot contain <, >, or &#'
        ]));
        exit();
      }

      $email = $request->getUser()->getEmail();

      $options = array(
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'company_name' => $company_name
      );

      try {
        $results = $request->getClient()->updateUserContactInfo($id, $options);

        $protocol = getUrlProtocol();
        $host = Util::getSetting('host');
        $route = '/oauth2/account.php';
        $redirect_uri = "{$protocol}://{$host}{$route}";

        header("Location: {$redirect_uri}");
      } catch (Exception $e) {
        echo var_dump($e);
        exit();
      }
    }
  } else if ($request_method == 'GET') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(35));

    $is_paid = $request->getUser()->isPaid();
    $user_contact_info = $request->getClient()->getUserContactInfo($request->getUser()->getUserId());

    $contact_info = array(
      'layout_theme' => 'b',
      'is_paid' => $is_paid,
      'first_name' => htmlspecialchars($user_contact_info['firstName']),
      'last_name' => htmlspecialchars($user_contact_info['lastName']),
      'email' => $request->getUser()->getEmail(),
      'company_name' => htmlspecialchars($user_contact_info['companyName']),
      'id' => $request->getUser()->getUserId()
    );

    // TODO - make the call for paid user data
    $billing_info = array();

    if (!$is_paid) {
      $billing_info = array(
        'plan' => '1,000 runs',
        'remaining' => '998',
        'run_renewal' => '12/16/2021',
        'price' => '$180',
        'payment_frequency' => 'Annually',
        'plan_renewal' => '11/17/2022',
        'status' => 'active',
        'cc_number' => '370844******3579',
        'cc_expiration_date' => '11/2042',
        'billing_history' => array(
          array(
            'date_time_stamp' => 'Nov 17 2021 15:39:23',
            'credit_card' => 'AMEX',
            'cc_number' => '370844******3579',
            'amount' => '$180'
          ),
          array(
            'date_time_stamp' => 'Nov 17 2020 15:39:23',
            'credit_card' => 'AMEX',
            'cc_number' => '370844******3579',
            'amount' => '$180'
          )
        )
      );
    } else {

      $info = $request->getClient()->getUnpaidAccountpageInfo();
      $plans = $info['wptPlans'];
      $annual_plans = array();
      $monthly_plans = array();
      usort($plans, function ($a, $b) {
        if ($a['price'] == $b['price']) {
          return 0;
        }
        return ($a['price'] < $b['price']) ? -1 : 1;
       });
      foreach($plans as $plan) {
        if ($plan['billingFrequency'] == 1) {
          $monthly_plans[] = $plan;
        } else {
          $plan['monthly_price'] = $plan['price'] / 12.00;
          $annual_plans[] = $plan;
        }
      }
      $billing_info = array(
        'braintree_client_token' => $info['braintreeClientToken'],
        'annual_plans' => $annual_plans,
        'monthly_plans' => $monthly_plans
      );
    }

    $results = array_merge($contact_info, $billing_info);
    $results['csrf_token'] = $_SESSION['csrf_token'];
    $results['validation_pattern'] = ValidatorPatterns::$symphony;

    $tpl = new Template('account');
    echo $tpl->render('my-account', $results);
    exit();
  }
})($request);
?>
