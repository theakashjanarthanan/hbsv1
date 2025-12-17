<?php
// Simple sticky footer for the site
?>
<style>
    .site-footer {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        background: #0d6efd;
        color: #ffffff;
        text-align: center;
        padding: 10px 12px;
        font-family: "Segoe UI", Arial, sans-serif;
        font-size: 16px;
        font-weight: 600;
        z-index: 1500;
        border-top: 1px solid rgba(255,255,255,.2);
    }

    @media (max-width: 480px) {
        .site-footer { font-size: 12px; padding: 8px 10px; }
    }
</style>

<footer class="site-footer" role="contentinfo" aria-label="Site Footer">
    <span>Developed By Department of Computer Science</span> <br> <span>&copy; 2025 Pondicherry University</span> 
    <span class="visually-hidden">All rights reserved.</span>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Get all the dropdown buttons
        var dropdownBtns = document.querySelectorAll(".dropdown-btn");

        // Loop through the buttons to add event listeners
        dropdownBtns.forEach(function(btn) {
            btn.addEventListener("click", function() {
                // Toggle between showing and hiding the active dropdown container
                this.classList.toggle("collapsed");
                var dropdownContainer = this.nextElementSibling;
                if (dropdownContainer.style.display === "block") {
                    dropdownContainer.style.display = "none";
                } else {
                    dropdownContainer.style.display = "block";
                }
            });
        });
    </script>