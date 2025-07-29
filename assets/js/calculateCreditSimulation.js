
function calculateCreditSimulation(valorMatricula, descuentoBeca, tasaInteres, plazoMeses, fechaInicioStr) {  
    
    function formatearFechaLocal(fecha) {
        const year = fecha.getFullYear();
        const month = String(fecha.getMonth() + 1).padStart(2, '0');
        const day = String(fecha.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // 1. Valor neto matrícula
    const matriculaNeta = valorMatricula * (1 - descuentoBeca);

    // 2. Cuota inicial (30%)
    const cuotaInicial = matriculaNeta * 0.3;

    // 3. Monto del crédito
    const montoCredito = matriculaNeta - cuotaInicial;

    // 4. Administración (5%)
    const administracion = montoCredito * 0.05;

    // 5. Cuota mensual (fórmula amortización)
    const tasa = tasaInteres; // mensual
    const n = plazoMeses;
    const cuotaMensual = (montoCredito * tasa) / (1 - Math.pow(1 + tasa, -n));

    // Resumen
    const resumen = {
        valorMatricula,
        descuentoBeca,
        matriculaNeta: Math.round(matriculaNeta),
        cuotaInicial: Math.round(cuotaInicial),
        montoCredito: Math.round(montoCredito),
        administracion: Math.round(administracion),
        cuotaMensual: Math.round(cuotaMensual),
        fechaInicio: fechaInicioStr,
        plazo: plazoMeses
    };

    // 6. Plan de pagos
    const planPagos = [];
    let saldo = montoCredito;
    
    const [anio, mes, dia] = fechaInicioStr.split('-').map(Number);
    let fecha = new Date(anio, mes - 1, dia);

    planPagos.push({
        mes: 0,
        fecha: formatearFechaLocal(fecha),
        cuota: (cuotaInicial + administracion).toFixed(2),
        interes: "0.00",
        abonoCapital: "0.00",
        saldo: saldo.toFixed(2)
    });

    for (let i = 1; i <= plazoMeses; i++) {
        const interes = saldo * tasa;
        const abonoCapital = cuotaMensual - interes;
        saldo -= abonoCapital;

        fecha.setMonth(fecha.getMonth() + 1);

        planPagos.push({
            mes: i,
            fecha: formatearFechaLocal(fecha),
            cuota: cuotaMensual.toFixed(2),
            interes: interes.toFixed(2),
            abonoCapital: abonoCapital.toFixed(2),
            saldo: Math.max(saldo, 0).toFixed(2)
        });

    }
    
    return { resumen, planPagos };
}