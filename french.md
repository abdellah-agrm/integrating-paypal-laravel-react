---

## **Intégrer PayPal avec Laravel et React.js : Un guide pas à pas**

### **Version Française**

Ce tutoriel vous guidera à travers l'intégration de PayPal avec un backend Laravel et un frontend React.js. Nous utiliserons le package PayPal `srmklive/paypal` pour le backend et `@paypal/react-paypal-js` pour le frontend.

---

### **Étape 1 : Création de Laravel et React.js**

1. **Créer un projet Laravel** :
   ```bash
   composer create-project --prefer-dist laravel/laravel laravel-react-paypal
   ```

2. **Configurer React.js** :
   Dans votre projet Laravel, créez une nouvelle application React avec `create-react-app` :
   ```bash
   npx create-react-app frontend
   ```

3. **Installer Tailwind CSS** dans l'application React :
   Suivez le [guide d'installation de Tailwind CSS](https://tailwindcss.com/docs/guides/create-react-app).

4. **Installer l'API dans Laravel** :
   Exécutez la commande suivante pour installer l'API :
   ```bash
   php artisan install:api
   ```

---

### **Étape 2 : Création de la Migration et du Modèle de Paiement**

1. **Créer une migration pour les paiements** :
   ```bash
   php artisan make:migration create_payments_table
   ```

2. **Définir le schéma de la migration** :
   Ajoutez des champs comme `fullname`, `email`, `amount`, et `status` dans le fichier de migration.

   Exemple :
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

3. **Créer le modèle Payment** :
   ```bash
   php artisan make:model Payment
   ```

4. **Créer le PaymentController** :
  

 ```bash
   php artisan make:controller PaymentController
   ```

---

### **Étape 3 : Installer et Configurer PayPal**

1. **Installer le package PayPal** :
   ```bash
   composer require srmklive/paypal
   ```

2. **Publier la configuration PayPal** :
   ```bash
   php artisan vendor:publish --provider "Srmklive\PayPal\Providers\PayPalServiceProvider"
   ```

3. **Configurer PayPal dans `.env`** :
   Ajoutez les lignes suivantes à votre fichier `.env` :
   ```env
   PAYPAL_MODE=sandbox
   PAYPAL_SANDBOX_CLIENT_ID=your-client-id
   PAYPAL_SANDBOX_CLIENT_SECRET=your-client-secret
   ```

   Visitez le [Tableau de bord des développeurs PayPal](https://developer.paypal.com/dashboard/), accédez à "Standard Checkout" et obtenez vos identifiants sandbox.

---

### **Étape 4 : Mettre à Jour PaymentController**

Ajoutez les méthodes suivantes dans `PaymentController` :

#### Fonction `createPayment` :
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

#### Fonction `executePayment` :
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

### **Étape 5 : Configurer les Routes**

Dans `routes/api.php`, ajoutez les routes suivantes :
```php
Route::post('/paypal/create', [PaymentController::class, 'createPayment']);
Route::post('/paypal/execute', [PaymentController::class, 'executePayment']);
```

---

### **Étape 6 : Configuration du Frontend**

1. **Installer PayPal et Axios** :
   Exécutez la commande suivante dans votre application React :
   ```bash
   npm install @paypal/react-paypal-js axios
   ```

2. **Créer le fichier `.env`** :
   Ajoutez ceci à votre fichier `.env` React :
   ```env
   REACT_APP_LOCALHOST=http://localhost:8000/api/
   REACT_APP_CLIENT_ID=your-client-id
   ```

3. **Créer le composant du bouton PayPal** :
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

### **Félicitations !**
Vous avez réussi à intégrer PayPal avec votre application Laravel et React.js.

---