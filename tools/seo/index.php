<?php require_once '../../layout/head.php';?>

<div class="row justify-content-center">
    <div class="col-md-8">

        <form method="post" action="">
            <div class="mb-3">
                <label for="websiteUrl" class="form-label">Website URL</label>
                <input type="url" id="websiteUrl" name="websiteUrl" class="form-control" placeholder="Enter Website URL"
                    value="https://eteamid.com" required>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Optimize Content
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