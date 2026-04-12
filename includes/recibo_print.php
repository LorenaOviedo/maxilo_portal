<!-- PLANTILLA DE RECIBO — IMPRESIÓN (oculta en pantalla) -->
<div id="reciboImprimir" style="display:none;">
    <div class="recibo-container">
        
        <header class="recibo-header">
            <div class="brand-info">
                <img src="img/logo_maxilo.png" alt="Logo Ortofacial" class="recibo-logo">
                <div class="clinica-datos">
                    <h1>ORTOFACIAL</h1>
                    <p>ORTODONCIA, CIRUGÍA MAXILOFACIAL Y PATOLOGÍA ORAL</p>
                </div>
            </div>
            <div class="doctor-nombre">
                Dr. Alfonso Ayala Gómez
            </div>
        </header>

        <main class="recibo-body">
            <h2 class="titulo-documento">RECIBO DE PAGO</h2>

            <div class="folio-fecha">
                <p><strong>Folio:</strong> <span id="printRecibo">REC-2026-000001</span></p>
                <p><strong>Fecha de emisión:</strong> <span id="printFechaPago">11/04/2026</span></p>
            </div>

            <section class="recibo-seccion">
                <h3>DATOS DEL PACIENTE</h3>
                <div class="linea-datos">
                    <span class="etiqueta">Nombre:</span>
                    <span class="valor" id="printPaciente">ANA MARIA GARCIA LOPEZ</span>
                </div>
            </section>

            <section class="recibo-seccion">
                <h3>DATOS DE LA CITA</h3>
                <div class="linea-datos">
                    <span class="etiqueta">Atendió:</span>
                    <span class="valor" id="printEspecialista">LAURA MORALES</span>
                </div>
                <div class="linea-datos">
                    <span class="etiqueta">Fecha de cita:</span>
                    <span class="valor" id="printFechaCita">23/03/2026 16:00</span>
                </div>
                <div class="linea-datos">
                    <span class="etiqueta">Motivo:</span>
                    <span class="valor" id="printMotivo">Dolor dental</span>
                </div>
            </section>

            <section class="recibo-seccion">
                <h3>DESGLOSE DEL PAGO</h3>
                <div class="linea-datos">
                    <span class="etiqueta">Método de pago:</span>
                    <span class="valor" id="printMetodo">Efectivo</span>
                </div>
                <div class="linea-datos">
                    <span class="etiqueta">Monto total:</span>
                    <span class="valor" id="printTotal">$600.00</span>
                </div>
            </section>

            <div class="total-final">
                <span>TOTAL PAGADO:</span>
                <span class="monto" id="printNeto">$600.00</span>
            </div>

            <div class="status-pagado">
                <p class="txt-pagado" id="printEstatus">PAGADO</p>
            </div>
        </main>

        <footer class="recibo-footer">
            <p class="nota">Este comprobante es válido como recibo de pago.</p>
            <p class="copyright">Sistema Maxilofacial Texcoco — 2026</p>
            
            <div class="footer-blue-bar">
                <p>Retorno C No. 8 Fraccionamiento San Martín, Texcoco, Estado de México</p>
                <p>Teléfonos: 55 1640-3007 55 1246 3777 59 5931 3070</p>
            </div>
        </footer>

    </div>
</div>

<style>
/* Estilos generales para el contenedor */
.recibo-container {
    width: 210mm; /* Ancho A4 */
    min-height: 297mm;
    margin: 0 auto;
    padding: 20mm;
    background-color: #fff;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #1a1a1a;
    display: flex;
    flex-direction: column;
}

/* Encabezado */
.recibo-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: none;
    margin-bottom: 40px;
}

.brand-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.recibo-logo {
    width: 80px;
    height: auto;
}

.clinica-datos h1 {
    font-size: 26px;
    font-weight: 800;
    color: #2c3e50;
    margin: 0;
    letter-spacing: 1px;
}

.clinica-datos p {
    font-size: 10px;
    color: #34495e;
    margin: 0;
    font-weight: 600;
}

.doctor-nombre {
    font-size: 20px;
    font-weight: 700;
    color: #1a3a8a; /* Azul oscuro como el de la imagen */
}

/* Título Documento */
.titulo-documento {
    text-align: center;
    font-size: 22px;
    margin-bottom: 30px;
    letter-spacing: 2px;
    font-weight: 700;
}

.folio-fecha {
    text-align: right;
    margin-bottom: 30px;
    font-size: 14px;
}
.folio-fecha p { margin: 2px 0; }

/* Secciones */
.recibo-seccion {
    margin-bottom: 25px;
}

.recibo-seccion h3 {
    font-size: 14px;
    border-bottom: 1px dashed #ccc;
    padding-bottom: 5px;
    margin-bottom: 10px;
    font-weight: 700;
    color: #333;
}

.linea-datos {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.valor {
    text-align: right;
    font-weight: 500;
}

/* Total */
.total-final {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #000;
    padding-top: 10px;
    margin-top: 30px;
    font-weight: 800;
    font-size: 18px;
}

/* Sello Pagado */
.status-pagado {
    text-align: center;
    margin-top: 60px;
}

.txt-pagado {
    font-size: 28px;
    font-weight: 900;
    color: #1a1a1a;
    letter-spacing: 3px;
}

/* Footer */
.recibo-footer {
    margin-top: auto;
    text-align: center;
}

.nota { font-size: 12px; margin-bottom: 5px; font-weight: 600; }
.copyright { font-size: 11px; color: #666; margin-bottom: 20px; }

.footer-blue-bar {
    border-top: 2px solid #1a3a8a;
    padding-top: 15px;
    color: #1a3a8a;
    font-size: 13px;
    line-height: 1.4;
}

/* Ajustes de Impresión */
@media print {
    body { background: none; }
    .recibo-container { 
        width: 100%; 
        padding: 10mm; 
        margin: 0;
        box-shadow: none;
    }
    #reciboImprimir { display: block !important; }
    /* Ocultar elementos de la interfaz */
    nav, .sidebar, .btn, .no-print { display: none !important; }
}
</style>