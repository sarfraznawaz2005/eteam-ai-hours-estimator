<?php require_once './utility/func.php';?>
<?php require_once './layout/head.php'; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="jumbotron text-center">
            <img src="assets/eteam.png" alt="eTteam Logo">
            <h2 class="display-4">eTeam AI Tools</h2>
            <p class="lead">Please click a tool below to open it.</p>
            <hr class="my-4">
            <p class="lead">
                <a class="btn btn-success btn-lg" href="./tools/estimator/index.php" role="button" target="_blank">
                    <i class="bi bi-calculator-fill"></i> Project Estimator
                </a>

                <a class="btn btn-warning btn-lg" href="./tools/idea-generator/index.php" role="button" target="_blank">
                    <i class="bi bi bi-lightbulb-fill"></i> Idea Generator
                </a>
            </p>
        </div>
    </div>
</div>

<?php require_once './layout/foot.php'; ?>