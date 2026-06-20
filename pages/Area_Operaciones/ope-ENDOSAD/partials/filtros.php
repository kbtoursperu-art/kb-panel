<div class="kb-card mb-4">

    <div class="kb-card-header">
        <h5 class="mb-0">
            Filtros de búsqueda
        </h5>
    </div>

    <div class="kb-card-body">

        <form method="GET">

            <div class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">Desde</label>
                    <input
                        type="date"
                        name="search_date_from"
                        class="form-control"
                        value="<?= htmlspecialchars($search_from) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Hasta</label>
                    <input
                        type="date"
                        name="search_date_to"
                        class="form-control"
                        value="<?= htmlspecialchars($search_to) ?>">
                </div>

                <div class="col-md-6">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="bi bi-search"></i>
                        Filtrar

                    </button>

                    <a
                        href="index.php"
                        class="btn btn-secondary">

                        <i class="bi bi-arrow-clockwise"></i>
                        Limpiar

                    </a>

                </div>

            </div>

        </form>

    </div>

</div>