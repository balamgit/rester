
## About Rester

Package built for laravel on top Guzzle.

## Introduction

In this documentation, we'll demonstrate how to use **RESTER** with the example API, **PaynowPaymentGateway**, focusing on the `PayNowCreateOrder` class. With simple and elegant syntax, you can abstract API calls into manageable, reusable objects.

---

## 1. Making an API Call with Minimal Code

With **RESTER**, you can call an API with as little as one line of code. Here’s how you can fetch an order creation API in the **PaynowPaymentGateway** example:

```php
$response = PayNowCreateOrder::fetch();
```

This single line handles everything — from making the request to processing the response. The `PayNowCreateOrder` class encapsulates all the necessary details for the API call.

---

## 2. Encapsulate API Logic, Focus on Business Logic

Developers should focus on business logic while **RESTER** handles the API interactions. You can encapsulate API call logic within a separate class, keeping your business logic clean and maintainable.

### Artisan Command to Create API Call Class

```bash
php artisan rester:create --group=PaynowPaymentGateway --api-name=PayNowCreateOrder
```

This command generates a new `PayNowCreateOrder` class under the `app/Rester/PaynowPaymentGateway/` directory (skipping the group folder creation if it already exists).

### Example Class: PayNowCreateOrder

```php
namespace App\Rester\PaynowPaymentGateway;

use Itsmg\Rester\Rester;
use Itsmg\Rester\Contracts\HasFinalEndPoint;

class PayNowCreateOrder extends Rester implements HasFinalEndPoint
{
    public function setFinalEndPoint(): string
    {
        return 'https://example-paynow.com/api/v1/create/order';
    }
}
```

---

## 3. Assigning Default Payloads for API Calls

You can assign default payloads for every request. For instance, when creating an order, you might need to pass amount, currency & userid:

```php
class PayNowCreateOrder extends Rester implements HasFinalEndPoint, WithDefaultPayload
{
    public function setFinalEndPoint(): string
    {
        return 'https://example-paynow.com/api/v1/create/order';
    }

    public function defaultPayload(): array
    {
       return [ 
         'user_id' => 'itsmg@1606',
         'amount' => '1994.00,
         'currency' => 'USD',
       ];
    }
}
```

Now, every time you call `PayNowCreateOrder`, it automatically sends the default payload unless overridden.

---

## 4. Assigning Default Headers for API Calls

Headers, like authentication tokens, can be set by default, ensuring they are sent with every request:

```php
class PayNowCreateOrder extends Rester implements HasFinalEndPoint, WithRequestHeaders
{
    public function setFinalEndPoint(): string
    {
        return 'https://example-paynow.com/api/v1/create/order';
    }

    public function defaultRequestHeaders(): array
    {
       return [ 
         'Content-Type' => 'application/json',
         'Authorization' => 'Bearer ' . PayNowAuthToken::fetch()
       ];
    }
}
```

Here, the `Authorization` header is automatically attached using an Auth token API call, making the process seamless for developers.

---

## 5. Dynamic Payload and Header Handling

You can easily customize payloads or headers dynamically during runtime. Here’s an example:

### Dynamically Adding Payloads

```php
$response = (new PayNowCreateOrder())
           ->addPayloads(['secret-key' => 'xxxxxxx'])
           ->send()
           ->get();
```

### Dynamically Adding Headers

```php
$response = (new PayNowCreateOrder())
           ->addHeaders(['Content-Type' => 'application/json'])
           ->send()
           ->get();
```

This flexibility allows you to pass dynamic data and headers depending on your application’s state.

---

## 6. Exploring RESTER Builder Methods

**RESTER** also provides builder methods for further flexibility:

- **`overWriteEndPoint`**: Overwrite the default endpoint if needed.
- **`appendEndPoint`**: Append additional segments to the endpoint.
- **`assignBaseUri`**: Set a base URI for all API calls.
- **`assignApiRoute`**: Define specific API routes.

These methods give you full control over API URLs as your application's complexity grows.

---

## 7. Payload and Header Interception

RESTER offers a powerful feature to intercept payloads or headers before the API call is fired, making it easy to implement validation rules, caching, or formatting. You can also intercept and modify the response content before returning it to your business logic.

### Example: Intercepting Payload

```php
class PayNowCreateOrder extends Rester implements HasFinalEndPoint, PayloadInterceptor
{
    public function setFinalEndPoint(): string
    {
        return 'https://example-paynow.com/api/v1/create/order';
    }

    public function interceptPayload($payload): array
    {
       $this->exampleValidator($payload);

       return [ 
         'encrypted-data' => $this->exampleEncryptionLogic($payload)
       ];
    }
}
```

In this example, you can validate and encrypt the payload before it is sent.

---

## 8. API Access Logging

RESTER includes API access logging. You can enable or disable it using the `$log` property and specify what details should be logged.

```php
class PayNowCreateOrder extends Rester implements HasFinalEndPoint
{
    protected bool $log = true;

    public function setFinalEndPoint(): string
    {
        return 'https://example-paynow.com/api/v1/create/order';
    }
}
```

This feature allows you to track API interactions for debugging or auditing purposes.

---

## Conclusion

With **RESTER**, API integration in Laravel becomes a breeze. Whether you're sending dynamic payloads, managing authentication, or logging API access, RESTER allows you to focus on your business logic by abstracting the complexity of REST API calls into reusable, manageable components.

---

## Security Vulnerabilities

If you discover a security vulnerability within Rester, please send an e-mail to Balamurugan via [balaneutro@gmail.com
](mailto:balaneutro@gmail.com). All security vulnerabilities will be promptly addressed.