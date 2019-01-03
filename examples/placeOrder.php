<?php

namespace examples;

require __DIR__.'/../vendor/autoload.php';

use CardCash_API\API;

try {
    $appID = "";
    $emailAddr = "";
    $pwd = "";

    $CC_API = new API($appID,false,false);

    $login = $CC_API->CustomerLogin($emailAddr, $pwd);
    echo "CustomerLogin resp";
    print_r($login);
    echo "\n\n";

    $CreateCart = $CC_API->CreateCart();
    echo "CreateCart resp";
    print_r($CreateCart);
    echo "\n\n";

    $AddCardToCart = $CC_API->AddCardToCart($CreateCart->cartId, 99, 50.00, "5555555122235584", "1234");
    echo "AddCardToCart resp";
    print_r( $AddCardToCart);
    echo "\n\n";

    $PlaceOrder = $CC_API->PlaceOrder($CreateCart->cartId, 1, "Card", "Cash", "990 Cedar Bridge Avenue", "Brick", "NJ", "08540");
    echo "PlaceOrder resp";
    print_r($PlaceOrder);
    echo "\n\n";


} catch (\Exception $e) {
  echo 'Caught exception: ',  $e->getMessage(), "\n";
}

?>
