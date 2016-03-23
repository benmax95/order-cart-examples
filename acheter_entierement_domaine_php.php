<?php

function extract_json($json){
	
	$json = str_replace('{', "", $json); 
	$json = str_replace('}', "", $json); 
	$json = str_replace('"', "", $json); 
	$json = (explode(',',$json));
	foreach($json as $k=>$v){
		$json[$k] = explode(':', $v);
	}
	
	return $json;
	
}

# Copyright (c) 2013, OVH SAS.
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#
#* Redistributions of source code must retain the above copyright
#  notice, this list of conditions and the following disclaimer.
#* Redistributions in binary form must reproduce the above copyright
#  notice, this list of conditions and the following disclaimer in the
#  documentation and/or other materials provided with the distribution.
#* Neither the name of OVH SAS nor the
#  names of its contributors may be used to endorse or promote products
#  derived from this software without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY OVH SAS AND CONTRIBUTORS ``AS IS'' AND ANY
# EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
# WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL OVH SAS AND CONTRIBUTORS BE LIABLE FOR ANY
# DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
# (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
# ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
# (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
# SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

define('OVH_API_EU', 'https://eu.api.ovh.com/1.0');
define('OVH_API_CA', 'https://ca.api.ovh.com/1.0');

class OvhApi {

    var $AK;
    var $AS;
    var $CK;
    var $timeDrift = 0;
    function __construct($_root, $_ak, $_as, $_ck) {
        // INIT vars
        $this->AK = $_ak;
        $this->AS = $_as;
        $this->CK = $_ck;
        $this->ROOT = $_root;

        // Compute time drift
        $serverTimeRequest = file_get_contents($this->ROOT . '/auth/time');
        if($serverTimeRequest !== FALSE)
        {
            $this->timeDrift = time() - (int)$serverTimeRequest;
        }
    }
    function call($method, $url, $body = NULL)
    {
        $url = $this->ROOT . $url;
        if($body)
        {
            $body = json_encode($body);
        }
        else
        {
            $body = "";
        }

        // Compute signature
        $time = time() - $this->timeDrift;
        $toSign = $this->AS.'+'.$this->CK.'+'.$method.'+'.$url.'+'.$body.'+'.$time;
        $signature = '$1$' . sha1($toSign);

        // Call
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'X-Ovh-Application:' . $this->AK,
            'X-Ovh-Consumer:' . $this->CK,
            'X-Ovh-Signature:' . $signature,
            'X-Ovh-Timestamp:' . $time,
        ));
        if($body)
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }
        $result = curl_exec($curl);

        if($result === FALSE)
        {
            echo curl_error($curl);
            return NULL;
        }

        return $result;
    }
    function get($url)
    {
        return $this->call("GET", $url);
    }
    function put($url, $body)
    {
        return $this->call("PUT", $url, $body);
    }
    function post($url, $body)
    {
        return $this->call("POST", $url, $body);
    }
    function delete($url)
    {
        return $this->call("DELETE", $url);
    }
}
$ak = "VOTRE AK"; 
$as = "VOTRE AS"; 
$ck = "VOTRE CK"; 


$ovh = new OvhApi(OVH_API_EU, $ak, $as, $ck); 

//CREATION PANIER
$cart = $ovh->post('/order/cart', array( 
            'expire'      =>  '2020-10-08T14:28:46+02:00', 
            'ovhSubsidiary'          =>  'FR', 
        )
	); 


$cart = extract_json($cart);
	
$cartId = $cart[0][1];

echo $cartId." -> id_cart<br/>";
//ASSIGNATION PANIER A VOTRE COMPTE
$assign = $ovh->post('/order/cart/'.$cartId.'/assign');

//AJOUT ET VERIFICATION DOAINE A ACHETER
$domain = $ovh->post('/order/cart/'.$cartId.'/domain', array( 
            'domain'          =>  'LE NOM DE DOMAINE A POURVOIR', 
        )
	); 

$domain = extract_json($domain);

$domainEtat = $domain[0][1];

//CHECK SI VALIDE
if($domainEtat!="This domain is already registered"){
	//RECUPERATION ID ITEM DOMAINE
	$item = $ovh->get('/order/cart/'.$cartId.'/item');
	
	$item = str_replace('[', "", $item); 
	$item = str_replace(']', "", $item); 
	
	echo $item." -> id_item<br/>";
	
	if(is_numeric($item)){
		//CREATION DU OWNER
		$contact = $ovh->post('/me/contact', array(
			"address" => array(
				"country" => "FR",
				"line1" => "",
				"city" => "",
				"zip" => "",
				"province" => ""
			),
			"email" => "VOTRE EMAIL",
			"firstName" => "VOTRE PRENOM",
			"nationalIdentificationNumber" => "CODE INPI (?)",
			"gender" => "male",
			"language" => "fr_FR",
			"lastName" => "VOTRE NOM",
			"legalForm" => "corporation",
			"nationality" => "FR",
			"organisationName" => "VOTRE ENTREPRISE",
			"phone" => "VOTRE TELEPHONE"
		  )
		); 


		$contact = str_replace('{', "", $contact); 
		$contact = str_replace('}', "", $contact); 
		$contact = str_replace('"', "", $contact); 
		$contact = (explode(',',$contact));
		foreach($contact as $k=>$v){
			$contact[$k] = explode(':', $v);
			if($contact[$k][0]=='id'){
				$id_contact = $contact[$k][1];
			}
		}
		echo $id_contact." -> id_contact<br/>";
	
		//CREATION DE L'INPI
		$inpi = $ovh->post('/domain/data/afnicCorporationTrademarkInformation', array(
			  "contactId" => (int)$id_contact,
			  "inpiNumber" => "VOTRE INPI",
			  "inpiTrademarkOwner" => "VOTRE ENTREPRISE"
		  )
		);
		
		
		
		$inpi = str_replace('{', "", $inpi); 
		$inpi = str_replace('}', "", $inpi); 
		$inpi = str_replace('"', "", $inpi); 
		$inpi = (explode(',',$inpi));
		foreach($inpi as $k=>$v){
			$inpi[$k] = explode(':', $v);
			if($inpi[$k][0]=='id'){
				$idinpi = $inpi[$k][1];
			}
		}

		
		
		
		echo $idinpi." -> INPI <br/>";
		
		//ASSIGNATION ITEM OWNER
		$configuration_owner = $ovh->post('/order/cart/'.$cartId.'/item/'.$item.'/configuration', array(
			'label' => 'OWNER_CONTACT',
			'value' => '/me/contact/'.$id_contact,
		  )
		);
		
		echo $configuration_owner." -> id_config (OWNER_CONTACT)<br/>";;

		//ASSINGATION ITEM INPI
		$configuration_inpi = $ovh->post('/order/cart/'.$cartId.'/item/'.$item.'/configuration', array(
			'label' => 'INPI',
			'value' => '/domain/data/afnicCorporationTrademarkInformation/'.$idinpi,
		  )
		);

		
		echo $configuration_inpi." -> id_config (INPI)<br/>";;
		
		//VALIDATION DU PANIER
		$checkout = $ovh->post('/order/cart/'.$cartId.'/checkout');
	
		echo "<hr><br/><br/>";
		print_r(extract_json($checkout));
		
		// FIN ? -> RETURN 0;

	}
	
	
	
}	else{
	
	echo "Abandon car non achetable";
	
}

?>
