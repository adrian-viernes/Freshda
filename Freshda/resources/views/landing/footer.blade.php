<!-- ================= FOOTER ================= -->
<footer class="main-footer">

    <div class="footer-container">


        <div class="footer-col">

            <div class="footer-logo">
                <img src="{{ asset('images/Freshda Logo.png') }}" alt="Fresh-DA" class="footer-logo-img">
                Fresh<span>-DA</span>
            </div>

            <p>
                A machine learning-based progressive web app
                for fish freshness and species recognition.
            </p>

            <div class="footer-tech">
                <span><i class="fa-solid fa-brain"></i> AI</span>
                <span><i class="fa-solid fa-eye"></i> Computer Vision</span>
                <span><i class="fa-solid fa-microchip"></i> Smart Recognition</span>
            </div>

        </div>


        <div class="footer-col">

            <h4>Quick Links</h4>

            <ul>
                <li>
                    <a href="#home">
                        <i class="fa-solid fa-chevron-right"></i>
                        Home
                    </a>
                </li>

                <li>
                    <a href="#features">
                        <i class="fa-solid fa-chevron-right"></i>
                        Features
                    </a>
                </li>

                <li>
                    <a href="{{ route('scan.public') }}">
                        <i class="fa-solid fa-chevron-right"></i>
                        Fish Scanner
                    </a>
                </li>

                <li>
                    <a href="#about">
                        <i class="fa-solid fa-chevron-right"></i>
                        About
                    </a>
                </li>

            </ul>

        </div>


        <div class="footer-col">

            <h4>System Features</h4>

            <ul>
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    AI Fish Recognition
                </li>

                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    Freshness Analysis
                </li>

                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    Batch Sampling
                </li>

                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    Scan History
                </li>

            </ul>

        </div>


        <div class="footer-col">

            <h4>Get Started</h4>

            <p>
                Experience smarter fish quality evaluation
                with artificial intelligence.
            </p>

            <a href="{{ route('scan.public') }}" class="footer-btn">
                Open the Fish Scanner
                <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>

    </div>

</footer>


<div class="footer-bottom">

    <p>
        <i class="fa-regular fa-copyright"></i>
        2026 Fresh-DA. All rights reserved.
    </p>

    <p class="footer-system">
        Smart technology for smarter fish quality evaluation
    </p>

</div>
