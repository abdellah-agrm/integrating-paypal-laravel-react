---

## **Integrating PayPal with Laravel and React.js: A Step-by-Step Guide**

### **English Version**

This tutorial will guide you through integrating PayPal with a Laravel API backend and React.js frontend. We will use PayPal's `srmklive/paypal` package for the backend and `@paypal/react-paypal-js` for the frontend.

---

### **Step 1: Setting up Laravel and React.js**

1. **Create a Laravel project**:
   ```bash
   composer create-project --prefer-dist laravel/laravel laravel-react-paypal
   ```

2. **Set up React.js**:
   Inside your Laravel project, create a new React app using `create-react-app`:
   ```bash
   npx create-react-app frontend
   ```

3. **Install Tailwind CSS** in the React app:
   Follow the [Tailwind CSS installation guide](https://tailwindcss.com/docs/guides/create-react-app).

4. **Install API in Laravel**:
   Run the following command to install the API functionality:
   ```bash
   php artisan install:api
   ```

---

### **Step 2: Create Payments Migration and Model**

1. **Create a migration for payments**:
   ```bash
   php artisan make:migration create_payments_table
   ```

2. **Define the schema for the migration**:
   Add fields like `fullname`, `email`, `amount`, and `status` in the migration file.

   Example:
   ```php
   Schema::create('payments', function (Blueprint $table) {
       $table->id();
       $table->string('fullname');
       $table->string('email');
       $table->decimal('amount', 10, 2);
       $table->string('status');
       $table->timestamps();
   });
   ```

3. **Create the Payment model**:
   ```bash
   php artisan make:model Payment
   ```

4. **Create the PaymentController**:
   ```bash
   php artisan make:controller PaymentController
   ```

---

### **Step 3: Install and Configure PayPal**

1. **Install the PayPal package**:
   ```bash
   composer require srmklive/paypal
   ```

2. **Publish PayPal configuration**:
   ```bash
   php artisan vendor:publish --provider "Srmklive\PayPal\Providers\PayPalServiceProvider"
   ```

3. **Configure PayPal in `.env`**:
   Add the following lines to your `.env` file:
   ```env
   PAYPAL_MODE=sandbox
   PAYPAL_SANDBOX_CLIENT_ID=your-client-id
   PAYPAL_SANDBOX_CLIENT_SECRET=your-client-secret
   ```

   Visit [PayPal Developer Dashboard](https://developer.paypal.com/dashboard/), navigate to "Standard Checkout," and obtain your sandbox credentials.

---

### **Step 4: Update PaymentController**

Add the following methods in `PaymentController`:

#### `createPayment` function:
```php
public function createPayment(Request $request)
{
    try {
        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $provider = \Srmklive\PayPal\Facades\PayPal::setProvider();
        $provider->setApiCredentials(config('paypal'));
        $token = $provider->getAccessToken();   

        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->amount,
                    ],
                ],
            ],
        ]);

        return response()->json(['order' => $response], 200);
    } catch (\Exception $e) {
        Log::error('Error creating payment: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

#### `executePayment` function:
```php
public function executePayment(Request $request)
{
    try {
        $request->validate([
            'orderID' => 'required|string',
        ]);

        $provider = \Srmklive\PayPal\Facades\PayPal::setProvider();
        $provider->setApiCredentials(config('paypal'));
        $token = $provider->getAccessToken();
        $provider->setAccessToken($token);

        $response = $provider->capturePaymentOrder($request->orderID);
        
        if (is_array($response) && isset($response['status']) && $response['status'] === 'COMPLETED') {
            $payment = Payment::create([
                'fullname' => $response['payer']['name']['given_name'].' '.$response['payer']['name']['surname'],
                'paymentstatus' => $response['status'],
                'amount' => $response['purchase_units'][0]['payments']['captures'][0]['amount']['value']
            ]);
            return response()->json(['message' => 'Payment successful', 'payment' => $payment], 200);
        }

        return response()->json([ 'error' => 'Payment not completed',  'full_response' => $response ], 400);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

---

### **Step 5: Set Up Routes**

In `routes/api.php`, add the following routes:
```php
Route::post('/paypal/create', [PaymentController::class, 'createPayment']);
Route::post('/paypal/execute', [PaymentController::class, 'executePayment']);
```

---

### **Step 6: Frontend Setup**

1. **Install PayPal and Axios**:
   Run the following command in your React app:
   ```bash
   npm install @paypal/react-paypal-js axios
   ```

2. **Create `.env` file**:
   Add the following to your React `.env` file:
   ```env
   REACT_APP_LOCALHOST=http://localhost:8000/api/
   REACT_APP_CLIENT_ID=your-client-id
   ```

3. **Create PayPal button component**:
   ```jsx
   import axios from 'axios';
   import { PayPalButtons, PayPalScriptProvider } from '@paypal/react-paypal-js';

   export default function PayButton({ amount }) {
     const localhost = process.env.REACT_APP_LOCALHOST;
     const client_id = process.env.REACT_APP_CLIENT_ID;

     const handleCreateOrder = async () => {
       try {
         const response = await axios.post(`${localhost}paypal/create`, { amount });
         return response.data.order?.id;
       } catch (err) {
         console.error(err);
       }
     };

     const handleApprove = async (data) => {
       try {
         const response = await axios.post(`${localhost}paypal/execute`, { orderID: data.orderID });
         console.log(response.data);
       } catch (err) {
         console.error(err);
       }
     };

     return (
       <PayPalScriptProvider options={{ "client-id": client_id }}>
         <div className="mt-2">
           <PayPalButtons
             disabled={false}
             createOrder={handleCreateOrder}
             onApprove={handleApprove}
             style={{ layout: 'horizontal', color: 'blue', shape: 'rect', label: 'paypal', tagline: false }}
           />
         </div>
       </PayPalScriptProvider>
     );
   }
   ```

---

### **Congratulations!**  
You have successfully integrated PayPal with your Laravel and React.js app.

---
