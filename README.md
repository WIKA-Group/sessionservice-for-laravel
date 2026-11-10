# Session Service for Laravel

This composer package handles the backend integration of the Session Service: redirecting guests to the login, exchanging the one-time code for a session, logging in the local user and logging out.

## Installation
**Install the package:**
```bash
composer require wika-group/sessionservice-for-laravel
```

**Publish and run the migration** (adds the OAuth ID column to the `users` table):
```bash
php artisan vendor:publish --tag=sessionservice-migrations
php artisan migrate
```

**Extend .env and configure:**
```ini
SESSION_SERVICE_TENANT_ID=
SESSION_SERVICE_CLIENT_ID=
SESSION_SERVICE_CLIENT_SECRET=
SESSION_SERVICE_BASE_URL=      # e.g. https://session.domain.com
SESSION_SERVICE_REDIRECT_URL=  # Callback URL of your app, e.g. https://your-app.com/sessionservice/callback
SESSION_SERVICE_SCOPE=         # e.g. api://{session-service-client-id}/.default
```

**Optional: Publish config:**
```bash
php artisan vendor:publish --tag=sessionservice-config
```

The config also contains the route paths (`callback`, `logout`) and the fallback redirect target after login (`home`).
All package routes are prefixed with `/sessionservice`. The resulting callback path must match `SESSION_SERVICE_REDIRECT_URL`.

## Usage
### Protect routes
Append the middleware to the `web` group in `bootstrap/app.php` so every web request requires a session.
Guests are redirected to the Session Service login and return to the originally requested URL afterwards.

```php
use WikaGroup\SessionService\Http\Middleware\EnsureSessionServiceAuthenticated;
use WikaGroup\SessionService\SessionService;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        EnsureSessionServiceAuthenticated::class,
    ]);
    $middleware->redirectGuestsTo(fn () => app(SessionService::class)->getLoginUrl());
})
```

Alternatively, assign it to individual routes only:
```php
Route::get('/dashboard', Dashboard::class)->middleware(EnsureSessionServiceAuthenticated::class);
```

### Provided routes
| Method | Path                       | Name                      | Description                                                                 |
|--------|----------------------------|---------------------------|-----------------------------------------------------------------------------|
| GET    | `/sessionservice/callback` | `sessionservice.callback` | Exchanges the code, finds/creates the user by OAuth ID or email and logs in |
| POST   | `/sessionservice/logout`   | `sessionservice.logout`   | Logs out locally and redirects to the Session Service logout                |

### Logout button
```html
@auth
    <form method="POST" action="{{ route('sessionservice.logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
@endauth
```

### Using the service directly
```php
use WikaGroup\SessionService\SessionService;

$service = app(SessionService::class);

$service->getLoginUrl();
$service->getLogoutUrl();
$service->exchangeCode($code); // session_token, expires_at, user_id, email, first_name, last_name, display_name
```

The client credentials token for the server-to-server call is cached for 55 minutes (`session-service:cc-token`).
