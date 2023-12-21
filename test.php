<?php

$secretData= json_decode(exec("aws secretsmanager get-secret-value --secret-id env-ops --query SecretString --output json"));

var_dump($secretData);
