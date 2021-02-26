<?php

use JRDev\Imagery\Exceptions\FileNotFoundException;
use JRDev\Imagery\Exceptions\InputException;
use JRDev\Imagery\Imagery;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';

try {
  $imgPath = str_replace('/imagery/run/', '', filter_input(INPUT_SERVER, 'REQUEST_URI'));
  $imagery = new Imagery(
    $imgPath,
    realpath(__DIR__ . '/../..'),
    realpath(__DIR__)
  );

  $imagery->compress(20);
  $imagery->response();

} catch (InputException $th) {
  $responseCode = 400;
  $responseMsg = $th->getMessage();
} catch (FileNotFoundException $th) {
  $responseCode = 404;
  $responseMsg = 'File Not Found';
} catch (Throwable $th) {
  $responseCode = 500;
  $responseMsg = 'Internal Error';
}

http_response_code($responseCode);
printf('Error %u - %s', $responseCode, $responseMsg);
