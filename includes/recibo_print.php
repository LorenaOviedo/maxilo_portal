<!-- PLANTILLA DE RECIBO — IMPRESIÓN (oculta en pantalla) -->
<div id="reciboImprimir" style="display:none;">
    <div class="recibo-wrapper">
 
        <!-- Encabezado -->
        <div class="recibo-header">
            <div class="recibo-clinica">
                <h1 class="recibo-clinica-nombre">Sistema Maxilofacial Texcoco</h1>
                <p class="recibo-clinica-subtitulo">Comprobante de pago</p>
            </div>
            <div class="recibo-num">
                <div class="recibo-num-label">RECIBO</div>
                <div class="recibo-num-valor" id="printRecibo">—</div>
                <div class="recibo-fecha" id="printFechaPago">—</div>
            </div>
        </div>
 
        <hr class="recibo-sep">
 
        <!-- Paciente -->
        <div class="recibo-section">
            <div class="recibo-section-title">DATOS DEL PACIENTE</div>
            <div class="recibo-row">
                <span class="recibo-lbl">Nombre:</span>
                <span id="printPaciente">—</span>
            </div>
        </div>
 
        <!-- Cita -->
        <div class="recibo-section">
            <div class="recibo-section-title">DATOS DE LA CITA</div>
            <div class="recibo-row">
                <span class="recibo-lbl">Especialista:</span>
                <span id="printEspecialista">—</span>
            </div>
            <div class="recibo-row">
                <span class="recibo-lbl">Fecha de cita:</span>
                <span id="printFechaCita">—</span>
            </div>
            <div class="recibo-row">
                <span class="recibo-lbl">Motivo:</span>
                <span id="printMotivo">—</span>
            </div>
        </div>
 
        <!-- Pago -->
        <div class="recibo-section">
            <div class="recibo-section-title">DESGLOSE DEL PAGO</div>
            <div class="recibo-row">
                <span class="recibo-lbl">Método de pago:</span>
                <span id="printMetodo">—</span>
            </div>
            <div class="recibo-row" id="printRowRef" style="display:none;">
                <span class="recibo-lbl">Referencia:</span>
                <span id="printRef">—</span>
            </div>
            <div class="recibo-row">
                <span class="recibo-lbl">Monto total:</span>
                <span id="printTotal">—</span>
            </div>
            <div class="recibo-row" id="printRowDesc" style="display:none;">
                <span class="recibo-lbl">Descuento:</span>
                <span id="printDesc" style="color:#28a745;">—</span>
            </div>
        </div>
 
        <hr class="recibo-sep">
 
        <!-- Total neto -->
        <div class="recibo-total">
            <span>TOTAL PAGADO</span>
            <span id="printNeto" class="recibo-total-monto">—</span>
        </div>
 
        <!-- Estatus -->
        <div style="text-align:center; margin-top:16px;">
            <span id="printEstatus" style="display:inline-block; padding:6px 24px;
                border-radius:20px; font-weight:700; font-size:14px;
                background:#e8f5e9; color:#2e7d32; letter-spacing:1px;">
                PAGADO
            </span>
        </div>
 
        <!-- Observaciones -->
        <div id="printRowObs" style="display:none; margin-top:16px;">
            <div class="recibo-section-title">OBSERVACIONES</div>
            <div id="printObs" style="font-size:12px; color:#495057;"></div>
        </div>
 
        <!-- Pie -->
        <div class="recibo-footer">
            <p>Este comprobante es válido como recibo de pago.</p>
            <p>Sistema Maxilofacial Texcoco — <?php echo date('Y'); ?></p>
        </div>
 
    </div><!-- /.recibo-wrapper -->
</div><!-- /#reciboImprimir -->
 
<style>
/* ── Estilos de impresión ─────────────────────────────────────────────── */
@media print {
    /* Ocultar TODO excepto el recibo */
    body > *:not(#reciboImprimir)    { display: none !important; }
    #reciboImprimir                  { display: block !important; }
 
    body { margin: 0; padding: 0; background: #fff; }
 
    .recibo-wrapper {
        width: 80mm;         /* ancho ticket térmico estándar */
        margin: 0 auto;
        font-family: 'Courier New', monospace;
        font-size: 11px;
        color: #000;
        padding: 8mm 4mm;
    }
}
 
/* ── Estilos del recibo (aplican en impresión) ──────────────────────── */
.recibo-wrapper {
    width: 80mm;
    font-family: 'Courier New', monospace;
    font-size: 11px;
    color: #000;
}
.recibo-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}
.recibo-clinica-nombre {
    font-size: 13px;
    font-weight: 700;
    margin: 0 0 2px;
    text-transform: uppercase;
}
.recibo-clinica-subtitulo {
    font-size: 10px;
    color: #555;
    margin: 0;
}
.recibo-num { text-align: right; }
.recibo-num-label {
    font-size: 9px;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.recibo-num-valor {
    font-size: 14px;
    font-weight: 700;
}
.recibo-fecha { font-size: 10px; color: #555; }
 
.recibo-sep {
    border: none;
    border-top: 1px dashed #000;
    margin: 8px 0;
}
 
.recibo-section { margin-bottom: 8px; }
.recibo-section-title {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #555;
    margin-bottom: 4px;
    border-bottom: 1px solid #ccc;
    padding-bottom: 2px;
}
.recibo-row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 2px;
    font-size: 11px;
}
.recibo-lbl { color: #555; flex-shrink: 0; }
 
.recibo-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
    font-size: 13px;
    margin-top: 8px;
}
.recibo-total-monto { font-size: 16px; }
 
.recibo-footer {
    margin-top: 16px;
    text-align: center;
    font-size: 9px;
    color: #777;
    border-top: 1px dashed #000;
    padding-top: 6px;
}
.recibo-footer p { margin: 2px 0; }
</style>