<?php

function slim_msg($status, $message)
{
  $data["status"] = $status;
  $data["message"] = $message;
  return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
