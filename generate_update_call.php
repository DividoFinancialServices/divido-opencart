<?php

$order_id = $argv[1];
$status = $argv[2];

$db = new mysqli('opencart21.divido.dev', 'root', '', 'opencart_21');
if ($db->connect_errno) {
    die($db->connect_error);
}

$s = $db->prepare("select salt from tst_divido_lookup where order_id = ?");
$s->bind_param('i', $order_id);
$s->execute();
$s->bind_result($salt);
$s->fetch();
$s->close();
$db->close();

$url = 'http://opencart21.divido.dev/index.php?route=payment/divido/update';
$statuses = [
   0 => 'ACCEPTED',
   1 => 'DEPOSIT-PAID',
   2 => 'SIGNED',
   3 => 'FULFILLED',
   4 => 'COMPLETED',
   5 => 'DEFERRED',
   6 => 'REFERRED',
   7 => 'DECLINED',
   8 => 'CANCELED',
];

if (! $status) {
    $status = $statuses[0];
}

$hash = hash('sha256', $order_id.$salt);

$req_tpl = [
    "application" => 'C84047A6D-89B2-FECF-D2B4-168444F5178C',
    "reference" => 100024,
    "status" => "{$status}",
    "live" => false,
    "metadata" =>  [
       "order_id" => $order_id,
       "order_hash" => $hash,
    ],
];

$data = json_encode($req_tpl);
$cmd = "curl -v -X POST -d '{$data}' -H 'Content-Type: application/json' {$url}";

system($cmd);

echo "\nhttp://opencart21.divdo.dev/index.php?route=checkout/success";

