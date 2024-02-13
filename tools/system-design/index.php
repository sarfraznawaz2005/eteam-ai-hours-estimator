<?php require_once '../../setup.php'; ?>
<?php require_once '../../layout/head.php';?>

<div class="row justify-content-center">
    <div class="col-md-8">

        <div class="mb-4" style="font-size: 0.9rem;">
            <h5 class="mb-4">Helpful Tips</h5>
            <ul class="list-unstyled">
                <li class="mb-2">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    By providing project type, AI will be able to suggest better.
                </li>
                <li class="mb-2">
                    <i class="bi bi-heart-fill text-primary"></i>
                    By providing clear project description, AI will understand features better and build data model and other things.
                </li>
                <li class="mb-2">
                    <i class="bi bi-lightbulb-fill text-warning"></i>
                    With generated response, developers/designers must be consulted to make any improvements
                    or corrections.
                </li>
                <li>
                    <i class="bi bi-exclamation-circle-fill text-danger"></i>
                    Disclaimer: AI can make mistakes. Consider checking important information.
                </li>
            </ul>
        </div>

        <form method="post" action="">
            <div class="mb-3">
                <label for="projectTypeSelect" class="form-label">Project Type</label>
                <select class="form-select" id="projectTypeSelect" name="projectTypeSelect" required>
                    <option value="">SELECT</option>
                    <?php foreach (CONFIG['project_types'] as $projectType): ?>
                        <option value="<?= $projectType ?>"><?= $projectType ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="descriptionTextarea" class="form-label">Project Description</label>
                <i class="bi bi-info-circle-fill text-primary" data-bs-toggle="modal" data-bs-target="#descriptionModal"
                    title="See Example">
                </i>
                <textarea class="form-control" id="descriptionTextarea" name="descriptionTextarea" rows="8"
                    required></textarea>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-tools"></i> Get Project Plan
                </button>
            </div>

            <input type="hidden" name="processor" value="process.php">
        </form>

    </div>

</div>

<div class="row justify-content-center">
    <?php require_once __DIR__ . '../../../layout/common.php'?>
</div>

<!-- Project Description Modal -->
<div class="modal fade" id="descriptionModal" tabindex="-1" aria-labelledby="descriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="descriptionModalLabel">Project Description Example</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size:14px;">
                Welcome to OpinSync - the innovative QR-based feedback collection web portal. Designed for
                businesses of all sizes,
                OpinSync allows companies to effortlessly gather valuable insights from their customers or clients.
                The process is
                simple: businesses can sign in, create customized feedback forms, and generate unique QR codes for
                each form.
                These QR codes can be printed and strategically placed in locations where feedback is desired,
                enabling a seamless
                and efficient data collection process.<br><br>
                Consider a scenario where a fitness enthusiast runs multiple gyms.
                With OpinSync, they can create tailored feedback forms for specific areas such as changing rooms,
                workout floors,
                and cafes across all their branches. This targeted approach ensures that businesses receive feedback
                on the aspects
                that matter most to them.The collected feedback is conveniently displayed on a dedicated dashboard,
                providing
                businesses with a comprehensive overview of customer sentiments. Additionally, OpinSync offers the
                flexibility to
                send standard email or SMS notifications in response to each feedback received, allowing businesses
                to engage with
                their customers and address concerns promptly.
                <br><br>
                Features:<br>
                1. Customizable Forms: Tailor feedback forms to suit the unique needs of your business or choose
                from a variety of templates.
                <br><br>
                2. QR Code Generation: Generate QR codes for each feedback form, making it easy to collect responses
                from specific locations.
                <br><br>
                3. Multi-Location Support: Ideal for businesses with multiple branches or locations, allowing them
                to gather feedback on a granular level.
                <br><br>
                4. Centralized Dashboard: View and analyze feedback in one centralized dashboard for a holistic
                understanding of customer sentiments.
                <br><br>
                5. Communication Tools: Respond to feedback efficiently by sending standard email or SMS
                notifications directly from the platform.
                <br><br>
                6. User-Friendly Interface: Intuitive design and easy navigation for a seamless user experience.
                <br><br>
                7. Data Security: Prioritize the security of customer data with robust encryption and privacy
                measures.
            </div>
        </div>
    </div>
</div>


<?php require_once '../../layout/foot.php';?>