<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class SetupController extends Controller

{
    public function index()
    {
        if (config('app.app_setup') === 'true' || config('app.app_setup') === true) {
            return redirect('/');
        }
        return view('setup');
    }

    public function setup(Request $request)
    {
        try{
        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required|numeric',
            'db_database' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',
        ]);

        Config::set('database.connections.mysql', [
            'driver'    => 'mysql',
            'host'      => $request->db_host ?? '127.0.0.1',
            'port'      => $request->db_port ?? '3306',
            'database'  => $request->db_database,
            'username'  => $request->db_username,
            'password'  => $request->db_password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ]);

        // Set the default database connection to 'tenant'
        Config::set('database.default', 'mysql');

        // Reconnect to apply the new connection settings
        DB::purge('mysql');
        DB::reconnect('mysql');
        // Migrate and seed the database
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true, 
        ]);

        // Update APP_SETUP to true
        $this->updateEnv([
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password,
        ]);
        $this->updateEnv(['APP_SETUP' => 'true']);

        return redirect('/')->with('success', 'Setup completed successfully!');
    } catch (\Exception $e) {
        return view('setup', ['error' => $e->getMessage()]);
        }
    }

    private function updateEnv($data)
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^$key=.*$/m";
            $replacement = "$key=" . (strpos($value, ' ') !== false ? '"' . $value . '"' : $value);
            $envContent = preg_match($pattern, $envContent) ?
                preg_replace($pattern, $replacement, $envContent) :
                $envContent . PHP_EOL . "$key=$value";
        }

        File::put($envPath, $envContent);
    }
}
