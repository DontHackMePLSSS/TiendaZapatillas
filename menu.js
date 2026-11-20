
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".menu-desplegable").forEach(menu => {
        const boton = menu.querySelector(".boton-desplegable");

        boton.addEventListener("click", () => {
            const abierto = menu.classList.toggle("abierto");
            boton.setAttribute("aria-expanded", String(abierto));
        });

        document.addEventListener("click", evento => {
            if (!menu.contains(evento.target)) {
                menu.classList.remove("abierto");
                boton.setAttribute("aria-expanded", "false");
            }
        });

        document.addEventListener("keydown", evento => {
            if (evento.key === "Escape") {
                menu.classList.remove("abierto");
                boton.setAttribute("aria-expanded", "false");
            }
        });
    });
});
