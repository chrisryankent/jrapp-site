<?php
session_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = "";
$borrower = [];
$location = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_borrower'])) {
    $nid = mysqli_real_escape_string($conn, $_POST['nid']);
    $queryBorrower = "SELECT * FROM tbl_borrower WHERE nid = '$nid' LIMIT 1";
    $resultBorrower = mysqli_query($conn, $queryBorrower);
    if ($resultBorrower && mysqli_num_rows($resultBorrower) > 0) {
        $borrower = mysqli_fetch_assoc($resultBorrower);

        // Get latest location for this borrower
        $locQuery = "SELECT * FROM tbl_borrower_location WHERE borrower_id = '".$borrower['id']."' ORDER BY location_time DESC LIMIT 1";
        $locResult = mysqli_query($conn, $locQuery);
        if ($locResult && mysqli_num_rows($locResult) > 0) {
            $location = mysqli_fetch_assoc($locResult);
        } else {
            $msg = "<div class='alert alert-warning alert-dismissible fade show mt-3' role='alert'>
                        No location found for this borrower.
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                          <span aria-hidden='true'>&times;</span>
                        </button>
                    </div>";
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                    Borrower not found.
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                      <span aria-hidden='true'>&times;</span>
                    </button>
                </div>";
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h2 class="mt-3 mb-4 font-weight-bold">Locate Borrower</h2>
        <?php if (!empty($msg)): ?>
            <div class="row"><div class="col-md-8 offset-md-2"><?php echo $msg; ?></div></div>
        <?php endif; ?>

        <!-- Borrower Search Form -->
        <form method="POST" action="">
            <div class="form-group row">
                <label for="nid" class="col-sm-3 col-form-label">Borrower NID:</label>
                <div class="col-sm-6">
                    <input type="text" name="nid" id="nid" class="form-control" placeholder="Enter Borrower NID" required>
                </div>
                <div class="col-sm-3">
                    <input type="submit" name="search_borrower" class="btn btn-primary" value="Locate Borrower">
                </div>
            </div>
        </form>

        <?php if (!empty($borrower)): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">Borrower Details</div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($borrower['name']); ?></p>
                    <p><strong>NID:</strong> <?php echo htmlspecialchars($borrower['nid']); ?></p>
                    <p><strong>Mobile:</strong> <?php echo htmlspecialchars($borrower['mobile']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($location)): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">Latest Location</div>
                <div class="card-body">
                    <p><strong>Latitude:</strong> <?php echo htmlspecialchars($location['latitude']); ?></p>
                    <p><strong>Longitude:</strong> <?php echo htmlspecialchars($location['longitude']); ?></p>
                    <p><strong>Time:</strong> <?php echo htmlspecialchars($location['location_time']); ?></p>
                    <!-- Mini Map Section -->
                    <div id="map" style="height:300px; width:100%;"></div>
                    <p class="mt-2"><strong>Location Name:</strong> 
                        <span id="locationName">Loading...</span>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($location)): ?>
<!-- Leaflet Map & Nominatim Reverse Geocoding -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    var lat = <?php echo floatval($location['latitude']); ?>;
    var lng = <?php echo floatval($location['longitude']); ?>;
    var map = L.map('map').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);
    var marker = L.marker([lat, lng]).addTo(map);

    // Nominatim reverse geocoding
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('locationName').textContent = data.display_name || 'N/A';
        })
        .catch(() => {
            document.getElementById('locationName').textContent = 'N/A';
        });
</script>
<?php endif; ?>

<?php
include_once "inc/footer.php";
?>