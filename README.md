# Vacansee API

Vacansee Bot is a Telegram bot which sends vacancies to users via [Vacansee API](https://github.com/mesolaries/vacansee-api).

## Deploying

Clone the repository, then install dependencies via composer:

```bash
$ composer install
```

Create `.env.local` file or modify `.env` file. Set `DATABASE_URL`, `TELEGRAM_BOT_TOKEN` and `VACANSEE_API_KEY` environment variables.

Create database and run migrations:

```bash
$ php bin/console doctrine:database:create
$ php bin/console doctrine:migrations:migrate
```

Sync channel mapping with Channel entity (you have to do this every time you add a new channel or modify existing one):

_Channel mapping is a constant in [Channel](src/Entity/Channel.php) entity_

```bash
$ php bin/console app:channel:sync
```

Done!

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](LICENSE)