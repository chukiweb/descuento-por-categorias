jQuery(document).ready(function ($) {

    // Manejar la selección de una insignia en el formulario
  $("#badge-select").on("change", function () {
      var selectedOption = $(this).find("option:selected");
      var imgSrc = selectedOption.data("img");
      $("#badge-preview img").attr("src", imgSrc);
    }).change(); // Trigger change event on page load to show initial preview

  // Trigger change event on page load to set the initial preview
  $("#badge-select").trigger("change");

  // Mostrar el formulario de nueva insignia
  $(".dpc-add-insignia").on("click", function () {
    $("#nueva-insignia-form").toggle();
  });

  // Manejar el envío del formulario de nueva insignia
  $("#nueva-insignia-form").on("submit", function (e) {
    e.preventDefault();

    var formData = new FormData(this);
    formData.append("action", "dpc_guardar_insignia");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          // Actualizar la tabla sin recargar la página
          var insigniaName = response.data.insignia_nombre;
          var insigniaUrl =
            '<?php echo plugin_dir_url(__FILE__) . "../images/"; ?>' +
            response.data.filename;

          var newRow = "<tr>";
          newRow += "<td>" + insigniaName + "</td>";
          newRow +=
            '<td><img src="' +
            insigniaUrl +
            '" alt="' +
            insigniaName +
            '" style="max-width: 50px; max-height: 50px;"></td>';
          newRow +=
            '<td><button class="button delete-insignia" data-insignia="' +
            response.data.filename +
            '">Eliminar</button></td>';
          newRow += "</tr>";

          $("#insignias-table-body").append(newRow);

          // Resetear el formulario
          $("#nueva-insignia-form")[0].reset();
          $("#nueva-insignia-form").hide();

          // Mostrar mensaje de éxito
          $(
            '<div class="notice notice-success is-dismissible"><p>' +
              response.data.message +
              "</p></div>"
          )
            .appendTo("body")
            .delay(3000)
            .fadeOut();
        } else {
          // Mostrar mensaje de error
          $(
            '<div class="notice notice-error is-dismissible"><p>' +
              response.data.message +
              "</p></div>"
          )
            .appendTo("body")
            .delay(3000)
            .fadeOut();
        }
      },
      error: function (response) {
        // Mostrar mensaje de error
        $(
          '<div class="notice notice-error is-dismissible"><p>Error en la solicitud AJAX.</p></div>'
        )
          .appendTo("body")
          .delay(3000)
          .fadeOut();
      },
    });
  });

  // Manejar la eliminación de insignias
  $("#insignias-table-body").on("click", ".delete-insignia", function () {
    if (confirm("¿Está seguro de que desea eliminar esta insignia?")) {
      var insignia = $(this).data("insignia");

      $.post(
        ajaxurl,
        {
          action: "dpc_eliminar_insignia",
          insignia: insignia,
          _wpnonce: '<?php echo wp_create_nonce("dpc_manage_insignias"); ?>',
        },
        function (response) {
          if (response.success) {
            // Eliminar la fila correspondiente
            $('button[data-insignia="' + insignia + '"]')
              .closest("tr")
              .remove();

            // Mostrar mensaje de éxito
            $(
              '<div class="notice notice-success is-dismissible"><p>' +
                response.data.message +
                "</p></div>"
            )
              .appendTo("body")
              .delay(3000)
              .fadeOut();
          } else {
            // Mostrar mensaje de error
            $(
              '<div class="notice notice-error is-dismissible"><p>' +
                response.data.message +
                "</p></div>"
            )
              .appendTo("body")
              .delay(3000)
              .fadeOut();
          }
        }
      );
    }
  });
});
