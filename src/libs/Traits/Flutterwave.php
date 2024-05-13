<?php

namespace Basttyy\FxDataServer\libs\Traits;

use BadMethodCallException;
use Closure;
use GuzzleHttp\Client;
use ReflectionClass;
use ReflectionMethod;

trait Flutterwave
{
    /**
     * Create a payment/subscription plan
     * 
     * @param int $amount
     * @param string $currency
     * @param string $name
     * @param string $interval
     * 
     * @return false|\stdClass
     */
    protected static function createPaymentPlan($amount, $currency, $name, $interval)
    {
        $client = new Client([ 'base_uri' => env('FLWV_BASE_URL')]);

        $resp = $client->request('POST', 'payment-plans', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('FLWV_SECRET_KEY')
            ],
            'body' => json_encode([
                'amount' => $amount,
                'currency' => $currency,
                'name' => $name,
                'interval' => $interval
            ])
        ]);

        if ($resp->getStatusCode()  !== 200) {
            return false;
        }

        return json_decode($resp->getBody(), false);
    }

    /**
     * Update a payment/subscription plan
     * 
     * @param int $id
     * @param string $name
     * @param string $interval
     * 
     * @return false|\stdClass
     */
    protected static function updatePaymentPlan($id, $name, $interval)
    {
        $client = new Client([ 'base_uri' => env('FLWV_BASE_URL')]);

        $resp = $client->request('PUT', "payment-plans/$id", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('FLWV_SECRET_KEY')
            ],
            'body' => json_encode([
                'name' => $name,
                'interval' => $interval
            ])
        ]);

        if ($resp->getStatusCode()  !== 200) {
            return false;
        }

        return json_decode($resp->getBody(), false);
    }

    /**
     * Cancel a payment plan
     * 
     * @param int $id
     * 
     * @return false|\stdClass
     */
    protected static function cancelPaymentPlan($id)
    {
        $client = new Client([ 'base_uri' => env('FLWV_BASE_URL')]);

        $resp = $client->request('PUT', "payment-plans/$id/cancel", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('FLWV_SECRET_KEY')
            ]
        ]);

        if ($resp->getStatusCode()  !== 200) {
            return false;
        }

        return json_decode($resp->getBody(), false);
    }

    /**
     * Cancel a subscription to a payment plan
     * 
     * @param int $id
     * 
     * @return false|\stdClass
     */
    protected static function cancelSubscriptionPlan($id)
    {
        $client = new Client([ 'base_uri' => env('FLWV_BASE_URL')]);

        $resp = $client->request('PUT', "subscriptions/$id/cancel", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('FLWV_SECRET_KEY')
            ]
        ]);

        if ($resp->getStatusCode()  !== 200) {
            return false;
        }

        return json_decode($resp->getBody(), false);
    }


    /**
     * Verify a Transaction
     * 
     * @param int $id
     * 
     * @return false|\stdClass
     */
    protected static function verifyTransaction($id)
    {
        $client = new Client([ 'base_uri' => env('FLWV_BASE_URL')]);

        $resp = $client->request('GET', "transactions/$id/verify", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('FLWV_SECRET_KEY')
            ]
        ]);

        if ($resp->getStatusCode()  !== 200) {
            return false;
        }

        return json_decode($resp->getBody(), false);
    }

    /**
     * Verify a Transaction by reference
     * 
     * @param string $tx_ref
     * 
     * @return false|\stdClass
     */
    protected static function verifyTransactionByRef($tx_ref)
    {
        $client = new Client([ 'base_uri' => env('FLWV_BASE_URL')]);

        $resp = $client->request('GET', "transactions/verify_by_reference/$tx_ref", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('FLWV_SECRET_KEY')
            ]
        ]);

        if ($resp->getStatusCode()  !== 200) {
            return false;
        }

        return json_decode($resp->getBody(), false);
    }
}