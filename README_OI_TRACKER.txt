OI Tracker (CodeIgniter 4) - Quick Use

1) Copy the 'app/...' folders from this ZIP into your CI4 project root.
   They will merge into:
      app/Database/Migrations/2025-08-19-000000_CreateOiSnapshots.php
      app/Models/OiSnapshotModel.php
      app/Commands/FetchOi.php
      app/Commands/AnalyzeOi.php
      app/Controllers/OiController.php
      app/Views/oi_dashboard.php

2) Add routes in app/Config/Routes.php:
      $routes->get('oi', 'OiController::index');
      $routes->get('oi/json', 'OiController::json');

3) Run migration:
      php spark migrate

4) Fetch data (manual test):
      php spark oi:fetch NIFTY 5

5) Analyze:
      php spark oi:analyze NIFTY 10

6) Web UI:
      http://<host>/oi
