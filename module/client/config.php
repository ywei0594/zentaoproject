<?php
$config->client->require = new stdclass();
$config->client->require->creat = 'version, strategy, downloads, status';
$config->client->require->edit  = 'version, strategy, downloads, status';
$config->client->expirationDays = 3;

$config->client->upgradeApi = 'https://www.xuanim.com/xxbversion-api%s.json';
