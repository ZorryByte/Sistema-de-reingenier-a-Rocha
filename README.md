<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Datos de demostración

En el entorno local, ejecuta estos comandos sobre la base que quieras usar para la demostración.

```sh
php artisan migrate
php artisan db:seed --class=DemoSeeder
```

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@panaderia.example` | `PanaderiaDemo2026!` |
| Caja | `caja@panaderia.example` | `PanaderiaDemo2026!` |

El seeder agrega clientes, proveedores, recetas, compras, lotes de producción, movimientos de inventario, pedidos, ventas y gastos con asientos contables. Las operaciones usan fechas relativas al día de carga. El seeder se ejecuta por separado de `DatabaseSeeder` y omite la carga si ya existe la cuenta de administración de la demo.

## Despliegue en Vercel

1. Importa el repositorio con la raíz del proyecto y selecciona el preset **Other** y Node.js **24.x**. Vercel instala las dependencias desde los archivos lock y genera los recursos de Vite durante el build.
2. Activa **Automatically expose System Environment Variables**. En **Environment Variables**, configura `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tu-dominio`, `APP_KEY` (genera el valor con `php artisan key:generate --show`), `LOG_CHANNEL=stderr`, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true` y `CACHE_STORE=database`.
3. Configura una base MySQL externa con `DB_CONNECTION=mysql` y las variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. Desde un entorno autorizado conectado a esa base, ejecuta `php artisan migrate --force`. Para cargar los datos de referencia, ejecuta por separado los seeders `ConfiguracionEmpresaSeeder`, `CuentasContablesSeeder`, `CategoriasGastoSeeder` y `CategoriasRecetaSeeder`. Crea el administrador de producción con credenciales propias. Los seeders `DatabaseSeeder`, `AdminUserSeeder` y `DemoSeeder` crean cuentas con credenciales de prueba.
4. Para conservar comprobantes de gastos, configura `RECEIPTS_DISK=s3`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION` y `AWS_BUCKET` con un bucket privado. Para proveedores compatibles con S3 puedes configurar también `AWS_ENDPOINT` y `AWS_USE_PATH_STYLE_ENDPOINT`.
5. Despliega y comprueba `/up`, `/login` y la carga de los recursos en `/build/`.

Las vistas compiladas usan almacenamiento temporal en Vercel. Ejecuta los respaldos y las restauraciones de MySQL desde un entorno persistente que disponga de las utilidades de MySQL.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
