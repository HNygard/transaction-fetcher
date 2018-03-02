# Features

Created using Docker containers melted together with Docker Compose.

- Fetch transactions from Sbanken every 10 seconds.
- Send push messages using Pushbullet for every new and modified transaction.
- Contains a mock of Sbanken API to simulate transaction list with new and modified transactions.

Fetcher:
- Save transactions to a data folder.
- If new it is a new transaction - notify a HTTP service.
- If transactions is modified - notify a HTTP service with the modification.
- Can configure the HTTP service to be something other than the default push messaging service.

Push messages:
- Service for sending push messages using Pushbullet

# Setup

Push-msg:

- Copy pushbullet/src/config.sample.json to pushbullet/src/config.json.
- Enter Pushbullet API access token (created from account settings).
- npm install

Mock:
- npm install

Fetcher:
- Optional (default is mock): Copy sbanken/src/config.prod.json to sbanken/src/config.json. Enter your API password, etc in the config.
- Will install using Composer during docker build.

# Start system using docker-compose

- sudo docker-compose up

# Using production config

- Copy sbanken/src/config.prod.json to sbanken/src/config.json.
- Modify sample values.
