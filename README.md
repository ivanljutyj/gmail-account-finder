# Gmail Account Finder

### Purpose of this project

The goal was to find out all the email addresses associated to my gmail account and to get used to working with the Google PHP API client.

### Installation

```
git clone https://github.com/ivanljutyj/gmail-account-finder.git
cd gmail-account-finder
composer install
```

### Additional Steps
You will also need to generate a `credentials.json` file from google in order to authenticate with google services and place it in the root project directory.

An easy way to generate that file would be by visiting this page: https://developers.google.com/gmail/api/quickstart/php#step_1_turn_on_the
and clicking the `Enable the Gmail API` button.

### Usage
```
php gmail-account-finder.php
```
You will be prompted to authenticate with google and you will be provided with a key to authenticate yourself.

