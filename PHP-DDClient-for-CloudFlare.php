<?php

$Domain    = 'mydomain.es';
$API_EMAI  = 'mymail@mymail.com';
$API_KEY   = 'YOUR-API-KEY-HERE';
$SubDomain = 'SubDomain-Want-Change-Here';




$V4_URL       = 'https://api.cloudflare.com/client/v4';
$Hjson        = 'Content-Type: application/json';
$HEmail       = "X-Auth-Email: $API_EMAI";
$HAuthKey     = "X-Auth-Key: $API_KEY";
$DNSTYPE      = 'A';
$ArrayHeaders = array(
    $HEmail,
    $HAuthKey,
    $Hjson
);

Function CurlGet($URI, $HEADERS)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, $URI);
    if (!empty($HEADERS)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADERS);
    }
    ;
    curl_setopt($curl, CURLOPT_VERBOSE, false);
    $CurlResponse = curl_exec($curl);
    curl_close($curl);
    return $CurlResponse;
}
;
Function CurlPut($URI, $HEADERS, $DATA)
{
    $Size    = "Content-Length: " . strlen($DATA);
    //              array_push($HEADERS,$Size,array( 'Expect:' ));
    $putData = tmpfile();
    fwrite($putData, $DATA);
    fseek($putData, 0);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_PUT, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADERS);
    curl_setopt($curl, CURLOPT_URL, $URI);
    curl_setopt($curl, CURLOPT_INFILE, $putData);
    curl_setopt($curl, CURLOPT_INFILESIZE, strlen($DATA));
    curl_setopt($curl, CURLOPT_VERBOSE, false);
    $CurlResponse = curl_exec($curl);
    curl_close($curl);
    fclose($putData);
    return $CurlResponse;
    
}
;





function GetIPCurrentIPv4()
{
    $IPV4Server  = 'https://ipv4.icanhazip.com/';
    $result      = CurlGet($IPV4Server, '');
    $resultClean = str_replace("\n", "", $result);
    if (!filter_var($resultClean, FILTER_VALIDATE_IP) === false) {
        return $resultClean;
    } else {
        echo ("$resultClean WARNING: Current ip is not a valid IP address");
    }
}
;





Function GetCloudFlareZoneID($V4_URL, $ArrayHeaders, $Domain)
{
    $URL                = "$V4_URL/zones?name=$Domain";
    $CurlZoneIDResponse = CurlGet($URL, $ArrayHeaders);
    $ArrayResponse      = json_decode($CurlZoneIDResponse, true);
    $zoneID             = $ArrayResponse["result"][0]["id"];
    
    if ($ArrayResponse = NULL) {
        return "Error in the cloudflare Api: Invalid json response ";
    } else {
        return $zoneID;
    }
    
}
;


Function GetCloudFlareIP($V4_URL, $ArrayHeaders, $zoneID, $SubDomain, $DNSTYPE)
{
    $URL                = "$V4_URL/zones/$zoneID/dns_records?type=$DNSTYPE&name=$SubDomain&page=1&per_page=20&order=type&direction=desc&match=all";
    $CurlZoneIpResponse = CurlGet($URL, $ArrayHeaders);
    $ArrayResponse      = json_decode($CurlZoneIpResponse, true);
    $ip                 = $ArrayResponse["result"][0]["content"];
    $DomainID           = $ArrayResponse["result"][0]["id"];
    if ($ArrayResponse = NULL) {
        return "Error in the cloudFlare Api: Invalid json response ";
    } else {
        if (!filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $array = array(
                "ip" => $ip,
                "domainid" => $DomainID
            );
            
        } else {
            echo ("Cloudflare error  ip address in the response is not a valid IP address, plase check if $SubDomain exist.");
        }
    }
    
}
;

Function ChangeCloudFlareIP($V4_URL, $ArrayHeaders, $zoneID, $SubDomain, $DNSTYPE, $CloudFlareSubDomainID, $CurrentIP)
{
    $URL           = "$V4_URL/zones/$zoneID/dns_records/$CloudFlareSubDomainID";
    $data          = "{\"id\":\"$CloudFlareSubDomainID\",\"content\":\"$CurrentIP\",\"type\":\"$DNSTYPE\",\"name\":\"$SubDomain\",\"data\":{}}";
    $CurlResponse  = CurlPut($URL, $ArrayHeaders, $data);
    $ArrayResponse = json_decode($CurlResponse, true);
    return $ArrayResponse["success"];
}
;


$zoneID                = GetCloudFlareZoneID($V4_URL, $ArrayHeaders, $Domain);
$CurrentIP             = GetIPCurrentIPv4();
$domainData            = GetCloudFlareIP($V4_URL, $ArrayHeaders, $zoneID, $SubDomain, $DNSTYPE);
$CloudFlareCurrentIP   = $domainData["ip"];
$CloudFlareSubDomainID = $domainData["domainid"];
if ($CurrentIP == $CloudFlareCurrentIP) {
    echo "Ip address not changed.";
} else {
    ChangeCloudFlareIP($V4_URL, $ArrayHeaders, $zoneID, $SubDomain, $DNSTYPE, $CloudFlareSubDomainID, $CurrentIP);
    if (ChangeCloudFlareIP) {
        PRINT "Ip address changed, new ip address is: $CurrentIP";
    } else {
        PRINT "Ip address cannot changed,  cloudflare api response error";
    }
}



?>
