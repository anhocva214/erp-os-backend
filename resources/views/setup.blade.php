<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backend Setup</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .setup-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .setup-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .setup-header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333333;
        }

        .setup-header p {
            color: #6c757d;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .form-label {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="setup-container">
            <div class="setup-header">
                <h1>Project Setup</h1>
                <p>Fill in the required details to set up your backend application.</p>
                <div class="alert alert-warning" role="alert">
                    Create Database Before Setup and Provide Database Details
                  </div>
            </div>
            @isset($error)

            <div class="alert alert-danger" role="alert">
                Database Connection Failed. Please check your database details and try again.
            </div>

            <div class="alert alert-danger" role="alert">
                {{ $error }}
            </div>
            @endisset
            <form method="POST" action="/install">
                @csrf
                <div class="mb-3">
                    <label for="db_host" class="form-label">Database Host</label>
                    <input type="text" class="form-control" id="db_host" name="db_host" placeholder="e.g., 127.0.0.1" required value="localhost">
                </div>
                <div class="mb-3">
                    <label for="db_port" class="form-label">Database Port</label>
                    <input type="number" class="form-control" id="db_port" name="db_port" placeholder="e.g., 3306" required value="3306">
                </div>
                <div class="mb-3">
                    <label for="db_database" class="form-label">Database Name</label>
                    <input type="text" class="form-control" id="db_database" name="db_database" placeholder="e.g., laravel_db" required>
                </div>
                <div class="mb-3">
                    <label for="db_username" class="form-label">Database Username</label>
                    <input type="text" class="form-control" id="db_username" name="db_username" placeholder="e.g., root" required>
                </div>
                <div class="mb-3">
                    <label for="db_password" class="form-label">Database Password</label>
                    <input type="password" class="form-control" id="db_password" name="db_password" placeholder="Leave blank if none">
                </div>
                <button type="submit" class="btn btn-primary w-100">Complete Setup</button>
            </form>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
