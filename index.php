<?php require_once './setup.php';?>
<?php require_once './layout/head.php';?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="jumbotron text-center">
            <img src="assets/eteam.png" alt="eTteam Logo" style="display: block; margin: 0 auto;">
            <h2 class="display-4" style="display: inline;">eTeam AI Tools</h2>
            <a href="#" data-bs-toggle="modal" data-bs-target="#whyModal"><sup>(Why)</sup></a>
            <p class="lead">Please click a tool below to open it.</p>
            <hr class="colorful">
            <p class="lead">
                <a class="btn btn-warning" href="./tools/idea-generator/index.php" role="button">
                    <i class="bi bi bi-lightbulb-fill"></i> Idea Generator
                </a>
                <a class="btn btn-success" href="./tools/estimator/index.php" role="button">
                    <i class="bi bi-calculator-fill"></i> Estimator
                </a>
                <a class="btn btn-primary" href="./tools/system-design/index.php" role="button">
                    <i class="bi bi-tools"></i> Project Planner
                </a>
                <a class="btn btn-info" href="./tools/talk-database/index.php" role="button">
                    <i class="bi bi-database"></i> DB Talk
                </a>
            </p>
            <p class="lead">
                <a class="btn btn-danger" href="./tools/general-query/index.php" role="button">
                    <i class="bi bi-question-circle-fill"></i> General Help
                </a>
                <a class="btn btn-secondary" href="./tools/seo/index.php" role="button">
                    <i class="bi bi-search"></i> SEO Optimizer
                </a>
            </p>
        </div>
    </div>
</div>

<div class="modal fade" id="whyModal" tabindex="-1" aria-labelledby="whyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body" style="font-size:14px;">
                <?=Parsedown::instance()->text("* **Follows Company Guidelines:** Unlike ChatGPT, the output can be customized to tailor company's needs.");?>
                <?=Parsedown::instance()->text("* **Easily Available:** Provide seamless access to AI tools for all authorized users, fostering collaboration and streamlining workflows.");?>
                <?=Parsedown::instance()->text("* **No Complex AI Prompting:** No need to learn complex AI prompt-engineering to get desired output.");?>
                <?=Parsedown::instance()->text("* **Add More Tools As Needed:** Scale and expand AI capabilities over time, integrating new tools and services to meet evolving business requirements.");?>
                <?=Parsedown::instance()->text("* **Increased Efficiency & Productivity:** Team can eliminate the need for manual and repetitive tasks, freeing up employee's time to focus on more strategic and creative projects.");?>
                <?=Parsedown::instance()->text("* **Enhanced Decision-Making:** AI-powered tools can provide data-driven insights, allowing company to make informed and timely decisions.");?>
                <?=Parsedown::instance()->text("* **Foster Innovation:** AI tools can help the company explore new opportunities, identify trends, and develop innovative products and services.");?>
                <?=Parsedown::instance()->text("* **Competitive Advantage:** By leveraging AI, the company can gain a competitive edge by responding quickly to market changes, optimizing operations, and developing differentiated products and services.");?>
                <?=Parsedown::instance()->text("* **Improved Customer Service:** AI-powered chatbots and virtual assistants can provide 24/7 customer support, answering questions, resolving issues, and scheduling appointments.");?>
                <?=Parsedown::instance()->text("* **Real-Time Insights:** AI tools can monitor data in real time, providing up-to-date insights that can help businesses make informed decisions and respond to changing market conditions.");?>
            </div>
        </div>
    </div>
</div>

<?php require_once './layout/foot.php';?>

<!-- Chatbot Embed Code -->
<script src="https://ai.eteamprojects.com/chatbot/chatbot-embed.js"></script>

