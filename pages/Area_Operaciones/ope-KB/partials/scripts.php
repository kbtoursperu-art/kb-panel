<!-- =========================
     JQUERY
========================= -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- =========================
     BOOTSTRAP
========================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- =========================
     DATATABLES
========================= -->
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

<!-- =========================
     BUTTONS
========================= -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>

<!-- =========================
     EXPORT EXCEL PDF
========================= -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<!-- =========================
     DATATABLE
========================= -->
<script>

$(document).ready(function() {

    $('#tablaOperaciones').DataTable({

        dom: 'Bfrtip',

        buttons: [

            {
                extend: 'excelHtml5',
                text: '📊 Excel',
                className: 'btn btn-success'
            },

            {
                extend: 'pdfHtml5',
                text: '📄 PDF',
                className: 'btn btn-danger'
            }

        ],

        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json'
        },

        pageLength: 10,

        order: [[0,'desc']],

        responsive: true

    });

});

</script>

<!-- =========================
     SIDEBAR
========================= -->
<script>

document.addEventListener("DOMContentLoaded", function () {

    const toggleBtn = document.getElementById("toggle-sidebar");

    const body = document.body;

    if (!toggleBtn) return;

    toggleBtn.addEventListener("click", function () {

        body.classList.toggle("sidebar-collapsed");

        body.classList.toggle("sidebar-open");

    });

});

</script>

<!-- =========================
     AJAX CLIENTES
========================= -->
<script>

$(document).on('click', '.ver-clientes', function() {

    let id_grupo = $(this).data('id');

    $.ajax({

        url: 'ajax/obtener_clientes.php',

        data: {
            id_grupo: id_grupo
        },

        dataType: 'json',

        success: function(data) {

            let tbody = '';

            data.forEach(function(cliente, index) {

                tbody += '<tr>';

                tbody += '<td>' + (index+1) + '</td>';

                tbody += '<td>' + cliente.nombre + ' ' + cliente.apellido + '</td>';

                tbody += '<td>' + cliente.tipo + '</td>';

                tbody += '<td>' + (cliente.nombre_servicio ? cliente.nombre_servicio : '-') + '</td>';

                tbody += '<td>' + (cliente.fecha_salida ? cliente.fecha_salida : '-') + '</td>';

                tbody += '<td>' + (cliente.fecha_retorno ? cliente.fecha_retorno : '-') + '</td>';

                tbody += '</tr>';

            });

            $('#tablaClientesGrupo tbody').html(tbody);

        }

    });

});

</script>

<!-- =========================
     APP JS
========================= -->
<script src="js/app.js"></script>