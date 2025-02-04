# LSS ![CI](https://github.com/noeldemartin/lss/actions/workflows/ci.yml/badge.svg)

> [!WARNING]
> This is **very experimental**, in fact it's nothing more than a proof of concept. So please refrain from reporting any bugs or feature requests. But feel free to look around and ask questions for educational purposes.

> [!NOTE]
> I am running an instance at [lss.noeldemartin.com](https://lss.noeldemartin.com/), but registrations are disabled at the moment. If you're interested in giving it a try, [let me know](mailto:hey@noeldemartin.com) and maybe I will create an account for you :).

LSS (Laravel Solid Server) is a [Solid server](https://solidproject.org) built with [Laravel](https://laravel.com/).

Currently, it's just a proof of concept and it doesn't support most of the hairy stuff (ACLs, content negotiation, etc.). But the basic CRUD operations and authentication are implemented, and it already works with a couple of apps like [Ramen](https://ramen.noeldemartin.com) and [Focus](https://focus.noeldemartin.com).

Furthermore, my initial motivation to start tinkering with this was to see how hard it would be to implement a Solid Server that uses external Cloud providers for storage (such as Nextcloud, Dropbox, Google Drive, etc.). And I'm glad to say that [I got it working in a couple of days](https://noeldemartin.social/@noeldemartin/113005202062248847). Again, it still needs a lot of work to be ready for production, but I'm hopeful that it may be easier than I thought... We'll see.

## Development

```sh
git clone git@github.com:NoelDeMartin/lss.git lss
cd lss
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan passport:keys
php artisan migrate:fresh --seed
php artisan serve --host=localhost
```

## Production

```sh
git clone https://github.com/NoelDeMartin/lss.git lss --branch kanjuro --single-branch
cd lss
kanjuro install
kanjuro start
nginx-agora start
```
