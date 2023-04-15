When creating a Laravel project, you'll find that Laravel by default uses MVC folder structure. Obviously it does works fine, but for larger projects you get a bit lost with files that are tied by a module coming from all over the places.

The structure I'm aiming for looks like this:

```
app/Modules/{Module}/
├── {Module}.php (model)
├── {Module}Controller.php
├── {Module}Policy.php
├── {Module}Request.php
├── {Module}Routes.php
├── ...
└── Tests/
    ├── {Module}Factory.php
    ├── {Module}FeatureTest.php
    ├── {Module}Seeder.php
    └── {Module}UnitTest.php
```

For my use case, seeders are only usable in tests. I do like to keep it with as few subfolders as possible.

## Installing

Start by creating a new project:

```
composer create-project laravel/laravel laravel-modular
```

We'll be using SQLite for this. Create it:

```
touch database/database.sqlite
```

Then let's edit our `.env` (copy from `.env.example` if it didn't automatically), update these vars:

```
DB_CONNECTION=sqlite
DB_HOST=database/database.sqlite
```

Now let's create a namespace for our modules folder, edit your `composer.json`:

```diff
"autoload": {
  "psr-4": {
      "App\\": "app/",
+     "Modules\\": "app/Modules/",
      "Database\\Factories\\": "database/factories/",
      "Database\\Seeders\\": "database/seeders/"
  }
},
```

You can also put it outside the `app` folder if you'd like, just update the stuff we're doing here accordingly.

Whenever you update composer's autoload stuff, run this command:

```bash
composer dump-autoload
```

You can now run your app with:

```
php artisan serve
```

## Creating our module

Let us create a Car module:

```bash
mkdir -p app/Modules/Car/Tests
php artisan make:model --all Car
```

The files will be created at the default Laravel dir structure, move every file to the folder we created:

```bash
mv -t app/Modules/Car app/Models/Car.php app/Http/Controllers/CarController.php app/Http/Requests/StoreCarRequest.php app/Policies/CarPolicy.php
mv -t app/Modules/Car/Tests database/factories/CarFactory.php database/seeders/CarSeeder.php
```

For this tutorial I'll leave migration in the default dir, I like to visualize all migrations in chronological order, but you can also move to the module folder if you want.

We also only need a single FormRequest for both store and update:

```bash
rm app/Http/Requests/UpdateCarRequest.php
mv app/Modules/Car/StoreCarRequest.php app/Modules/Car/CarRequest.php
# also update class name
```

After that we have to update every namespace to our new folder, update all files in `app/Modules/Car` to:

```php
namespace Modules\Car;
```

And all files in `app/Modules/Car/Tests` to:

```php
namespace Modules\Car\Tests;
```

You have to do it for every new module. And if you're puting files inside subfolders you have to adequate those as well. Also fix any import errors you might have -- the first module is always the most time consuming.

Run `composer dump-autoload` to see if there's any file off from PSR-4.

## Setup routes

Let's create a modular route:

```bash
touch app/Modules/Car/CarRoutes.php
```

And update the file:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Car\CarController;

Route::resource('cars', CarController::class);
```

> ``Route::resource`` maps index/show/store/update/destroy methods in controller to GET/POST/PUT/DELETE endpoints, see more here: https://laravel.com/docs/10.x/controllers#actions-handled-by-resource-controller

For that to work we need a Service Provider, create one:

```bash
php artisan make:provider ModuleServiceProvider
```

And add a `boot` method to search routes in our modules (you can do the same thing for migrations if you'd like):

```php
public function boot(): void
{
    // Can also use (**) wildcard if you have subfolders
    foreach (glob(base_path('app/Modules/*')) ?: [] as $dir) {
        $modelClassName = class_basename($dir);
        $path = Str::before($dir, "\\$modelClassName");
        $moduleRouteFile = "$path/$modelClassName" . 'Routes.php';

        if (!file_exists($moduleRouteFile)) continue;

        $this->loadRoutesFrom($moduleRouteFile);
    }
}
```

And add it to the ``config/app.php`` under ``providers``:

```php
App\Providers\ModuleServiceProvider::class
```

You can now test your route! Should be working.

## Setup seeders and factories

Update the migration:

```php
Schema::create('cars', function (Blueprint $table) {
  $table->id();
  $table->string('name');
  $table->timestamps();
});
```

Update the seeder:

```php
public function run(): void
{
    Car::factory()
        ->count(10)
        ->create();
}
```

Register it to ``database/seeders/DatabaseSeeder.php``:

```php
public function run(): void
{
    // Note the leading slash
    $this->call([
        \Modules\Car\Tests\CarSeeder::class
    ]);
}
```

And the factory:

```php
protected $model = Car::class;

public function definition(): array
{
    return [
        'name' => fake()->name()
    ];
}
```

Note the ``protected $model``, since we're using a custom file structure, we have to declare it. We also need to add a ``register`` method to our Service Provider:

```php
public function register(): void
{
    Factory::guessFactoryNamesUsing(function (string $modelName) {
        $modelClassName = class_basename($modelName);
        $namespace = Str::before($modelName, "\\$modelClassName");
        return "$namespace\\$modelClassName\\Tests\\$modelClassName" . 'Factory';
    });
}
```

You can now run migration and seeder:

```bash
php artisan migrate:fresh --seed
```

## Testing

Last part, let's add some tests, run:

```bash
php artisan make:test CarFeatureTest
mv tests/Feature/CarFeatureTest.php app/Modules/Car/Tests/CarFeatureTest.php
```

> Don't forget to update namespaces!

Add a function to the test file:

```php
/** @test */
public function get_cars_should_return_success(): void
{
    $response = $this->get('/cars');
    $response->assertStatus(200);
}
```

> Note the ``/** @test */`` comment, PHPUnit won't find the test if you don't add this comment.

Now we have to update ``phpunit.xml`` to discorver our tests:

```diff
<testsuites>
    <testsuite name="Unit">
-       <directory suffix="Test.php">./tests/Unit</directory>
+       <directory suffix="UnitTest.php">./app/Modules</directory>
    </testsuite>
    <testsuite name="Feature">
-       <directory suffix="Test.php">./tests/Feature</directory>
+       <directory suffix="FeatureTest.php">./app/Modules</directory>
    </testsuite>
</testsuites>
```

You have to use ``UnitTest.php`` or ``FeatureTest.php`` suffix, or change ``phpunit.xml`` for your use case.

You can now test:

```bash
php artisan test
```

## Conclusion

As you can see, Laravel is very powerful and can handle very well custom structures, with little boilerplate IMO. Unfortunately the ``php artisan make:*`` commands won't work correctly within modules, but you can add new commands to make it work for you.

That's it for today, hopefully you learned something!
