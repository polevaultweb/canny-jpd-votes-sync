## Canny Jira Product Discovery Votes Sync

Sync votes on Canny posts to a custom field on an Idea in a Jira Product Discovery board.

## Pre-Requisites

- PHP
- Composer

## Installation

- Clone the repository
- Run `composer install` 
- Run `cp .env.example .env`
- Fill in the credentials and information in the `.env` file as outlined below

## Environment Variables

### JIRA_SUBDOMAIN

The subdomain used in Jira URLs, eg. https://acme.atlassian.net/ = acme. 

### JIRA_API_TOKEN

Create an API token from your Atlassian account [here](https://id.atlassian.com/manage-profile/security/api-tokens).

### JIRA_EMAIL_ADDRESS

Use the email address you login to Jira with.

### JIRA_PROJECT_PREFIX

The project prefix using for the Jira Product Discovery (JPD) board. For example, if the board URL is https://YOUR_COMPANY.atlassian.net/jira/polaris/projects/MTKA, then the prefix is MTKA.

### JIRA_CUSTOM_FIELD_ID

This app assumes you have created a custom field on your JPD board to store the number of votes from Canny on the idea.

To find the internal Jira ID for the custom field you have created:

- On an existing JPD idea, enter the value of the Canny votes custom field as 9999
- Note the idea id, eg. MTKA-123
- Run `php get-jira-fields.php MTKA-123`
- Search the command response for '9999', the array key is the custom field ID, eg. `customfield_17064`

### CANNY_API_KEY

Get the Canny API key from https://[YOUR-COMPANY].canny.io/admin/settings/api or ask a Canny admin for it.

### CANNY_BOARD_ID

This is the internal ID for the Canny Board. 

To find the internal ID for the Canny board you want to use:

- Run `php get-canny-boards.php`
- Find the board name in the response 
- Grab the ID property

## Usage

Once all the environment variables are populated, to sync the votes between Canny posts and JPD issues, run:

```php sync.php```

