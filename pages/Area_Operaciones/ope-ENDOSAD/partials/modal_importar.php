<!-- ====================== DataTables ====================== -->

<!-- 🔹 Modal Importar Excel -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="importar_excel.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">📥 Importar Operaciones desde Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label for="archivo_excel" class="form-label">Selecciona el archivo Excel (.xlsx o .csv)</label>
          <input type="file" name="archivo_excel" id="archivo_excel" class="form-control" accept=".xlsx, .xls, .csv" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Importar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
