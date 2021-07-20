<?php

$index_test = file_get_contents('https://grinnell-omeka-s-werud.ondigitalocean.app/s/MusicalInstruments/musical-instrument-search?q=*&limit[resource_class_s][0]=wmi:MusicalInstrument');

$index = new DOMDocument();
$index->loadHTML($index_text);

$headings = $doc->getElementsByTagName('h3');

$count = explode(" ", $headings->item(0)->getAttribute('value'));

echo $count;

if ($count[0] > 100) {
	exit();
}

$creds = parse_ini_file('/home/libadmin/reindex.ini');
$login_url = "https://grinnell-omeka-s-werud.ondigitalocean.app/login";
$reindex_url = "https://grinnell-omeka-s-werud.ondigitalocean.app/admin/search/index/1/rebuild";
$cookie= "/home/libadmin/cookies.txt";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $login_url);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if (curl_errno($ch)) die(curl_error($ch));

echo $response;

$doc = new DOMDocument();
$doc->loadHTML($response);

$tokens = $doc->getElementsByTagName('input');
for ($i=0;$i<$tokens->length;$i++) {
	$node = $tokens->item($i);
	if($node->getAttribute('name') == 'loginform_csrf') {
		$token = $node->getAttribute('value');
	}
}

echo $token;
$postinfo = "email=" . $creds['user'] . "&password=" . $creds['password'] . "&loginform_csrf=" . $token;
curl_setopt($ch, CURLOPT_URL, $login_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
$html = curl_exec($ch);
if (curl_errno($ch)) print curl_error($ch);

curl_setopt($ch, CURLOPT_URL, $reindex_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

$doc = new DOMDocument();
$doc->loadHTML($response);

$tokens = $doc->getElementsByTagName('input');
for ($i=0;$i<$tokens->length;$i++) {
	$node = $tokens->item($i);
	if($node->getAttribute('name') == 'csrf') {
		$token = $node->getAttribute('value');
	}
}

echo $token;

$reindex_post_info = "csrf=" . $token . "&clear-index=0&batch-size=100";
curl_setopt($ch, CURLOPT_POSTFIELDS, $reindex_post_info);
$reindex = curl_exec($ch);
if (curl_errno($ch)) print curl_error($ch);
echo $reindex;

curl_close($ch);
unlink($cookie);

?>
