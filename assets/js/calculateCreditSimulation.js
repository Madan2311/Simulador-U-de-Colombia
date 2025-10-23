function calculateCreditSimulation(valorMatricula, descuentoBeca, tasaInteres, plazoMeses, fechaInicioStr, program, financingChecked) {

  function formatearFechaLocal(fecha) {
    const year = fecha.getFullYear();
    const month = String(fecha.getMonth() + 1).padStart(2, '0');
    const day = String(fecha.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function obtenerUltimoDiaDelMes(anio, mes) {
    return new Date(anio, mes + 1, 0).getDate(); // Día 0 del siguiente mes
  }

  function obtenerFechaDePago(anio, mes) {
    const dia = obtenerUltimoDiaDelMes(anio, mes);
    return new Date(anio, mes, Math.min(30, dia)); // Si el mes tiene menos de 30 días, usar el último
  }
  let porcentajeCuotaInicial = 0;

  if (program === "EXPERIENCIA.CO LICENCIATURA EDUCACIÓN FÍSICA" || program === "EXPERIENCIA.CO LICENCIATURA MODELOS EDUCATIVOS FLEXIBLES") {
    porcentajeCuotaInicial = 0.15; // 15% para estos programas
  } else if(financingChecked) {
    porcentajeCuotaInicial = 0 ; // 0% si se selecciona la opción de financiación
  } else {
    porcentajeCuotaInicial = 0.3; // 30% para otros programas
  }

  // 1. Valor neto matrícula
  const matriculaNeta = valorMatricula * (1 - descuentoBeca);

  // 2. Cuota inicial 
  const cuotaInicial = matriculaNeta * porcentajeCuotaInicial;

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
    valorMatricula: valorMatricula.toLocaleString('es-CO'),
    descuentoBeca,
    matriculaNeta: Math.round(matriculaNeta).toLocaleString('es-CO'),
    cuotaInicial: Math.round(cuotaInicial).toLocaleString('es-CO'),
    montoCredito: Math.round(montoCredito).toLocaleString('es-CO'),
    administracion: Math.round(administracion).toLocaleString('es-CO'),
    cuotaMensual: Math.round(cuotaMensual).toLocaleString('es-CO'),
    fechaInicio: fechaInicioStr,
    plazo: plazoMeses
  };

  // Plan de pagos
  const planPagos = [];
  let saldo = montoCredito;

  // Obtener fecha de inicio
  const [anioInicio, mesInicio, diaInicio] = fechaInicioStr.split('-').map(Number);
  const fechaInicio = new Date(anioInicio, mesInicio - 1, diaInicio);

  // Calcular fecha de la primera cuota mensual
  const mesPrimeraCuota = fechaInicio.getDate() <= 10 ? fechaInicio.getMonth() : fechaInicio.getMonth() + 1;
  const anioPrimeraCuota = fechaInicio.getFullYear() + (mesPrimeraCuota > 11 ? 1 : 0);
  const mesAjustado = mesPrimeraCuota % 12;

  let fechaCuota = obtenerFechaDePago(anioPrimeraCuota, mesAjustado);

  // Registrar cuota inicial (día del trámite)
  planPagos.push({
    mes: 0,
    fecha: formatearFechaLocal(fechaInicio),
    cuota: (cuotaInicial + administracion).toFixed(2),
    interes: "0.00",
    abonoCapital: "0.00",
    saldo: saldo.toFixed(2)
  });

  // Cuotas mensuales
  for (let i = 1; i <= plazoMeses; i++) {
    const interes = saldo * tasa;
    const abonoCapital = cuotaMensual - interes;
    saldo -= abonoCapital;

    planPagos.push({
      mes: i,
      fecha: formatearFechaLocal(fechaCuota),
      cuota: cuotaMensual.toFixed(2),
      interes: interes.toFixed(2),
      abonoCapital: abonoCapital.toFixed(2),
      saldo: Math.max(saldo, 0).toFixed(2)
    });

    // Avanzar un mes para la próxima cuota
    const siguienteMes = fechaCuota.getMonth() + 1;
    const siguienteAnio = fechaCuota.getFullYear() + (siguienteMes > 11 ? 1 : 0);
    fechaCuota = obtenerFechaDePago(siguienteAnio, siguienteMes % 12);
  }

  return { resumen, planPagos };
}
