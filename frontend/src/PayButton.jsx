import axios from 'axios';
import { PayPalButtons, PayPalScriptProvider } from '@paypal/react-paypal-js';

export default function PayButton({ amount }) {
  const localhost = process.env.REACT_APP_LOCALHOST;
  const client_id = process.env.REACT_APP_CLIENT_ID;

  const handleCreateOrder = async () => {
    try {
      const response = await axios.post(`${localhost}paypal/create`, { amount: amount });
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
        <PayPalButtons disabled={false} createOrder={handleCreateOrder} onApprove={handleApprove}
          style={{ layout: 'horizontal', color: 'blue', shape: 'rect', label: 'paypal', tagline: false }} />
      </div>
    </PayPalScriptProvider>
  )
}
