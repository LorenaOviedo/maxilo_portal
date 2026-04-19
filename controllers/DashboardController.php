<?php
/**
 * Controlador para el dashboard del sistema.
 *
 * Gestiona la vista del dashboard y expone el método
 * resumen() para que Api.php lo consuma vía AJAX.
 */

require_once __DIR__ . '/../models/Dashboard.php';

class DashboardController
{
    private Dashboard $model;

    public function __construct(PDO $db)
    {
        $this->model = new Dashboard($db);
    }

    /**
     * Regresa el resumen completo del dashboard..
     */
    public function resumen(): array
    {
        return $this->model->resumen();
    }

    /**
     * Prepara las variables que necesita la vista dashboard.php.
     * Llamar antes de incluir la vista.
     *
     * @return array  Datos listos para la vista.
     */
    public function prepararVista(): array
    {
        // Carga inicial síncrona: los datos se actualizan por AJAX.
        return $this->model->resumen();
    }
}