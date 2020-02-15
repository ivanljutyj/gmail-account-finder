<?php

require_once 'vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
    throw new \Exception('This appication must be run from the CLI');
}

function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Gmail Account Finder');
    $client->setScopes(Google_Service_Gmail::GMAIL_READONLY);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            if (array_key_exists('error', $accessToken)) {
                throw new \Exception(join(', ', $accessToken));
            }
        }
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 700, true);
        }

        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }

    return $client;
}


function decodeMessages($messages)
{
    $client = getClient();
    $gmail = new Google_Service_Gmail($client);
    foreach($messages as $message) {
        $response = $gmail->users_messages->get('me', $message->getId(), ['format' => 'full']);
        $parts = $response->getPayload()->getParts();
        $headers = $response->getPayload()->getHeaders();
        if (isset($parts[0]['body'])) {
            $rawData = $parts[0]['body']->data;
            $sanitizedData = strtr($rawData,'-_', '+/');

            $decodedEmail = trim(base64_decode($sanitizedData));
            if ($foundKeyword = findWebsiteAssocWithEmail($decodedEmail)) {
                 printf("Website: %s\t Keyword: %s\n", parseSenderEmailFromHeaders($headers), $foundKeyword);
            }
        }
    }
}

function getMessages($nextPage = null)
{
    $client = getClient();
    $gmail = new Google_Service_Gmail($client);

    $options = [
        'labelIds' => 'INBOX',
        'maxResults' => 100
    ];

    if ($nextPage) {
        $options['pageToken'] = $nextPage;
    }

    $response = $gmail->users_messages->listUsersMessages('me', $options);

    decodeMessages($response->getMessages());

    $nextPageToken = $response->getNextPageToken();

    if ($nextPageToken) {
        getMessages($nextPageToken);
    }
}

function findWebsiteAssocWithEmail(string $emailContent)
{
    $keywords = [
        'account created',
        'welcome to',
        'verify your account',
        'confirm your email',
        'verify your email address',
        'registration',
        'activate account',
        'confirmation',
        'unsubscribe'
    ];

    foreach ($keywords as $keyword) {
        if (stripos($emailContent, $keyword) !== false) {
            return $keyword;
        }
    }

    return false;
}

function parseSenderEmailFromHeaders($headers)
{
    foreach($headers as $header)
    {
        if ($header['name'] === 'From') {
            return parseDomain($header['value']);
        }
    }
}

function parseDomain($string)
{
    preg_match ("/@(.*?)>/", $string, $result);
    $parts = explode(".", $result[1]);

    return (array_key_exists(count($parts) - 2, $parts) ? $parts[count($parts) - 2] : "").".".$parts[count($parts) - 1];
}

getMessages();
