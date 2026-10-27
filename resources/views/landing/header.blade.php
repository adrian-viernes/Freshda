<!-- ================= HEADER ================= -->
<header id="header">

    <a href="#home" class="logo">
        <img src="{{ asset('images/Freshda Logo.png') }}" alt="Fresh-DA" class="logo-img">

        <div class="logo-text">
            Fresh-<span>DA</span>
        </div>
    </a>


    <input type="checkbox" id="check">

    <label for="check" class="checkbtn">
        <i class="fa-solid fa-bars"></i>
    </label>


    <nav>
        <ul>

            <li>
                <a href="#home" class="nav-link active">
                    <i class="fa-solid fa-house"></i>
                    Home
                </a>
            </li>

            <li>
                <a href="#features" class="nav-link">
                    <i class="fa-solid fa-table-cells-large"></i>
                    Features
                </a>
            </li>

            <li>
                <a href="#about" class="nav-link">
                    <i class="fa-solid fa-circle-info"></i>
                    About
                </a>
            </li>

            <li>
                <a href="{{ route('scan.public') }}" class="nav-link">
                    <i class="fa-solid fa-camera"></i>
                    Fish Scanner
                </a>
            </li>

            <li>
                <a href="{{ route('login') }}" class="login-btn">
                    Login
                </a>
            </li>

        </ul>
    </nav>

</header>
