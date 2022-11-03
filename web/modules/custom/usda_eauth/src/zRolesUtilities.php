<?php

namespace Drupal\usda_eauth;
use Drupal\Core\Session\AccountInterface;
use \SimpleXMLElement;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Site\Settings;  //used to access Settings

class zRolesUtilities implements zRolesUtilitiesInterface {

/**
 * {@inheritdoc}
 */
public static function accessIfAdmin(AccountInterface $account) {
   // Check permissions and combine that with any custom access checking needed.
   $session = \Drupal::request()->getSession();
   $sessionID = $session->get('ApplicationRoleEnumeration');
   if ($sessionID == 'CIG_App_Admin' || $session == 'CIG_APA')
      {
        $result = True;
      }
   else
      {
        $result = False;
      }
   return AccessResult::allowedIf($result);
 }

/**
 * {@inheritdoc}
 */
public static function accessIfAwardee(AccountInterface $account) {
   // Check permissions and combine that with any custom access checking needed.
   $session = \Drupal::request()->getSession();
   $sessionID = $session->get('ApplicationRoleEnumeration');
   if ($sessionID == 'CIG_NSHDS' || $sessionID == 'CIG_APT')
      {
        $result = True;
      }
   else
      {
        $result = False;
      }
   return AccessResult::allowedIf($result);
 }

/**
 * {@inheritdoc}
 */
public static function getUserAccessRolesAndScopes(String $eAuthId){

        /* Make a soap call to get the zRole info using the eAuth Id */
        $endpoint = Settings::get('zroles_url', '');
        $nrtApplicationId = Settings::get('nrtApplicationId', '');
        $wsSecuredToken = Settings::get('wsSecuredToken', '');

        $xml = '<?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Header>
            <AuthHeader xmlns="http://zRoles.sc.egov.usda.gov">
              <nrtApplicationId>' . $nrtApplicationId . '</nrtApplicationId>
                <wsSecuredToken>' . $wsSecuredToken . '</wsSecuredToken>
            </AuthHeader>
          </soap:Header>
          <soap:Body>
            <GetUserAccessRolesAndScopes xmlns="http://zRoles.sc.egov.usda.gov">
               <eauthId>'. $eAuthId  .  '</eauthId>
               <userAuthoritativeId>0</userAuthoritativeId>
               <nrtApplicationId>' . $nrtApplicationId . '</nrtApplicationId>
            </GetUserAccessRolesAndScopes>
          </soap:Body>
        </soap:Envelope>';

        // log the query going to zRoles
        \Drupal::logger('usda_eauth')->notice("zRoles request from getUserAccessRolesAndScopes: " . htmlspecialchars($xml));

         $curl = curl_init();

         curl_setopt_array($curl, [
         CURLOPT_URL => $endpoint,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => "",
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 30,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => "POST",
         CURLOPT_POSTFIELDS =>  $xml,
         CURLOPT_HTTPHEADER => [
             "Content-Type: text/xml"
             ],
         ]);

         $response = curl_exec($curl);
         $err = curl_error($curl);
         curl_close($curl);

         if ($err) {
            echo "cURL Error #:" . $err;
            $response = '';
           }

          // log the raw response coming back from zRoles
          \Drupal::logger('usda_eauth')->notice("raw zRoles response from getUserAccessRolesAndScopes: " . htmlspecialchars($response));

         return $response;
}


/**
 * {@inheritdoc}
 */
public static function getListByzRole(String $zRole) {
    /* This function only works in the USDA environment */
    // Get a list of employees having the zRole passed in

        $endpoint = Settings::get('zroles_url', '');
        $nrtApplicationId = Settings::get('nrtApplicationId', '');
        $wsSecuredToken = Settings::get('wsSecuredToken', '');

        $xml = '<?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Header>
            <AuthHeader xmlns="http://zRoles.sc.egov.usda.gov">
              <nrtApplicationId>' . $nrtApplicationId .'</nrtApplicationId>
                <wsSecuredToken>' . $wsSecuredToken .'</wsSecuredToken>
            </AuthHeader>
          </soap:Header>
          <soap:Body>
              <GetAuthorizedUsers xmlns="http://zRoles.sc.egov.usda.gov">
                 <aRole>' . $zRole .'</aRole>
                 <jurisdictions>
                     <Jurisdiction>
                     </Jurisdiction>
                </jurisdictions>
              </GetAuthorizedUsers>
           </soap:Body>
        </soap:Envelope>';

        // log the query going to zRoles
        \Drupal::logger('usda_eauth')->notice("zRoles request from getListByzRole: " . htmlspecialchars($xml));

         $curl = curl_init();

         curl_setopt_array($curl, [
         CURLOPT_URL => $endpoint,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => "",
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 30,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => "POST",
         CURLOPT_POSTFIELDS =>  $xml,
         CURLOPT_HTTPHEADER => [
             "Content-Type: text/xml"
             ],
         ]);

         $response = curl_exec($curl);
         $err = curl_error($curl);

         curl_close($curl);

         if ($err) {
            echo "cURL Error #:" . $err;
           }

        // log the raw response coming back from zRoles
        \Drupal::logger('usda_eauth')->notice("raw zRoles response from getListByzRole: " . htmlspecialchars($response));

        //the next 6 lines correct the first part of $response and removes unnecessary xml code
        $ResLen = strlen($response);
        $pos2 = strpos($response, '<soap:Body>');
        $len = $ResLen -$pos2+1;
        $value = substr($response,$pos2,$len);
        $value = '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope>' . $value;

        $data = new SimpleXMLElement($value );
        $data = json_decode(json_encode($data));
        $result = $data->{'soap:Body'}->{'GetAuthorizedUsersResponse'}->{'GetAuthorizedUsersResult'}->{'UserSummary'};

        // log the parsed zRoles response
        \Drupal::logger('usda_eauth')->notice("parsed zRoles response from getListByzRole: " . htmlspecialchars($response));

        return $result;
  }

/**
 * {@inheritdoc}
 */
public static function getTokenValue ($xmlString, $token)
       {
         $tokenLen = strlen($token)+2;
         $pos1 = strpos($xmlString, '<'. $token .'>')+$tokenLen;
         $pos2 = strpos($xmlString, '</'. $token .'>')-1;
         $len = $pos2 -$pos1+1;
         if ( ($pos1 > -1) and ($pos2 > -1) )
            {
              $value = substr($xmlString,$pos1,$len);
            }
         else
            {
            $value = '';
            }
       return $value;
}

  /**
   * {@inheritdoc}
   */
  public static function printUserInfo ($user) {
             if (gettype($user->{'UsdaeAuthenticationId'})=='string') { $userID = $user->{'UsdaeAuthenticationId'};}
              else {$userID ='NA';};
             if (gettype($user->{'FirstName'})=='string'){ $userFN = $user->{'FirstName'};}
              else {$userFN ='NA';};
             if (gettype($user->{'LastName'})=='string') { $userLN = $user->{'LastName'};}
              else {$userLN ='NA';};
             print_r( $userFN . '  ' . $userLN . ' ' . $userID . '  ' . '<br>');
             return;
             }

}