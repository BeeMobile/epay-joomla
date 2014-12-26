<?php
require_once("kkb.utils.php");
/*$self = $_SERVER['PHP_SELF'];
$path1 = dirname(realpath("../".$self)).'/config.txt';
if(is_file($path1)){
	$config=parse_ini_file($path1,0);
} else { return "Config not exist";};

$sign = 'xjJwgeLAyWssZr3/gS7TI/xaajoF3USk0B/ZfLv6SYyY/3H8tDHUiyGcV7zDO5+rINwBoTn7b9BrnO/kvQfebIhHbDlCSogz2cB6Qa2ELKAGqs8aDZDekSJ5dJrgmFT6aTfgFgnZRmadybxTMHGR6cn8ve4m0TpQuaPMQmKpxTI=';
$xml = '<bank name="Kazkommertsbank JSC"><customer name="Ucaf Test Maest" mail="SeFrolov@kkb.kz" phone=""><merchant cert_id="00C182B189" name="test merch"><order order_id="0706172110" amount="1000" currency="398"><department merchant_id="92056001" amount="1000"/></order></merchant><merchant_sign type="RSA"/></customer><customer_sign type="RSA"/><results timestamp="2006-07-06 17:21:50"><payment merchant_id="92056001" amount="1000" reference="618704198173" approval_code="447753" response_code="00"/></results></bank>';
$kkb = new KKBSign();
$kkb->invert();
if (!$kkb->load_private_key($config['PRIVATE_KEY_FN'],$config['PRIVATE_KEY_PASS'])){
	if ($kkb->ecode>0){return $kkb->estatus;};
};
$sign = $kkb->sign($xml);
$sign = base64_decode($sign);
echo $kkb->check_sign($xml, $sign, $config['PUBLIC_KEY_FN']);*/

$xml = '<document><bank name="Kazkommertsbank JSC"><customer name="Ucaf Test Maest" mail="SeFrolov@kkb.kz" phone=""><merchant cert_id="00C182B189" name="test merch"><order order_id="0706172110" amount="1000" currency="398"><department merchant_id="92056001" amount="1000"/></order></merchant><merchant_sign type="RSA"/></customer><customer_sign type="RSA"/><results timestamp="2006-07-06 17:21:50"><payment merchant_id="92056001" amount="1000" reference="618704198173" approval_code="447753" response_code="00"/></results></bank><bank_sign cert_id="00C18327E8" type="SHA/RSA">xjJwgeLAyWssZr3/gS7TI/xaajoF3USk0B/ZfLv6SYyY/3H8tDHUiyGcV7zDO5+rINwBoTn7b9BrnO/kvQfebIhHbDlCSogz2cB6Qa2ELKAGqs8aDZDekSJ5dJrgmFT6aTfgFgnZRmadybxTMHGR6cn8ve4m0TpQuaPMQmKpxTI=</bank_sign ></document>';

$xml_parser = new xml();
$result = $xml_parser->parse($xml);
foreach ($result as $key => $value) {echo "	//\$data['".$key."'] = \"".$value."\"<br>";};


?>