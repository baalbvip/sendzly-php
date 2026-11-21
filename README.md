# Sendzly PHP & Laravel SDK (`sendzly/sendzly-php`)

SDK oficial de **[Sendzly](https://sendzly.com)** para **PHP 8+** y **Laravel 10 / 11 / 12**. Envía correos transaccionales (MT 1-a-1), códigos OTP, facturas y plantillas dinámicas en milisegundos con SPF/DKIM/DMARC verificado y escudo anti-rebotes.

## Instalación

```bash
composer require sendzly/sendzly-php
```

---

## Uso en PHP nativo (Cero dependencias)

```php
<?php

require 'vendor/autoload.php';

use Sendzly\SendzlyClient;

$sendzly = new SendzlyClient(
    mailserverKey: 'sz_ms_tu_dominio_verificado',
    campaignKey: 'sz_camp_tu_campaign'
);

$response = $sendzly->send([
    'to' => 'cliente@empresa.com',
    'subject' => 'Bienvenido a nuestra plataforma, $name',
    'html' => '<h1>Hola $name</h1><p>Tu código de acceso es <strong>$code</strong></p>',
    'variables' => [
        'name' => 'Carlos',
        'code' => '492817',
    ],
]);
```

### Atajo rápido para códigos OTP

```php
$sendzly->sendOtp('cliente@empresa.com', '492817', 'Carlos');
```

---

## Uso nativo en Laravel 10 / 11 / 12 (`MAIL_MAILER=sendzly`)

El `SendzlyServiceProvider` se auto-registra automáticamente. Solo agrega en tu `.env`:

```env
MAIL_MAILER=sendzly
SENDZLY_MAILSERVER_KEY=sz_ms_tu_dominio_verificado
SENDZLY_CAMPAIGN_KEY=sz_camp_tu_campaign
```

Y agrega el mailer `sendzly` en `config/mail.php`:

```php
'mailers' => [
    'sendzly' => [
        'transport' => 'sendzly',
    ],
],
```

¡Listo! Ya puedes usar `Mail::to($user)->send(new MiMailable())` y todo saldrá por Sendzly.

## Licencia

MIT © [Sendzly](https://sendzly.com)
