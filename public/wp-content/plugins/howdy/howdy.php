<?php
	phpinfo();

echo "\nREQUEST:\n";
foreach (apache_request_headers() as $header => $value) {
    echo "$header: $value\n";
}
echo "\nRESPONSE:\n";
foreach (apache_response_headers() as $header => $value) {
    echo "$header: $value \n";
}
echo "\nENV:\n";
print_r( $_ENV );