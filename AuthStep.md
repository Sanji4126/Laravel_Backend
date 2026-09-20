 # Laravel Sanctum Bearer Authentication

This project uses Laravel Sanctum personal access tokens. The client sends the token in this header:

```http
Authorization: Bearer YOUR_TOKEN_HERE
```

## Step 1: Check the prerequisites

These parts are already present in this project:

- `laravel/sanctum` is installed.
- The `personal_access_tokens` migration exists.
- `User` uses the `HasApiTokens` trait.
- `User` has the `password` cast set to `hashed`.

Run the migration if needed:

```bash
php artisan migrate
```

Because the `User` model hashes the `password` automatically, pass the plain password to `User::create()`. Do not call `Hash::make()` during registration or the password may be hashed twice.

## Step 2: Implement `AuthController.php`

Open `app/Http/Controllers/Users/AuthController.php` and replace its contents with:

```php
<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
		public function register(Request $request)
		{
				$data = $request->validate([
						'user_name' => ['required', 'string', 'max:255'],
						'email' => ['required', 'email', 'max:255', 'unique:users,email'],
						'password' => ['required', 'string', 'min:8', 'confirmed'],
				]);

				$user = User::create([
						'user_name' => $data['user_name'],
						'email' => $data['email'],
						'password' => $data['password'],
						'role' => 'user',
				]);

				return $this->tokenResponse($user, 'Registration successful', 201);
		}

		public function login(Request $request)
		{
				$data = $request->validate([
						'email' => ['required', 'email'],
						'password' => ['required', 'string'],
				]);

				$user = User::where('email', $data['email'])->first();

				if (!$user || !Hash::check($data['password'], $user->password)) {
						return response()->json(['message' => 'Invalid credentials'], 401);
				}

				return $this->tokenResponse($user, 'Login successful');
		}

		public function logout(Request $request)
		{
				$request->user()->currentAccessToken()?->delete();

				return response()->json(['message' => 'Logout successful']);
		}

		public function profile(Request $request)
		{
				return response()->json($request->user());
		}

		private function tokenResponse(User $user, string $message, int $status = 200)
		{
				$token = $user->createToken('auth_token')->plainTextToken;

				return response()->json([
						'message' => $message,
						'token' => $token,
						'token_type' => 'Bearer',
						'user' => $user,
				], $status);
		}
}
```

The public registration method always creates a normal `user`. Do not allow anyone to send `role: admin` during public registration.

## Step 3: Add API routes

Open `routes/api.php` and add:

```php
<?php

use App\Http\Controllers\Users\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
		Route::post('/logout', [AuthController::class, 'logout']);
		Route::get('/user', [AuthController::class, 'profile']);
});
```

The routes are automatically prefixed with `/api`, so the final URLs are:

| Method | URL | Authentication |
| --- | --- | --- |
| POST | `/api/register` | None |
| POST | `/api/login` | None |
| GET | `/api/user` | Bearer token required |
| POST | `/api/logout` | Bearer token required |

## Step 4: Register a user

Send a `POST` request to `/api/register` with JSON:

```json
{
	"user_name": "Test User",
	"email": "test@example.com",
	"password": "password123",
	"password_confirmation": "password123"
}
```

The response contains a token. Store the value of `token` securely. The token is shown only when it is created.

Using curl:

```bash
curl -X POST http://127.0.0.1:8000/api/register \
	-H "Accept: application/json" \
	-H "Content-Type: application/json" \
	-d '{"user_name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'
```

## Step 5: Log in

Send a `POST` request to `/api/login`:

```json
{
	"email": "test@example.com",
	"password": "password123"
}
```

Using curl:

```bash
curl -X POST http://127.0.0.1:8000/api/login \
	-H "Accept: application/json" \
	-H "Content-Type: application/json" \
	-d '{"email":"test@example.com","password":"password123"}'
```

Copy the `token` from the response.

## Step 6: Call a protected endpoint

Send the token as a Bearer token. Do not send only the token; include the word `Bearer` followed by a space.

```bash
curl http://127.0.0.1:8000/api/user \
	-H "Accept: application/json" \
	-H "Authorization: Bearer YOUR_TOKEN_HERE"
```

Without a valid token, Sanctum returns HTTP `401 Unauthorized`.

## Step 7: Log out

Logout deletes the current token:

```bash
curl -X POST http://127.0.0.1:8000/api/logout \
	-H "Accept: application/json" \
	-H "Authorization: Bearer YOUR_TOKEN_HERE"
```

After logout, the deleted token cannot access protected endpoints.

## Step 8: Verify the setup

Run these commands from the project root:

```bash
php artisan route:list
php artisan migrate:status
php artisan test
```

Start the API locally with:

```bash
php artisan serve
```

## Common problems

- `401 Unauthorized`: the token is missing, expired/deleted, or the header is not exactly `Authorization: Bearer TOKEN`.
- `422 Unprocessable Entity`: the request validation failed; inspect the JSON response for the invalid fields.
- `419 Page Expired`: use the API routes in `routes/api.php`, not web routes that require a CSRF token.
- Password login always fails: check that registration passes the plain password to `User::create()` because the model already uses the `hashed` cast.
