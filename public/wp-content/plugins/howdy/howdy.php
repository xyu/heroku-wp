<?php
	phpinfo();

echo "REQUEST:\n";
foreach (apache_request_headers() as $header => $value) {
    echo "$header: $value\n";
}
echo "\nRESPONSE:\n";
foreach (apache_response_headers() as $header => $value) {
    echo "$header: $value \n";
}
