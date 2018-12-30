Dependencies:
composer
php7.2-xml
php7.2-curl
php7.2-mbstring
php7.2-zip

Installation

1. Install composer dependencies:
composer install

2. Make and run migrations:
bin/console make:migration
bin/console doctrine:migrations:migrate

3. Create admin user:
php bin/console make:fixtures
