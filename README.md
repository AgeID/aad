---
services: active-directory
platforms: php
author: AgeID
---

# Authenticating requests to an Azure AD resource from a PHP web app

This PHP class allows obtaining an authentication token object from Azure Active Directory, which in turn is used to generate an Authorization header that you must add to all your requests to the desired Azure Active Directory application (resource).
This class uses OAuth 2.0 Client Credentials to authenticate and get authorization to the Graph API.
Pre-existing access to an Azure tenant is required. Using a standard Microsoft account will not be enough.

### Using the PHP class
1. Construct your AzureActiveDirectoryHelper instance by passing the $clientId, $tenant and, if desired, a custom implementation of the Guzzle client inside $options['httpClient']
2. Obtain an Auth Token object by providing the appropriate parameter values, as generated and obtained from above: $authToken = $aadHelper->getToken($clientId, $resource).
3. Prepare your cURL request to the AAD service according to its API usage definition.
4. Add the obtained $authToken to your cURL request along with any other applicable headers. Example: curl_setopt($ch, CURLOPT_HTTPHEADER, array($authToken,  'OtherExampleHeaderName:OtherExampleHeaderValue', ...)).
5. Execute the cURL request.