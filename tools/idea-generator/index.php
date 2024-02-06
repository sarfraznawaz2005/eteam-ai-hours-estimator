<?php require_once '../../layout/head.php';?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="jumbotron text-center">
            <h2 class="display-4" style="display: inline;">Idea Generator</h2>
            <p class="lead">Please click a button below on how you want to generate a project idea.</p>
            <hr class="my-4">
            <p class="lead">
            <div class="btn-group" role="group">
                <a class="btn btn-success rounded" role="button" data-bs-toggle="modal" data-bs-target="#ideaModal">
                    <i class="bi bi-person-fill"></i> Generate My Idea
                </a>

                <form action="" method="post">
                    <button type="submit" class="btn btn-primary" style="margin: 0 1rem;">
                        <i class="bi bi-magic"></i> Generate Random Idea
                    </button>
                </form>

                <a class="btn btn-warning rounded" role="button" data-bs-toggle="modal" data-bs-target="#nicheModal">
                    <i class="bi bi-briefcase-fill"></i> Generate Niche Ideas
                </a>

            </div>
        </div>
    </div>

</div>

<div class="row justify-content-center">
    <?php require_once __DIR__ . '../../../layout/common.php'?>
</div>

<div class="modal fade" id="ideaModal" tabindex="-1" aria-labelledby="ideaModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body" style="font-size:14px;">
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="ideaInput" class="form-label">Please Input Your Idea</label>
                        <input type="text" class="form-control" id="ideaInput" name="ideaInput" required
                            placeholder="One or more words or short description.">
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-down"></i> Generate Idea
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="nicheModal" tabindex="-1" aria-labelledby="nicheModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body" style="font-size:14px;">
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="niche" class="form-label">Please Input Niche Keyword(s)</label>
                        <input type="text" class="form-control" id="niche" name="niche" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-down"></i> Generate Idea
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../layout/foot.php';?>