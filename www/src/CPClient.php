<?php

declare(strict_types=1);

namespace WebPageTest;

use Exception as BaseException;
use GraphQL\Query;
use GraphQL\Mutation;
use GraphQL\Variable;
use GraphQL\Client as GraphQLClient;
use GuzzleHttp\Client as GuzzleClient;
use WebPageTest\AuthToken;
use WebPageTest\Exception\ClientException;

class CPClient
{
    private GuzzleClient $auth_client;
    private GraphQLClient $graphql_client;
    public ?string $client_id;
    public ?string $client_secret;
    public ?string $grant_type;

    public function __construct(string $host, array $options = [])
    {
        $auth_client_options = $options['auth_client_options'] ?? array();
        $this->client_id = $auth_client_options['client_id'] ?? null;
        $this->client_secret = $auth_client_options['client_secret'] ?? null;
        $this->grant_type = $auth_client_options['grant_type'] ?? null;
        $this->auth_client = new GuzzleClient($auth_client_options);
        $this->graphql_client = new GraphQLClient($host);

        $this->host = $host;
    }

    public function authenticate(string $access_token): void
    {
        $this->graphql_client = new GraphQLClient(
            $this->host,
            ['Authorization' => "Bearer {$access_token}"]
        );
    }

    public function login(string $username, string $password): AuthToken
    {
        if (is_null($this->client_id) || is_null($this->client_secret) || is_null($this->grant_type)) {
            throw new BaseException("Client ID, Client Secret, and Grant Type all must be set in order to login");
        }

        $body = array(
            'form_params' => array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => $this->grant_type,
                'username' => $username,
                'password' => $password
            )
        );
        try {
            $response = $this->auth_client->request('POST', '/auth/connect/token', $body);
        } catch (BaseException $e) {
            throw new ClientException($e->getMessage());
        }
        $json = json_decode((string)$response->getBody());
        return new AuthToken((array)$json);
    }

    public function refreshAuthToken(string $refresh_token): AuthToken
    {
        if (is_null($this->client_id) || is_null($this->client_secret) || is_null($this->grant_type)) {
            throw new BaseException("Client ID, Client Secret, and Grant Type all must be set in order to login");
        }

        $body = array(
            'form_params' => array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token
            )
        );
        try {
            $response = $this->auth_client->request('POST', '/auth/connect/token', $body);
        } catch (BaseException $e) {
            throw new ClientException($e->getMessage());
        }
        $json = json_decode((string)$response->getBody());
        return new AuthToken((array)$json);
    }

    public function revokeToken(string $token, string $type = 'access_token'): void
    {
        $body = array(
            'form_params' => array(
                'token' => $token,
                'token_type_hint' => $type,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret
            )
        );
        try {
            $this->auth_client->request('POST', '/auth/connect/revocation', $body);
        } catch (BaseException $e) {
            throw new ClientException($e->getMessage());
        }
    }

    public function getUserDetails(): array
    {
        $gql = (new Query('userIdentity'))
              ->setSelectionSet([
                (new Query('activeContact'))
                  ->setSelectionSet([
                    'id',
                    'name',
                    'email',
                    'isWptPaidUser',
                    'isWptAccountVerified'
                  ])
              ]);

        try {
            $account_details = $this->graphql_client->runQuery($gql, true);
            return $account_details->getData()['userIdentity']['activeContact'];
        } catch (BaseException $e) {
            throw new ClientException($e->getMessage());
        }
    }

    public function getUserContactInfo(int $id): array
    {
        $gql = (new Query('contact'))
        ->setVariables([
        new Variable('id', 'ID', true)
        ])
        ->setArguments(['id' => '$id'])
        ->setSelectionSet([
        'companyName',
        'firstName',
        'lastName'
        ]);

        $variables_array = array('id' => $id);
        $contact_info = $this->graphqlClient->runQuery($gql, true, $variables_array);
        return $contact_info->getData()['contact'][0];
    }

    public function getUnpaidAccountpageInfo(): array
    {
        $gql = (new Query())
        ->setSelectionSet([
        'braintreeClientToken',
        (new Query('wptPlans'))
          ->setSelectionSet([
            'id',
            'name',
            'price',
            'billingFrequency',
            'billingDayOfMonth',
            'currencyIsoCode',
            'numberOfBillingCycles',
            'trialDuration',
            'trialPeriod',
            (new Query('discount'))
              ->setSelectionSet([
                'amount',
                'numberOfBillingCycles'
              ])
          ])
        ]);
        $page_info = $this->graphqlClient->runQuery($gql, true);
        return $page_info->getData();
    }

    public function updateUserContactInfo(string $id, array $options): array
    {
        $gql = (new Mutation('wptContactUpdate'))
        ->setVariables([
        new Variable('contact', 'ContactUpdateInputType', true)
        ])
        ->setArguments([
        'contact' => '$contact'
        ])
        ->setSelectionSet([
        'id',
        'firstName',
        'lastName',
        'companyName',
        'email'
        ]);

        $variables_array = array('contact' => [
        'id' => $id,
        'email' => $options['email'],
        'firstName' => $options['first_name'],
        'lastName' => $options['last_name'],
        'companyName' => $options['company_name']
        ]);

        $results = $this->graphqlClient->runQuery($gql, true, $variables_array);
        return $results->getData();
    }
}
