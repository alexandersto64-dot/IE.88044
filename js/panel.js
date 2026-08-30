document.addEventListener("DOMContentLoaded", function () {

    var toggle = document.getElementById("sidebarToggle");
    var sidebar = document.getElementById("panelSidebar");
    var overlay = document.getElementById("sidebarOverlay");

    function closeSidebar() {
        if (!sidebar || !overlay || !toggle) return;
        sidebar.classList.remove("open");
        overlay.classList.remove("visible");
        toggle.setAttribute("aria-expanded", "false");
    }

    if (toggle && sidebar && overlay) {

        toggle.addEventListener("click", function () {
            var isOpen = sidebar.classList.toggle("open");
            overlay.classList.toggle("visible", isOpen);
            toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        overlay.addEventListener("click", closeSidebar);

    }

    // Submenús del sidebar: solo uno abierto a la vez.
    var parents = document.querySelectorAll(".nav-parent");

    parents.forEach(function (parent) {

        var btn = parent.querySelector(".nav-parent-toggle");
        if (!btn) return;

        btn.addEventListener("click", function () {
            var yaAbierto = parent.classList.contains("open");
            parents.forEach(function (p) { p.classList.remove("open"); });
            if (!yaAbierto) { parent.classList.add("open"); }
        });

    });

});
