<?php
declare(strict_types=1);

namespace App\Services;

class PaymentService
{
    private string $payment_provider;
    private array $config;
    private array $supported_methods;

    public function __construct()
    {
        $this->payment_provider = $_ENV['PAYMENT_PROVIDER'] ?? 'mollie';
        $this->config = [
            'mollie_api_key' => $_ENV['MOLLIE_API_KEY'] ?? '',
            'stripe_secret_key' => $_ENV['STRIPE_SECRET_KEY'] ?? '',
            'stripe_publishable_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '',
            'paypal_client_id' => $_ENV['PAYPAL_CLIENT_ID'] ?? '',
            'paypal_client_secret' => $_ENV['PAYPAL_CLIENT_SECRET'] ?? '',
        ];
        
        $this->supported_methods = [
            'ideal' => 'iDEAL',
            'creditcard' => 'Credit Card',
            'bancontact' => 'Bancontact',
            'sofort' => 'SOFORT Banking',
            'paypal' => 'PayPal',
            'banktransfer' => 'Bank Transfer',
        ];
    }

    /**
     * Create payment for order
     * 
     * @param array<string, mixed> $order
     * @param string $payment_method
     * @param string $return_url
     * @return array<string, mixed>
     */
    public function create_payment(array $order, string $payment_method, string $return_url): array
    {
        try {
            switch ($this->payment_provider) {
                case 'mollie':
                    return $this->create_mollie_payment($order, $payment_method, $return_url);
                case 'stripe':
                    return $this->create_stripe_payment($order, $payment_method, $return_url);
                case 'paypal':
                    return $this->create_paypal_payment($order, $payment_method, $return_url);
                default:
                    return $this->create_manual_payment($order);
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Payment creation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create Mollie payment
     * 
     * @param array<string, mixed> $order
     */
    private function create_mollie_payment(array $order, string $payment_method, string $return_url): array
    {
        $api_key = $this->config['mollie_api_key'];
        
        if (empty($api_key)) {
            throw new \Exception('Mollie API key not configured');
        }

        $payment_data = [
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($order['total_amount'], 2, '.', ''),
            ],
            'description' => "Bestelling #{$order['id']}",
            'redirectUrl' => $return_url,
            'webhookUrl' => $_ENV['APP_URL'] . '/api/webhook/mollie',
            'metadata' => [
                'order_id' => $order['id'],
            ],
        ];

        if ($payment_method !== 'creditcard') {
            $payment_data['method'] = $payment_method;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.mollie.com/v2/payments',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payment_data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 201) {
            $error_data = json_decode($response, true);
            throw new \Exception('Mollie API error: ' . ($error_data['detail'] ?? 'Unknown error'));
        }

        $payment_data = json_decode($response, true);

        return [
            'success' => true,
            'payment_id' => $payment_data['id'],
            'checkout_url' => $payment_data['_links']['checkout']['href'],
            'status' => $payment_data['status'],
        ];
    }

    /**
     * Create Stripe payment
     * 
     * @param array<string, mixed> $order
     */
    private function create_stripe_payment(array $order, string $payment_method, string $return_url): array
    {
        $secret_key = $this->config['stripe_secret_key'];
        
        if (empty($secret_key)) {
            throw new \Exception('Stripe secret key not configured');
        }

        // Create Stripe Checkout Session
        $session_data = [
            'payment_method_types' => [$payment_method === 'creditcard' ? 'card' : $payment_method],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => "Bestelling #{$order['id']}",
                        ],
                        'unit_amount' => (int) ($order['total_amount'] * 100), // Amount in cents
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $return_url . '?success=true',
            'cancel_url' => $return_url . '?success=false',
            'metadata' => [
                'order_id' => $order['id'],
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.stripe.com/v1/checkout/sessions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($session_data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secret_key,
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            throw new \Exception('Stripe API error: ' . ($error_data['error']['message'] ?? 'Unknown error'));
        }

        $session_data = json_decode($response, true);

        return [
            'success' => true,
            'payment_id' => $session_data['id'],
            'checkout_url' => $session_data['url'],
            'status' => 'pending',
        ];
    }

    /**
     * Create PayPal payment
     * 
     * @param array<string, mixed> $order
     */
    private function create_paypal_payment(array $order, string $payment_method, string $return_url): array
    {
        $client_id = $this->config['paypal_client_id'];
        $client_secret = $this->config['paypal_client_secret'];
        
        if (empty($client_id) || empty($client_secret)) {
            throw new \Exception('PayPal credentials not configured');
        }

        // Get PayPal access token
        $access_token = $this->get_paypal_access_token($client_id, $client_secret);

        // Create PayPal order
        $order_data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => number_format($order['total_amount'], 2, '.', ''),
                    ],
                    'description' => "Bestelling #{$order['id']}",
                ],
            ],
            'application_context' => [
                'return_url' => $return_url . '?success=true',
                'cancel_url' => $return_url . '?success=false',
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.paypal.com/v2/checkout/orders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($order_data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 201) {
            $error_data = json_decode($response, true);
            throw new \Exception('PayPal API error: ' . ($error_data['message'] ?? 'Unknown error'));
        }

        $paypal_order = json_decode($response, true);
        $approve_link = '';
        
        foreach ($paypal_order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approve_link = $link['href'];
                break;
            }
        }

        return [
            'success' => true,
            'payment_id' => $paypal_order['id'],
            'checkout_url' => $approve_link,
            'status' => 'pending',
        ];
    }

    private function get_paypal_access_token(string $client_id, string $client_secret): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.paypal.com/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $client_id . ':' . $client_secret,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \Exception('PayPal authentication failed');
        }

        $data = json_decode($response, true);
        return $data['access_token'];
    }

    /**
     * Create manual payment (bank transfer, etc.)
     * 
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    private function create_manual_payment(array $order): array
    {
        return [
            'success' => true,
            'payment_id' => 'manual_' . $order['id'] . '_' . time(),
            'checkout_url' => null,
            'status' => 'pending_manual',
            'instructions' => $this->get_manual_payment_instructions($order),
        ];
    }

    /**
     * Get manual payment instructions
     * 
     * @param array<string, mixed> $order
     */
    private function get_manual_payment_instructions(array $order): string
    {
        $bank_account = $_ENV['BANK_ACCOUNT'] ?? 'NL00 BANK 0000 0000 00';
        $bank_name = $_ENV['BANK_NAME'] ?? 'Webshop Bank';
        
        return "Gelieve het bedrag van €" . number_format($order['total_amount'], 2) . " over te maken naar:\n\n" .
               "Rekeningnummer: {$bank_account}\n" .
               "Bank: {$bank_name}\n" .
               "Betalingskenmerk: Bestelling #{$order['id']}\n\n" .
               "Je bestelling wordt verwerkt zodra de betaling is ontvangen.";
    }

    /**
     * Verify payment status
     */
    public function verify_payment(string $payment_id): array
    {
        try {
            switch ($this->payment_provider) {
                case 'mollie':
                    return $this->verify_mollie_payment($payment_id);
                case 'stripe':
                    return $this->verify_stripe_payment($payment_id);
                case 'paypal':
                    return $this->verify_paypal_payment($payment_id);
                default:
                    return ['status' => 'pending_manual'];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function verify_mollie_payment(string $payment_id): array
    {
        $api_key = $this->config['mollie_api_key'];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.mollie.com/v2/payments/{$payment_id}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $api_key,
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \Exception('Failed to verify Mollie payment');
        }

        $payment_data = json_decode($response, true);
        
        return [
            'status' => $payment_data['status'],
            'amount' => $payment_data['amount']['value'],
            'order_id' => $payment_data['metadata']['order_id'] ?? null,
        ];
    }

    private function verify_stripe_payment(string $session_id): array
    {
        $secret_key = $this->config['stripe_secret_key'];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.stripe.com/v1/checkout/sessions/{$session_id}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secret_key,
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \Exception('Failed to verify Stripe payment');
        }

        $session_data = json_decode($response, true);
        
        $status = $session_data['payment_status'] === 'paid' ? 'paid' : 'pending';
        
        return [
            'status' => $status,
            'amount' => $session_data['amount_total'] / 100, // Convert from cents
            'order_id' => $session_data['metadata']['order_id'] ?? null,
        ];
    }

    private function verify_paypal_payment(string $order_id): array
    {
        $client_id = $this->config['paypal_client_id'];
        $client_secret = $this->config['paypal_client_secret'];
        
        $access_token = $this->get_paypal_access_token($client_id, $client_secret);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.paypal.com/v2/checkout/orders/{$order_id}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $access_token,
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \Exception('Failed to verify PayPal payment');
        }

        $order_data = json_decode($response, true);
        
        $status = $order_data['status'] === 'COMPLETED' ? 'paid' : 'pending';
        $amount = $order_data['purchase_units'][0]['amount']['value'] ?? 0;
        
        return [
            'status' => $status,
            'amount' => $amount,
            'order_id' => null, // PayPal doesn't store custom order ID in this endpoint
        ];
    }

    /**
     * Process webhook from payment provider
     * 
     * @return array<string, mixed>
     */
    public function process_webhook(): array
    {
        try {
            switch ($this->payment_provider) {
                case 'mollie':
                    return $this->process_mollie_webhook();
                case 'stripe':
                    return $this->process_stripe_webhook();
                case 'paypal':
                    return $this->process_paypal_webhook();
                default:
                    return ['success' => false, 'error' => 'Unsupported payment provider'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function process_mollie_webhook(): array
    {
        $payment_id = $_POST['id'] ?? '';
        
        if (empty($payment_id)) {
            throw new \Exception('Missing payment ID in webhook');
        }
        
        $payment_data = $this->verify_mollie_payment($payment_id);
        
        return [
            'success' => true,
            'payment_id' => $payment_id,
            'status' => $payment_data['status'],
            'order_id' => $payment_data['order_id'],
            'amount' => $payment_data['amount'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function process_stripe_webhook(): array
    {
        $payload = file_get_contents('php://input');
        $webhook_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';
        
        if (!empty($webhook_secret)) {
            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
            // Verify webhook signature here if needed
        }
        
        $event = json_decode($payload, true);
        
        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];
            
            return [
                'success' => true,
                'payment_id' => $session['id'],
                'status' => 'paid',
                'order_id' => $session['metadata']['order_id'] ?? null,
                'amount' => $session['amount_total'] / 100,
            ];
        }
        
        return ['success' => false, 'error' => 'Unhandled event type'];
    }

    /**
     * @return array<string, mixed>
     */
    private function process_paypal_webhook(): array
    {
        $payload = file_get_contents('php://input');
        $event = json_decode($payload, true);
        
        if ($event['event_type'] === 'CHECKOUT.ORDER.COMPLETED') {
            $order = $event['resource'];
            
            return [
                'success' => true,
                'payment_id' => $order['id'],
                'status' => 'paid',
                'order_id' => null, // Extract from order if stored
                'amount' => $order['purchase_units'][0]['amount']['value'],
            ];
        }
        
        return ['success' => false, 'error' => 'Unhandled event type'];
    }

    /**
     * Get supported payment methods
     * 
     * @return array<string, string>
     */
    public function get_supported_methods(): array
    {
        return $this->supported_methods;
    }

    /**
     * Format amount for display
     */
    public function format_amount(float $amount, string $currency = 'EUR'): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        
        return $symbol . number_format($amount, 2, ',', '.');
    }
}
