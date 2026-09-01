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

    // Estado de carga en formularios del panel (subida de PCA/Unidades/
    // Sesiones, envío de trabajos, etc.): deshabilita el botón al enviar
    // para evitar doble clic y dar una señal visual inmediata mientras
    // la página recarga. Solo visual: el formulario sigue enviándose de
    // forma normal (POST + redirect), no se cambia ninguna validación.
    var formulariosPanel = document.querySelectorAll(".panel-form, .material-upload-form");

    formulariosPanel.forEach(function (form) {

        form.addEventListener("submit", function () {

            var boton = form.querySelector("button[type='submit']");
            if (!boton || boton.disabled) return;

            boton.dataset.textoOriginal = boton.textContent;
            boton.disabled = true;
            boton.classList.add("is-loading");
            boton.textContent = "Guardando…";

        });

    });

});
