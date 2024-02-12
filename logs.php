<?php require_once './layout/head.php';?>

<link href="/assets/css/jquery.dataTables.css" rel="stylesheet">
<script src="/assets/js/jquery.dataTables.js"></script>

<div class="row justify-content-center mt-5">
    <table id="logTable" class="table" style="width:100%">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $logFile = __DIR__ . DIRECTORY_SEPARATOR . 'application.log';
                
                if (file_exists($logFile)) {
                    $fileHandle = fopen($logFile, 'r');

                    if ($fileHandle) {
                        while (($line = fgets($fileHandle)) !== false) {
                            preg_match('/^\[(.*?)\] \[(.*?)\] (.*)$/', $line, $matches);

                            if (count($matches) == 4) {
                                echo "<tr><td>{$matches[1]}</td><td>{$matches[2]}</td><td>{$matches[3]}</td></tr>";
                            }
                        }
                        
                        fclose($fileHandle);
                    }
                }
            ?>
        </tbody>
    </table>
</div>

<?php require_once './layout/foot.php';?>

<script>
$(document).ready(function() {
    $('#logTable').DataTable({
        "order": [
            [0, 'desc']
        ]
    });
});
</script>