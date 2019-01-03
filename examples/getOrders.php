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

    $GetAllOrders = $CC_API->GetAllOrders();
    echo "GetAllOrders resp";
    print_r($GetAllOrders);
    echo "\n\n";

    $GetAllCards = $CC_API->GetAllCards();
    echo "GetAllCards resp";
    print_r( $GetAllCards);
    echo "\n\n";

} catch (\Exception $e) {
  echo 'Caught exception: ',  $e->getMessage(), "\n";
}

?>
