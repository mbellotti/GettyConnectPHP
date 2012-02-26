<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
function create_session(){
 
// set params
$endpoint = "https://connect.gettyimages.com/v1/session/CreateSession";
$systemId = "";
$systemPassword = "";
$userName = "";
$userPassword = "";
$token = null;
 
// create array of data for request
$createSessionArray = array(
          "RequestHeader" => array(
                   "Token" => "",
                   "CoordinationId" => ""
          ),
          "CreateSessionRequestBody" => array(
                   "SystemId" => $systemId,
                   "SystemPassword" => $systemPassword,
                   "UserName" => $userName,
                   "UserPassword" => $userPassword
          )
);
 
$json = json_encode($createSessionArray);

	//Get data
	 $data = curl_me($endpoint, $json, true);
	 $success = $data->ResponseHeader->Status;
	if($success == "success"){
		$token[] = $data->CreateSessionResult->Token;
          // or retrieve secure token
         $token[] = $data->CreateSessionResult->SecureToken;
	}else{
		$token = false;
	}
	
	return $token;

}

/*//////////////////////////////////////////////////////////////////////////////
//
//  [Searching for Images] Pass in your search terms and the unsecure token
//
/*////////////////////////////////////////////////////////////////////////////*/

function search($searchPhrase, $token){
	$endpoint = 'http://connect.gettyimages.com/v1/search/SearchForImages';
	
	$searchImagesArray = array (
	          "RequestHeader" => array (
	                   "Token" => $token // Token received from a CreateSession/RenewSession API call
	          ),
	          "SearchForImages2RequestBody" => array (
	                   "Query" => array (
	                             "SearchPhrase" => $searchPhrase
	                   ),
	                   "Filter" => array(
	                                       "ImageFamilies" => array("creative") // specify only creative image family here
	                    ),
	                   "ResultOptions" => array (
	                             "IncludeKeywords" => "false",
	                             "ItemCount" => 25, // return 25 items
	                             "ItemStartNumber" => 1 // 1-based int, start at the first page
	                   )
	          )
	);

	// encode to json
	$json = json_encode($searchImagesArray);
	
	$data = curl_me($endpoint, $json);
	
	$success = $data->ResponseHeader->Status;
	if($success == "success"){
		//return data
		return $data->SearchForImagesResult->Images;
		
	}else if($success == "error" && $data->ResponseHeader->StatusList[0]->Code == 'AUTH-012'){
		//AUTH-012 means our tokens have expired
		return false;
	}else{
		//Otherwise this will contend the era
		return $data->ResponseHeader;
	}
}


/*//////////////////////////////////////////////////////////////////////////////
//
//  [Requesting Download] Pass image id and the unsecure token
//
/*////////////////////////////////////////////////////////////////////////////*/
	
	function buy_getty($id, $token){
	     $imageIdArray[] = array("ImageId" => $id);

		// build request to get largest available download of this image
		$endpoint = "http://connect.gettyimages.com/v1/download/GetLargestImageDownloadAuthorizations";

		// build array to query api for images
		$imageAuthorizationArray = array (
		          "RequestHeader" => array (
		                   "Token" => $token
		          ),
		          "GetLargestImageDownloadAuthorizationsRequestBody" => array (
		                   "Images" => $imageIdArray
		          )
		);

		// encode
		$json = json_encode($imageAuthorizationArray);
		
		$data = curl_me($endpoint, $json);
		
		$success = $data->ResponseHeader->Status;
		if($success == "success"){
			//return data
			return $data->GetLargestImageDownloadAuthorizationsResult->Images[0]->Authorizations[0]->DownloadToken;

		}else if($success == "error" && $data->ResponseHeader->StatusList[0]->Code == 'AUTH-012'){
			return false;
		}else{
			return $data->ResponseHeader;
		}
	}
	
	
	/*//////////////////////////////////////////////////////////////////////////////
	//
	//  [Accessing Download] Pass in your SECURE token and the download token retrieved
	//   from download request. This request requires https
	//
	/*////////////////////////////////////////////////////////////////////////////*/
	
	function get_download($sectoken, $dtoken){
		// image ID for an image we know exists in the system
		$imageDownloadArray = array(array("DownloadToken" => $dtoken));

		// build request to get largest available download of this image
		$endpoint = "https://connect.gettyimages.com/v1/download/CreateDownloadRequest";

		// build array to query api for images
		$imageAuthorizationArray = array (
		          "RequestHeader" => array (
		                   "Token" => $sectoken
		          ),
				"CreateDownloadRequestBody" => array(
			        "DownloadItems" => $imageDownloadArray
			      )
		);

		// encode
		$json = json_encode($imageAuthorizationArray);
		
		$data = curl_me($endpoint, $json, true);
		$success = $data->ResponseHeader->Status;
		if($success == "success"){
			//return data
			return $data;

		}else if($success == "error" && $data->ResponseHeader->StatusList[0]->Code == 'AUTH-012'){
			return false;
		}else{
			return $data->ResponseHeader;
		}
	}
	

function curl_me($endpoint, $json, $sec = false){
	$ch = curl_init();
	  $timeout = 5;
	  curl_setopt($ch,CURLOPT_URL,$endpoint);
	  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
	  curl_setopt($ch, CURLOPT_POSTFIELDS, $json);                                                                  
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	//To get cURL to handle https we need some more settings
	if($sec == true){
		//Unsafe but quick way
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		//Right way
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/CAcerts/BuiltinObjectToken-EquifaxSecureCA.crt");
	}
	
	
	  curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
	    'Content-Type: application/json',                                                                                
	    'Content-Length: ' . strlen($json))                                                                       
	);
	  $data = json_decode(curl_exec($ch));
	  curl_close($ch);
	return $data;
}
