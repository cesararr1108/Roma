/**
 * Cliente HTTP único del módulo. Toda comunicación con el backend
 * (adg/models/ControlGastos.php) pasa por enviarPeticion(link, op, parametros),
 * que homologa el contrato y siempre devuelve una Promise.
 */
const ControlGastosApi = (() => {

  const LINK_MODELO = '../models/ControlGastos.php';

  /**
   * @param {string} link       Ruta del modelo PHP a invocar (usar ControlGastosApi.LINK_MODELO).
   * @param {string} op         Valor del "case" que atiende el switch del modelo.
   * @param {Object|FormData} parametros  Datos a enviar. Si es un objeto plano se convierte a FormData
   *                                      (soporta también archivos vía <input type="file">.files).
   * @returns {Promise<Object>} Respuesta ya parseada: { exito, mensaje, datos }.
   */
  const enviarPeticion = (link, op, parametros = {}) => {

    const data = (parametros instanceof FormData) ? parametros : new FormData();

    if (!(parametros instanceof FormData)) {
      Object.keys(parametros).forEach((clave) => {
        const valor = parametros[clave];
        if (valor === undefined || valor === null) return;

        if (valor instanceof FileList) {
          Array.from(valor).forEach((archivo) => data.append(clave, archivo));
        } else {
          data.append(clave, valor);
        }
      });
    }

    data.append('op', op);

    return new Promise((resolve, reject) => {
      $.ajax({
        url: link,
        type: 'POST',
        dataType: 'json',
        contentType: false,
        processData: false,
        cache: false,
        data,
      })
        .done((respuesta) => {
          if (respuesta && respuesta.exito) {
            resolve(respuesta);
          } else {
            reject(respuesta || { exito: false, mensaje: 'Respuesta inválida del servidor.' });
          }
        })
        .fail((jqXHR) => {
          let mensaje = 'Ocurrió un error de comunicación con el servidor.';
          try {
            const cuerpo = JSON.parse(jqXHR.responseText);
            if (cuerpo && cuerpo.mensaje) mensaje = cuerpo.mensaje;
          } catch (e) {
            // La respuesta no era JSON (p. ej. error 500 con HTML); se mantiene el mensaje genérico.
          }
          reject({ exito: false, mensaje });
        });
    });
  };

  return { enviarPeticion, LINK_MODELO };
})();

// Alias global solicitado: enviarPeticion(link, op, parametros)
const enviarPeticion = ControlGastosApi.enviarPeticion;
