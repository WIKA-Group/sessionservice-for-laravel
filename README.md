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
    $middleware->redirectGuestsTo(fn () => resolve(SessionService::class)->getLoginUrl());
})
```

Alternatively, assign it to individual routes only:
```php
Route::get('/dashboard', Dashboard::class)->middleware(EnsureSessionServiceAuthenticated::class);
```

### Provided routes
| Method | Path                       | Name                      | Description                                                                 |
|--------|----------------------------|---------------------------|-----------------------------------------------------------------------------|
| GET    | `/sessionservice/login`    | `sessionservice.login`    | Top-level redirect to the Session Service login; `?return=` URL is used after login if its origin is allowed |
| GET    | `/sessionservice/callback` | `sessionservice.callback` | Exchanges the code, finds/creates the user by OAuth ID or email and logs in |
| POST   | `/sessionservice/logout`   | `sessionservice.logout`   | Logs out locally and redirects to the Session Service logout                |

### XHR / fetch requests (CORS)
A redirect to the Session Service can't be followed by XHR/fetch (CORS error). For such requests the middleware therefore does not redirect:

- **Inertia** (`X-Inertia`): `409` with `X-Inertia-Location` → Inertia navigates to the login automatically.
- **JSON** (`expectsJson()`): `401` with `login_url` (contains `?return=` from the `Referer`).

Handle the `401` in the frontend with a top-level navigation (no user interaction needed):
```js
const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'include' });
if (res.status === 401) {
    const { login_url } = await res.json();
    window.top.location.href = login_url;
}
```

Allowed `return` origins: `APP_URL` plus `config('cors.allowed_origins')`, or `sessionservice.allowed_return_origins` if set (wildcards like `https://*.wika.com` allowed). Other return URLs are ignored and `home` is used.

### Embedded components (e.g. wire-extender)
Embedded components are loaded via cross-origin `fetch` and inserted with `innerHTML`, so neither a server redirect nor a plain `<script>` tag works.
Instead, run the login redirect script in the host page, e.g. from a Livewire component (`$this->js()` is executed after the component is initialized):

```php
use Illuminate\Support\Facades\Auth;
use WikaGroup\SessionService\SessionService;

public function mount(SessionService $service): void
{
    if (Auth::guest()) {
        $this->js($service->getLoginRedirectScript());
    }
}
```

The host page navigates to `/sessionservice/login?return={host page URL}` and returns there after login (the host origin must be an allowed return origin, see above).
If a redirect was already triggered within the last 30 seconds (configurable via parameter), it is skipped to prevent redirect loops, e.g. if the session cookie is not sent with the embed request (`SESSION_SAME_SITE=none`, `SESSION_SECURE_COOKIE=true` and `cors.supports_credentials` are required).

### Logout button
```html
@auth
    <form method="POST" action="{{ route('sessionservice.logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
@endauth
```

### Events
The package dispatches Laravel events you can listen to (e.g. in `AppServiceProvider::boot()`):

| Event                                          | Properties                                       |
|------------------------------------------------|--------------------------------------------------|
| `WikaGroup\SessionService\Events\LoginSucceeded`  | `$user`, `$userData` (result of `exchangeCode`)  |
| `WikaGroup\SessionService\Events\LoginFailed`     | `$message`, `$exception`                         |
| `WikaGroup\SessionService\Events\LogoutSucceeded` | `$user`                                          |
| `WikaGroup\SessionService\Events\LogoutFailed`    | `$message`, `$user`, `$exception`                |

```php
use Illuminate\Support\Facades\Event;
use WikaGroup\SessionService\Events\LoginSucceeded;

Event::listen(function (LoginSucceeded $event): void {
    logger()->info('Login', ['email' => $event->userData['email']]);
});
```

After a successful login the user data is also stored in the session:
```php
$userData = session('sessionservice-user-data');
```

### Using the service directly
```php
use WikaGroup\SessionService\SessionService;

$service = app(SessionService::class);

$service->getLoginUrl();
$service->getLogoutUrl();
$service->getLoginRedirectScript(); // JS for embedded components, see above
$service->exchangeCode($code); // session_token, expires_at, user_id, email, first_name, last_name, display_name
```

The client credentials token for the server-to-server call is cached for 55 minutes (`session-service:cc-token`).
