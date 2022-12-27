# Lynk System


## requirements

- webserver
- composer
- PHP 8.0.2
- MySQL database


## Installation

`You can get the project via composer using:
`

``` bash
git clone https://gitlab.com/businessinnovationmine/lynk/lynk-backend.git
```

- create two tables for example [lynk - lynk-wallet]
- create .env file in the main directory it should be like this:


```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:MZo4oo/P3ICB2/xnVx/y2EWgLlm3i1KQvfAaTIr0EI0=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lynk
DB_USERNAME=root
DB_PASSWORD=

WALLET_DB_HOST=127.0.0.1
WALLET_DB_PORT=3306
WALLET_DB_DATABASE=lynk_wallet
WALLET_DB_USERNAME=root
WALLET_DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=array
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

OCI_ACCESS_KEY_ID=f02e8cde155d7cfbb33e18207977ba52a1c82f38
OCI_SECRET_ACCESS_KEY=X2lcaX/nw3NaoNhCcCWp9maBReWC140XZau55mdWZ5M=
OCI_DEFAULT_REGION=me-jeddah-1
OCI_BUCKET=testing-bucket
OCI_ENDPOINT=https://axluzxsuyumj.compat.objectstorage.me-jeddah-1.oraclecloud.com

OCI_PUBLIC_ACCESS_KEY_ID=f02e8cde155d7cfbb33e18207977ba52a1c82f38
OCI_PUBLIC_SECRET_ACCESS_KEY=X2lcaX/nw3NaoNhCcCWp9maBReWC140XZau55mdWZ5M=
OCI_PUBLIC_DEFAULT_REGION=me-jeddah-1
OCI_PUBLIC_BUCKET=public-testing-bucket
OCI_PUBLIC_ENDPOINT=https://axluzxsuyumj.compat.objectstorage.me-jeddah-1.oraclecloud.com

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

OTPIFY_DEFAULT_DRIVER=
OTPIFY_CODE_LENGTH=4
OTPIFY_CODE_EXPIRATION_TIME=10

OTPIFY_TWILIO_SID=
OTPIFY_TWILIO_TOKEN=
OTPIFY_TWILIO_FROM=
OTPIFY_TWILIO_VERIFY_SID=
OTPIFY_TWILIO_SSL_VERIFY_PEER=
OTPIFY_TWILIO_SSL_VERIFY_HOST=
AUTHORIZED_TOKEN_LENGTH=

PERMISSION_GUARDS=web,api

GRANTIFY_DEFAULT_DRIVER=

SETTINGS_CACHE_ENABLED=
HOST_WHITELIST=localhost
BITLY_ACCESS_TOKEN=5c67bf1e06f40fcb04c1aa89643e52bca93c15d1
MOBILE_VERIFY_DEFAULT_DRIVER=fake_tcc
SMS_DEFAULT_DRIVER=fake_sms
OTPIFY_NATIONAL_ID_DEFAULT_DRIVER=fake_absher
DEFAULT_TRADER=dmcc
DEFAULT_PDF_GENERATOR=browserless
BROWSERLESS_BASE_URL=http://dev-docker.uselynk.com
BROWSERLESS_STORAGE_DRIVER=local

MEDIA_DISK=local
```


### To install the project via the composer rune this command

``` Bash
composer install
```

### To create the database migration & seed important data

``` Bash
php artisan migrate --seed
```

### now you have a super admin account with the following credentials

```
email: admin@bim.com
password: 12345678
```
