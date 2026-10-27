/* ================= STATE ================= */
var state = {
  darkMode: false,
  authView: "login",
};

var pageDarkToggle = document.getElementById("pageDarkToggle");

/* ================= DARK MODE =================
   The layout applies the saved theme before the first paint, so the body class
   is the source of truth here. Both storage keys are kept in step so the
   choice follows the user between the landing, login and dashboard pages. */
function persistDarkPreference(on) {
  try {
    localStorage.setItem("freshda-dark", on ? "1" : "0");

    var raw = localStorage.getItem("freshda-settings");
    var saved = raw ? JSON.parse(raw) : {};
    if (!saved || typeof saved !== "object") saved = {};
    saved.darkMode = on;
    localStorage.setItem("freshda-settings", JSON.stringify(saved));
  } catch (e) {}
}

function setDarkMode(on) {
  state.darkMode = on;
  document.body.classList.toggle("dark", on);
  if (pageDarkToggle) {
    var i = pageDarkToggle.querySelector("i");
    if (i) i.className = on ? "ri-sun-line" : "ri-moon-line";
  }
  persistDarkPreference(on);
}
if (pageDarkToggle) {
  pageDarkToggle.addEventListener("click", function () {
    setDarkMode(!state.darkMode);
  });
}
setDarkMode(document.body.classList.contains("dark"));

/* ================= AUTH VIEW SWITCH ================= */
var loginView = document.getElementById("loginView");
var registerView = document.getElementById("registerView");
var toggleLogin = document.getElementById("toggleLogin");
var toggleRegister = document.getElementById("toggleRegister");

function showAuthView(view) {
  if (!loginView || !registerView || !toggleLogin || !toggleRegister) return;
  state.authView = view;
  loginView.classList.toggle("hidden", view !== "login");
  registerView.classList.toggle("hidden", view !== "register");
  toggleLogin.classList.toggle("active", view === "login");
  toggleRegister.classList.toggle("active", view === "register");
}
if (toggleLogin) {
  toggleLogin.addEventListener("click", function () {
    showAuthView("login");
  });
}
if (toggleRegister) {
  toggleRegister.addEventListener("click", function () {
    showAuthView("register");
  });
}

/* A rejected registration has to reopen the register panel, otherwise its
   errors would be hidden behind the login form after the redirect back. */
var authShell = document.getElementById("authShell");
if (authShell && authShell.dataset.view === "register") {
  showAuthView("register");
}

var goToRegisterLink = document.getElementById("goToRegisterLink");
if (goToRegisterLink)
  goToRegisterLink.addEventListener("click", function () {
    showAuthView("register");
  });
var goToLoginLink = document.getElementById("goToLoginLink");
if (goToLoginLink)
  goToLoginLink.addEventListener("click", function () {
    showAuthView("login");
  });

/* ================= PASSWORD TOGGLE ================= */
document.querySelectorAll(".toggle-password").forEach(function (btn) {
  btn.addEventListener("click", function () {
    var t = document.getElementById(this.dataset.target);
    var i = this.querySelector("i");
    if (!t || !i) return;
    if (t.type === "password") {
      t.type = "text";
      i.className = "ri-eye-off-line";
    } else {
      t.type = "password";
      i.className = "ri-eye-line";
    }
  });
});

/* ================= FIELD VALIDATION CHECKMARK ================= */
function bindFieldCheck(inputId) {
  var input = document.getElementById(inputId);
  if (!input) return;
  input.addEventListener("input", function () {
    var field = this.closest(".auth-field");
    if (field) field.classList.toggle("valid", this.value.trim().length > 0);
  });
}
["loginEmail", "registerName", "registerEmail"].forEach(bindFieldCheck);

/* ================= PASSWORD STRENGTH ================= */
var registerPassword = document.getElementById("registerPassword");
var passwordStrength = document.getElementById("passwordStrength");
var passwordStrengthText = document.getElementById("passwordStrengthText");
if (registerPassword && passwordStrength && passwordStrengthText) {
  registerPassword.addEventListener("input", function () {
    var val = this.value,
      score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/\d/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    var bars = passwordStrength.querySelectorAll(".bar");
    var colors = ["var(--border)", "var(--border)", "var(--border)", "var(--border)"];
    if (val.length === 0) {
      passwordStrengthText.textContent = "Use At Least 8 Characters";
    } else if (score <= 1) {
      colors[0] = "#dc2626";
      passwordStrengthText.textContent = "Weak Password";
    } else if (score === 2) {
      colors[0] = "#d97706";
      colors[1] = "#d97706";
      passwordStrengthText.textContent = "Fair Password";
    } else if (score === 3) {
      colors[0] = "#0284c7";
      colors[1] = "#0284c7";
      colors[2] = "#0284c7";
      passwordStrengthText.textContent = "Good Password";
    } else {
      colors = ["#16a34a", "#16a34a", "#16a34a", "#16a34a"];
      passwordStrengthText.textContent = "Strong Password 💪";
    }
    bars.forEach(function (b, i) {
      b.style.background = colors[i];
    });
  });
}

/* ================= ERROR HELPERS ================= */
function showError(el, txt, msg) {
  if (!el || !txt) return;
  txt.textContent = msg;
  el.classList.add("show");
}
function hideError(el) {
  if (!el) return;
  el.classList.remove("show");
}
var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/* ================= SESSION-SCOPED BROWSER DATA =================
   The unsaved batch photos belong to the account that created them. Clear
   them when the next user signs in on this device so account B never
   inherits account A's captures. */
["freshda-batch-draft"].forEach(function (key) {
  try {
    localStorage.removeItem(key);
  } catch (e) {}
});

/* ================= LOGIN ================= */
var loginForm = document.getElementById("loginForm");
var loginError = document.getElementById("loginError");
var loginErrorText = document.getElementById("loginErrorText");
var loginBtn = document.getElementById("loginBtn");
var loginSubmitting = false;
if (loginForm) {
  loginForm.addEventListener("submit", function (e) {
    /* Rapid double-clicks must not send two authentication requests. */
    if (loginSubmitting) {
      e.preventDefault();
      return;
    }

    hideError(loginError);
    var email = document.getElementById("loginEmail").value.trim();
    var password = document.getElementById("loginPassword").value;
    if (!email || !password) {
      e.preventDefault();
      showError(loginError, loginErrorText, "Please Enter Your Email And Password");
      return;
    }
    if (!EMAIL_RE.test(email)) {
      e.preventDefault();
      showError(loginError, loginErrorText, "Please Enter a Valid Email Address");
      return;
    }

    loginSubmitting = true;
    if (loginBtn) {
      loginBtn.disabled = true;
      loginBtn.textContent = "Logging in...";
    }
  });
}

/* Restore the button if the browser restores this page from its back/forward
   cache after a completed login. */
window.addEventListener("pageshow", function () {
  loginSubmitting = false;
  if (!loginBtn) return;
  loginBtn.disabled = false;
  loginBtn.textContent = "Log In";
});

/* ================= REGISTER ================= */
var registerForm = document.getElementById("registerForm");
var registerError = document.getElementById("registerError");
var registerErrorText = document.getElementById("registerErrorText");
if (registerForm) {
  registerForm.addEventListener("submit", function (e) {
    hideError(registerError);
    var name = document.getElementById("registerName").value.trim();
    var email = document.getElementById("registerEmail").value.trim();
    var password = registerPassword ? registerPassword.value : "";
    var confirm = document.getElementById("registerConfirm").value;
    if (!name || !email || !password || !confirm) {
      e.preventDefault();
      showError(registerError, registerErrorText, "Please Fill In All Fields");
      return;
    }
    if (!EMAIL_RE.test(email)) {
      e.preventDefault();
      showError(registerError, registerErrorText, "Please Enter a Valid Email Address");
      return;
    }
    if (password.length < 8) {
      e.preventDefault();
      showError(registerError, registerErrorText, "Password Must Be At Least 8 Characters");
      return;
    }
    if (password !== confirm) {
      e.preventDefault();
      showError(registerError, registerErrorText, "Passwords Do Not Match");
    }
  });
}
console.log("%cFresh-DA Auth Loaded ✓", "color:#0284C7;font-weight:bold;font-size:14px;");
