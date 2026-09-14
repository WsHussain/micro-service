<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap\Database;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

Database::boot();

$schema = Capsule::schema();

if (!$schema->hasTable('users')) {
    $schema->create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('username');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });
    echo "Created users table\n";
}

if (!$schema->hasTable('messages')) {
    $schema->create('messages', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->text('content');
        $table->timestamps();
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
    echo "Created messages table\n";
}

echo "Migration complete.\n";
