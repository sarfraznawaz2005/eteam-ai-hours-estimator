<?php require_once '../../layout/head.php';?>

<div class="row justify-content-center">
    <div class="col-md-8">

        <form method="post" action="">
            <div class="mb-3">
                <label for="descriptionTextarea" class="form-label">Ask Me Anything</label>
                <textarea class="form-control" id="descriptionTextarea" name="descriptionTextarea" rows="8"
                    required></textarea>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-question-circle-fill"></i> Get Answer
                </button>
            </div>

            <input type="hidden" name="processor" value="process.php">
        </form>

    </div>

</div>

<div class="row justify-content-center">
    <?php require_once __DIR__ . '../../../layout/common.php'?>
</div>

<?php require_once '../../layout/foot.php';?>