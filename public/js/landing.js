document.addEventListener("DOMContentLoaded", function () {

    /* =========================
       MOBILE NAVIGATION
    ========================= */

    const menuToggle = document.getElementById("check");
    const navLinks = document.querySelectorAll("nav a");


    // Automatically close the mobile menu
    // after clicking a navigation link
    navLinks.forEach(link => {
        link.addEventListener("click", function () {
            if (window.innerWidth <= 768) {
                menuToggle.checked = false;
            }
        });
    });


    /* =========================
       SMOOTH SCROLL
    ========================= */

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {

        anchor.addEventListener("click", function (e) {

            const targetId = this.getAttribute("href");

            if (targetId === "#") return;

            const targetElement = document.querySelector(targetId);

            if (targetElement) {

                e.preventDefault();

                targetElement.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            }

        });

    });


    /* =========================
       SCROLL EFFECTS (active nav link + header shrink)
    ========================= */

    const sections = document.querySelectorAll(
        "#features, #how-it-works, #about"
    );
    const header = document.querySelector("header");

    window.addEventListener("scroll", function () {

        /* Header shrinks once the page is scrolled down a bit */
        header.classList.toggle("header-scrolled", window.scrollY > 50);

        /* Highlight the nav link of the section currently on screen */
        let currentSection = "";

        sections.forEach(section => {

            const sectionTop = section.offsetTop - 150;
            const sectionHeight = section.offsetHeight;

            if (
                window.scrollY >= sectionTop &&
                window.scrollY < sectionTop + sectionHeight
            ) {
                currentSection = section.getAttribute("id");
            }

        });

        navLinks.forEach(link => {

            link.classList.remove("active");

            const href = link.getAttribute("href");

            if (href === "#" + currentSection) {
                link.classList.add("active");
            }

        });

    });


    /* =========================
       FADE-IN ANIMATION
    ========================= */

    const animatedElements = document.querySelectorAll(
        ".feature-item, .product-card, .step-card"
    );

    const observer = new IntersectionObserver(
        function (entries) {

            entries.forEach(entry => {

                if (entry.isIntersecting) {

                    entry.target.classList.add("show");

                }

            });

        },
        {
            threshold: 0.15
        }
    );

    animatedElements.forEach(element => {

        element.classList.add("fade-in");

        observer.observe(element);

    });


    /* The landing page has no dark/light switch: the layout locks it to the
       light theme, so nothing here reads or writes the shared theme keys. */

});
