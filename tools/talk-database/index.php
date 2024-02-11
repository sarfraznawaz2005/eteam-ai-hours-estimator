<?php require_once '../../setup.php'; ?>
<?php require_once '../../layout/head.php';?>

<div class="row justify-content-center">
    <div class="col-md-8">

        <form method="post" action="">
            <div class="mb-3">
                <label for="databaseTypeSelect" class="form-label">Database Type</label>
                <select class="form-select" id="databaseTypeSelect" name="databaseTypeSelect" required>
                    <option value="">Select Database Type</option>
                    <option value="MySQL">MySQL</option>
                    <option value="MariaDB">MariaDB</option>
                    <option value="Microsoft SQL">Microsoft SQL Server</option>
                    <option value="PostgreSQL">PostgreSQL</option>
                    <option value="SQLite">SQLite</option>
                    <option value="Oracle Database">Oracle Database</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="descriptionTextarea" class="form-label">Tables Description</label>
                <i class="bi bi-info-circle-fill text-primary" data-bs-toggle="modal" data-bs-target="#descriptionModal"
                    title="See Example">
                </i>
                <textarea class="form-control" id="descriptionTextarea" name="descriptionTextarea" rows="8"
                    required></textarea>
            </div>
            <div class="mb-3">
                <label for="queryInput" class="form-label">Your Query</label>
                <i class="bi bi-info-circle-fill text-primary" data-bs-toggle="modal" data-bs-target="#queryModal"
                    title="See Example">
                </i>
                <input type="text" class="form-control" id="queryInput" name="queryInput" required>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-database"></i> Get Results
                </button>
            </div>

            <input type="hidden" name="processor" value="process.php">
        </form>

    </div>

</div>

<div class="row justify-content-center">
    <?php require_once __DIR__ . '../../../layout/common.php'?>
</div>

<!-- Description Modal -->
<div class="modal fade" id="descriptionModal" tabindex="-1" aria-labelledby="descriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="descriptionModalLabel">Tables Example</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size:14px;">
                Products: id, name, discount, created_at
                <br>
                Orders: id, product_id, user_id, created_at
                <br>
                Users: id, name, email, password, created_at
            </div>
        </div>
    </div>
</div>


<!-- Description Modal -->
<div class="modal fade" id="queryModal" tabindex="-1" aria-labelledby="queryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="queryModalLabel">Query Examples</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size:14px;">
                You can ask questions like below for example:
                <br><br>
                <strong>List the products with highest discounts.</strong>
                <br><br>
                <strong>Which user has placed most orders ?</strong>
                <br><br>
                <strong>Create a view to get users who have placed most orders.</strong>
                <br><br>
                <strong>Create a procedure to get products that have orders placed against them.</strong>
                <br><br>
                <strong>Create a before insert trigger for products.</strong>
                <br><br>
                <strong>Give me SQL query to select users with most orders.</strong>
                <br><br>
                <strong>select * from users limit 10</strong>
            </div>
        </div>
    </div>
</div>


<?php require_once '../../layout/foot.php';?>