<?php

namespace CardCash_API;

class API
{

    protected $_uri;
    protected $_appID;
    protected $_debug;
    protected $_headers;

    const TIMEOUT = 30;

    function __construct($appID, $isProduction = false, $debug = false)
    {
        $this->_appID = $appID;
        $this->_debug = $debug;
        $this->_uri = ($isProduction) ?
          "https://production-api.cardcash.com/v3/" :
          "https://sandbox-api.cardcash.com/v3/";

        $this->_headers = array(
            'accept: application/json',
            'content-type: application/json',
            'x-cc-app:' . $appID
        );
    }

    protected function getHeaders()
    {
        return $this->_headers;
    }

    protected function getMyCookie()
    {
        return $this->_myCookie;
    }

    protected function setMyCookie($cookie)
    {
        return $this->_myCookie = $cookie;
    }

    protected function parseMyCookie($response)
    {
        $cookies = array();

        preg_match_all('/Set-Cookie:(?<cookie>\s{0,}.*)$/im', $response, $cookies);

        foreach($cookies['cookie'] as $cookie)
        {
            if (strpos($cookie, $this->_appID) !== false)
            {
                $this->setMyCookie($cookie);
                break;
            }
        }
    }

    protected function parseBody($ch, $response)
    {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body       = substr($response, $headerSize);

        if (strpos($body, '{') === 0)
        {
            return json_decode($body);
        }

        if (!empty($body)) {
          return $body;
        }

        return "OK";
    }

    protected function execute($method, $path, $jsonObject = null)
    {

         if ($path !== "session" && empty( $this->getMyCookie() ) )
         {
             $this->execute('POST', 'session');
         }

         try {

            if ($this->_debug)
            {
                echo "Calling ".$method." ".$this->_uri.$path;
                echo "\n\n";
                echo "with APPID Cookie: ".$this->getMyCookie();
                echo "\n\n";
                echo "with headers: ";
                print_r($this->getHeaders());
                echo "\n\n";
                echo "with data: ".$jsonObject;
                echo "\n\n";
            }

            $ch = curl_init();

            if ($ch === false) {
                throw new Exception('failed to initialize');
            }


            curl_setopt($ch , CURLOPT_URL , $this->_uri.$path);
            curl_setopt($ch , CURLOPT_HTTPHEADER , $this->getHeaders());
            curl_setopt($ch , CURLOPT_TIMEOUT , $this->_timeout);
            curl_setopt($ch , CURLOPT_HEADER , 1);
            curl_setopt($ch , CURLOPT_RETURNTRANSFER , 1);
            curl_setopt($ch , CURLOPT_FOLLOWLOCATION , 1);
            curl_setopt($ch , CURLOPT_COOKIE , $this->getMyCookie());
            curl_setopt($ch , CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1);

            if (jsonObject != null && ('POST' == $method || 'PUT' == $method))
            {
                curl_setopt($ch , CURLOPT_CUSTOMREQUEST , $method);
                curl_setopt($ch , CURLOPT_POSTFIELDS , $jsonObject);
            } else {
                curl_setopt($ch , CURLOPT_CUSTOMREQUEST , $method);
            }

            $response = curl_exec($ch);

            if ($response === false) {
                throw new Exception(curl_error($ch), curl_errno($ch));
            }


            if ($this->_debug)
            {
                echo $response;
                echo "\n\n";
            }

            $this->parseMyCookie($response);
            $body = $this->parseBody($ch, $response);


            curl_close($ch);

            return $body;
        } catch(Exception $e) {
             trigger_error(sprintf(
                 'Curl failed with error #%d: %s',
                 $e->getCode(), $e->getMessage()),
                 E_USER_ERROR);
         }
    }

    public function CustomerLogin($email, $password)
    {
        $customer = array();
        $customer["email"] = $email;
        $customer["password"] = $password;
        $loginData = array("customer" => $customer);

        $loginResponse = $this->execute('POST',  "customers/login", json_encode($loginData));

        return $loginResponse;
    }

    public function GetCustomer()
    {
        $getCustomerResponse = $this->execute('GET', "customers");

        return $getCustomerResponse;
    }

    public function GetDefaultPaymentOptions()
    {
        $getCustomerResponse = $this->execute('GET', "customers/payment-options");

        return $getCustomerResponse;
    }

    public function GetMerchants()
    {
        $merchantResponse = $this->execute('GET', "merchants/sell");

        return $merchantResponse;
    }

    public function RetrieveCart()
    {
        $getCartResponse = $this->execute('GET', "carts");

        return $getCartResponse;
    }

    public function CreateCart()
    {
        $createCartObj = array("action" => "sell");

        $createCartResponse = $this->execute('POST', "carts", json_encode($createCartObj));

        return $createCartResponse;
    }

    public function DeleteCart($cartID)
    {
        $deleleteCartResponse = $this->execute('DELETE', "carts/" . $cartID);

        return $deleleteCartResponse;
    }

    public function AddCardToCart($cartID, $merchantID, $cardValue, $cardNum = null, $cardPin = null, $refID = null)
    {
        $addCard = array();
        $addCard["merchantId"] = $merchantID;
        $addCard["enterValue"] = $cardValue;

        if (!empty($cardNum))
        {
            $addCard["number"] = $cardNum;
        }

        if (!empty($cardPin))
        {
            $addCard["pin"] = $cardPin;
        }

        if (!empty($refID))
        {
          $addCard["refId"] = $refID;
        }

        $card = array("card" => $addCard);
        $addCardResponse = $this->execute('POST', "carts/" . $cartID . "/cards", json_encode($card));

        return $addCardResponse;
    }

    public function UpdateCardInCart($cartID, $cardID, $cardValue = null, $cardNum = null, $cardPin = null, $refID = null)
    {
        $updateCard = array();

        if (!empty($cardValue))
        {
          $updateCard["enterValue"] = $cardValue;
        }

        if (!empty($cardNum))
        {
            $updateCard["number"] = $cardNum;
        }

        if (!empty($cardPin))
        {
            $updateCard["pin"] = $cardPin;
        }

        if (!empty($refID))
        {
          $updateCard["refId"] = $refID;
        }

        $card = array("card" => $updateCard);
        $updateCardResponse = $this->execute('PUT', "carts/" . $cartID . "/cards/" + $cardID, json_encode($card));

        return $updateCardResponse;
    }

    public function DeleteCardInCart($cartID, $cardID)
    {
        $deleteCardResponse = $this->execute('DELETE', "carts/" . $cartID . "/cards/" . $cardID);

        return $deleteCardResponse;
    }

    public function PlaceOrder($cartID, $paymentDetailID, $firstName, $lastName, $street, $city, $state, $postcode, $street2 = null)
    {
        $paymentDetails = array(
          "id" => $paymentDetailID
        );

        $billingDetails = array(
          "firstname" => $firstName,
          "lastname"  => $lastName,
          "street"    => $street,
          "city"      => $city,
          "state"     => $state,
          "postcode"  => $postcode
        );

        if (!empty($street2))
        {
            $billingDetails["street2"] = $street2;
        }

        $order = array(
          "autoSplit" => true,
          "cartId" => $cartID,
          "paymentMethod" => "ACH_BULK",
          "billingDetails" => $billingDetails,
          "paymentDetails" => $paymentDetails
        );

        $orderReponse = $this->execute('POST', "orders", json_encode($order));

        return $orderReponse;
    }


    public function GetOrder($orderID)
    {
        $getOrderResponse = $this->execute('GET', "orders/". $orderID);

        return $getOrderResponse;
    }

    public function GetAllOrders()
    {
        $getOrdersResponse = $this->execute('GET', "orders/sell");

        return $getOrdersResponse;
    }

    public function GetOrderCards($orderID)
    {
        $getOrdersCardsResponse = $this->execute('GET', "cards/sell?orderId=". $orderID);

        return $getOrdersCardsResponse;
    }

    public function GetAllCards()
    {
        $getAllCardsResponse = $this->execute('GET', "cards/sell");

        return $getAllCardsResponse;
    }

    public function GetAllPayments()
    {
        $getAllPaymentsResponse = $this->execute('GET', "payments/sell");

        return $getAllPaymentsResponse;
    }

    public function GetPayment($paymentID)
    {
        $getPaymentResponse = $this->execute('GET', "payments/sell/" . $paymentID);

        return $getPaymentResponse;
    }

}

?>
