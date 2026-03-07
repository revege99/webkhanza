<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Klinik St. Lucia</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- fontawesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

   <link rel="stylesheet" href="/stmartina/views/css/sidebar.css">

    <!-- font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    
  </head>
  <body>

<div class="sidebar">
      <div class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion">
        <div class=" row justify-content-center align-items-center text-center" id="title">
          <h2>St. Lucia</h2>
        </div>

            <div class="dropdown-divider">
                <div class="dropdown">
                    <a class="btn dropdown-toggle-icon" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-house-fill" id="icon"></i> BPJS <i class="bi bi-chevron-right chevron-icon"></i>
                    </a>

                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?page=cppt_null">CPPT NULL</a></li>
                        <li><a class="dropdown-item" href="?page=report">Report Task</a></li>
                        <li><a class="dropdown-item" href="?page=task">TASK BPJS</a></li>
                        <li><a class="dropdown-item" href="?page=task_1">TASK BPJS 1</a></li>
                    </ul>
                </div>

                <div class="dropdown">
                    <a class="btn dropdown-toggle-icon" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-tape" id="icon"></i> Bridging <i class="bi bi-chevron-right chevron-icon"></i>
                    </a>

                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?page=post_obat">add obat</a></li>
                        <li><a class="dropdown-item" href="?page=get_obat_bynokunjungan">Data Obat Pcare</a></li>
                        <li><a class="dropdown-item" href="?page=data_obat_pcare_lokal">Data Obat Pcare Lokal</a></li>
                        <li><a class="dropdown-item" href="?page=post_mcu">add MCU</a></li>
                        <li><a class="dropdown-item" href="?page=data_kunjungan">Data Pcare Kunjungan</a></li>
                        <li><a class="dropdown-item" href="?page=data_mcu">Data Pcare MCU</a></li>
                        <li><a class="dropdown-item" href="?page=data_obat">Data Pcare Obat</a></li>
                        <li><a class="dropdown-item" href="?page=put_rujukan">Ubah Rujukan</a></li>
                    </ul>
                </div>


                <div class="dropdown">
                    <a class="btn dropdown-toggle-icon" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-car-side" id="icon"></i> Service <i class="bi bi-chevron-right chevron-icon"></i>
                    </a>

                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?page=antrean">Antrean</a></li>
                        <li><a class="dropdown-item" href="?page=kirim_antrean">Antrean Service</a></li>
                        
                    </ul>
                </div>

                <div class="menu-item">
                    <a class="btn" href="?page=logout">
                        <i class="fa-solid fa-right-from-bracket" id="icon"></i> Logout
                    </a>
                </div>


            </div>
        </div>
      </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>


   <!-- <script>
   
    let dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dropdown => {
        let toggleButton = dropdown.querySelector('.dropdown-toggle-icon');
        let chevronIcon = dropdown.querySelector('.chevron-icon');

        // Tambahkan event listener untuk toggle dropdown
        toggleButton.addEventListener('click', function () {
            setTimeout(() => {
                if (toggleButton.getAttribute("aria-expanded") === "true") {
                    chevronIcon.classList.remove("bi-chevron-right");
                    chevronIcon.classList.add("bi-chevron-down");
                } else {
                    chevronIcon.classList.remove("bi-chevron-down");
                    chevronIcon.classList.add("bi-chevron-right");
                }
            }, 50);
        });
    });
</script> -->

<script>
document.querySelectorAll('.dropdown').forEach(dropdown => {
    dropdown.addEventListener('show.bs.dropdown', function () {
        this.querySelector('.chevron-icon').style.transform = 'rotate(90deg)';
    });

    dropdown.addEventListener('hide.bs.dropdown', function () {
        this.querySelector('.chevron-icon').style.transform = 'rotate(0deg)';
    });
});
</script>
  </body>
</html>