<?php

require_once("../common/userdata.php");

header("Content-Type: application/json");

$u = new UserData();
$u->set_id("abc");
$u->set_name("john");
echo(json_encode($u));