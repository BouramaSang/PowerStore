<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="apple-touch-icon" sizes="180x180" href="inapp-1.0.0/assets/images/favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="inapp-1.0.0/assets/images/favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="inapp-1.0.0/assets/images/favicon_io/favicon-16x16.png">
  <link rel="manifest" href="inapp-1.0.0/assets/images/favicon_io/site.webmanifest">
    <!-- Bootstrap CSS --> 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tabler Icons -->
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
  <!-- Custom CSS -->
 <link rel="stylesheet" href="inapp-1.0.0/assets/css/style.css">
</head>
<body>
    <!-- SIDEBAR -->
  <aside id="sidebar" class="sidebar">
    <div class="logo-area">
     <a href="index.php" class="d-inline-flex"><img src="/inapp-1.0.0/src/assets/images/logo-icon.svg" alt="" width="24">
        <span class="logo-text ms-2"> <img src="/inapp-1.0.0/src/assets/images/logo.svg" alt=""></span>
      </a>
    </div>
    <ul class="nav flex-column">
      <li class="px-4 py-2"><small class="nav-text">Main</small></li>
      <li><a class="nav-link active" href="/inapp-1.0.0/src/index.php"><i class="ti ti-home"></i><span
            class="nav-text">Dashboard</span></a></li>
        <li class="px-4 py-2"><small class="nav-text">Enregistrements</small></li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/Frontend/category/index_category.php"><i class="ti ti-category"></i><span
            class="nav-text"> Add Category</span></a></li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/create-product.php"><i class="ti ti-package"></i><span class="nav-text">Add
            Product</span></a></li>
    <li><a class="nav-link" href="/inapp-1.0.0/src/reports.php"><i class="ti ti-receipt"></i><span class="nav-text">Reports</span></a>
      </li>
    <li><a class="nav-link" href="/inapp-1.0.0/src/404-error.php"><i class="ti ti-alert-circle"></i><span class="nav-text">404 Error</span></a>
      </li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/docs.php"><i class="ti ti-file-text"></i><span class="nav-text">Docs</span></a></li>

      <li class="px-4 pt-4 pb-2"><small class="nav-text">Account</small></li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/signin.php"><i class="ti ti-logout"></i><span class="nav-text">Log in</span></a>
      </li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/signup.php"><i class="ti ti-user-plus"></i><span class="nav-text">Sign
            up</span></a></li>
    </ul>

    
  </aside>
</body>
  <!-- Bootstrap JS cdn -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <!-- Bootstrap JS fichier main -->
  <script src="/inapp-1.0.0/src/assets/js/main.js" type="module"></script>

</html>