# Features

Created using Docker containers melted together with Docker Compose.

- Fetch transactions from Sbanken every 10 seconds
- Send push messages using Pushbullet for every new and modified transaction

Fetcher:
- Save transactions to a data folder
- If new it is a new transaction - notify a HTTP service
- If transactions is modified - notify a HTTP service with the modification

Push messages:
- Default service is to send push messages using Pushbullet

# Setup

Push-msg:

- Copy pushbullet/src/config.sample.json to pushbullet/src/config.json
- Enter Pushbullet API access token (created from account settings)
- npm install

Mock:
- npm install

# Start system using docker-compose

- sudo docker-compose up
