
document.addEventListener("DOMContentLoaded", () => {
    const formularios = document.querySelectorAll(
        ".form-agregar-carrito"
    );

    const contador = document.getElementById("contadorCarrito");

    formularios.forEach((formulario) => {
        formulario.addEventListener("submit", async (evento) => {
            evento.preventDefault();

            const boton = formulario.querySelector(
                "button[type='submit']"
            );

            // Evitar pulsaciones repetidas mientras se procesa
            if (boton.disabled) return;

            boton.disabled = true;

            const textoOriginal = boton.textContent;

            try {
                const datos = new FormData(formulario);

                const respuesta = await fetch("agregar_carrito.php", {
                    method: "POST",
                    body: datos,
                    credentials: "same-origin"
                });

                const resultado = await respuesta.json();

                if (!respuesta.ok || !resultado.exito) {
                    throw new Error(resultado.mensaje);
                }

                // Actualizar el contador sin recargar
                if (contador) {
                    contador.textContent = resultado.cantidad;
                }

                // Confirmar visualmente que se ha añadido
                boton.textContent = "✓ Añadido";
                boton.classList.add("producto-anadido");

                setTimeout(() => {
                    boton.textContent = textoOriginal;
                    boton.classList.remove("producto-anadido");
                }, 1200);

            } catch (error) {
                alert(
                    error.message ||
                    "No se pudo añadir el producto."
                );

                boton.textContent = textoOriginal;
            } finally {
                boton.disabled = false;
            }
        });
    });
});
