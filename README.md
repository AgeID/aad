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
1. Obtain an Auth Token object by providing the appropriate parameter values, as generated and obtained from above: $authToken = AAD_AuthHelper::getAuthToken($tenantName, $clientId, $clientKey, $resource, $previousToken = null).
   > The last parameter is not needed for the first request. If you're storing the Auth Token object in your cache, you can pass it as the $previousToken parameter to avoid making unnecessary web requests to Azure if your existing token is still valid.
2. [Recommended] Store your $authToken in cache, especially if your application is making many calls per hour. Pass this to getAuthToken function every time before a new request.
3. Obtain the Authorization header for Azure AD: $authHeader = AAD_AuthHelper::getAuthHeader($authToken) by passing the token object from before.
4. Prepare your CURL request to the AAD service according to its API usage definition.
5. Add the obtained $authHeader to your CURL request along with any other applicable headers. Example: curl_setopt($ch, CURLOPT_HTTPHEADER, array($authHeader,  'OtherExampleHeaderName:OtherExampleHeaderValue', ...)).
6. Execute the CURL request.