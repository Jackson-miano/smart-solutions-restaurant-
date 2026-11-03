document.addEventListener("DOMContentLoaded", function () {

    // ---------- 1. Mobile menu button ----------
    var toggle = document.querySelector(".nav-toggle");
    var links = document.getElementById("nav-links");
    if (toggle && links) {
        toggle.addEventListener("click", function () {
            var open = links.classList.toggle("open");
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
        });
    }

    // ---------- 2. Highlight the page you are on ----------
    var here = location.pathname.split("/").pop();
    document.querySelectorAll(".nav-links a").forEach(function (a) {
        if (a.getAttribute("href") === here) a.classList.add("active");
    });

    // ---------- 3. Show / hide password ----------
    document.querySelectorAll('input[type="password"]').forEach(function (input) {
        var wrap = document.createElement("div");
        wrap.className = "pw-wrap";
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "pw-toggle";
        btn.textContent = "Show";
        btn.setAttribute("aria-label", "Show password");
        btn.addEventListener("click", function () {
            var reveal = input.type === "password";
            input.type = reveal ? "text" : "password";
            btn.textContent = reveal ? "Hide" : "Show";
            btn.setAttribute("aria-label", reveal ? "Hide password" : "Show password");
        });
        wrap.appendChild(btn);
    });

    // ---------- 4. Meal quantity steppers + live pre-order total ----------
    var qtyInputs = document.querySelectorAll("input.qty");
    var totalBox = document.getElementById("order-total");
    var totalValue = document.getElementById("order-total-value");

    function updateTotal() {
        if (!totalBox) return;
        var total = 0;
        qtyInputs.forEach(function (input) {
            var q = parseInt(input.value, 10) || 0;
            total += q * parseFloat(input.dataset.price || 0);
        });
        totalValue.textContent = total.toLocaleString("en-KE", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        totalBox.hidden = total === 0;
    }

    function makeButton(symbol, label) {
        var b = document.createElement("button");
        b.type = "button";
        b.textContent = symbol;
        b.setAttribute("aria-label", label);
        return b;
    }

    qtyInputs.forEach(function (input) {
        var min = parseInt(input.min, 10) || 0;
        var max = parseInt(input.max, 10) || 20;

        var wrap = document.createElement("div");
        wrap.className = "stepper";
        var minus = makeButton("\u2212", "One less");
        var plus = makeButton("+", "One more");

        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(minus);
        wrap.appendChild(input);
        wrap.appendChild(plus);

        function change(delta) {
            var value = (parseInt(input.value, 10) || 0) + delta;
            input.value = Math.min(max, Math.max(min, value));
            updateTotal();
        }
        minus.addEventListener("click", function () { change(-1); });
        plus.addEventListener("click", function () { change(1); });
        input.addEventListener("input", updateTotal);
    });
    updateTotal();

    // ---------- 5. Bring the result message into view after submitting ----------
    var message = document.querySelector(".container .error, .container .success, .container .info");
    if (message) {
        var calm = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        message.scrollIntoView({ block: "center", behavior: calm ? "auto" : "smooth" });
    }
});