# Reporte de Corrección: Desbalance Negativo en Cajas (Finanzas)

## 1. Descripción del Problema
Se reportó que el sistema mostraba saldos negativos significativos en el Dashboard para el método de pago **Efectivo** (S/-70,188.60). Tras la auditoría de la base de datos, se identificaron dos causas principales provenientes del módulo de Finanzas (`/finances`):

- **Gastos Espejo:** Cuando se registraba una cuota de crédito pagada a través de un método de pago interno (como Efectivo), el sistema automáticamente generaba un registro en la tabla `Expense` (Gastos) para reflejar la salida del dinero.
- **Registros Huérfanos (Bug Crítico):** Si un usuario eliminaba un Crédito (`BankLoan`) desde el panel administrativo, el sistema borraba el crédito de la tabla, pero **no eliminaba** ni los pagos asociados (`LoanPayment`) ni los gastos asociados (`Expense`). Como resultado, quedaron más de **S/ 113,620.00** en gastos de "Caja Arequipa" descontando permanentemente dinero de las cajas sin estar atados a ningún crédito visible.

## 2. Soluciones Implementadas

### A. Limpieza de Datos (Comando Artisan)
Para recuperar la información sin perder la trazabilidad de los pagos de créditos activos, se desarrolló un comando de consola en Laravel: `CleanFinances`. 

Este comando realiza 3 acciones clave:
1. Identifica y **elimina permanentemente** todos los gastos (`Expense`) que quedaron huérfanos por créditos eliminados anteriormente.
2. Identifica y **elimina permanentemente** todos los pagos de cuotas (`LoanPayment`) cuyos créditos ya no existen.
3. Para los créditos que **sí están activos**, actualiza sus pagos existentes cambiando el método de pago a Nulo (`payment_method_id = null`), transformándolos en **pagos externos**, y elimina el gasto espejo que descontaba de la caja.

> [!TIP]
> **Ejecución en el VPS:**
> El comando se puede ejecutar en el servidor de producción usando: `php artisan finance:cleanup`

### B. Parche Preventivo en el Controlador
Para evitar que el problema de registros huérfanos vuelva a ocurrir, se parcheó el método de eliminación en el backend.

**Archivo Modificado:** `app/Http/Controllers/FinanceController.php`

**Cambio Realizado:** Se interceptó el método `destroy()` para que, antes de borrar el `BankLoan` de la base de datos, iterativamente busque y borre todos los `Expense` generados por sus pagos, seguido por los `LoanPayment`.

```php
    public function destroy($id)
    {
        $loan = BankLoan::findOrFail($id);
        
        // Buscar y eliminar todos los gastos asociados a los pagos antes de eliminar el crédito
        foreach ($loan->payments as $payment) {
            $description = 'Pago de Cuota ' . $payment->installment_number . ' - Crédito Banco ' . $loan->bank_name;
            
            $expense = \App\Models\Expense::where('description', $description)
                ->where('amount', $payment->amount)
                ->where('payment_method_id', $payment->payment_method_id)
                ->whereDate('date', $payment->payment_date->toDateString())
                ->first();
                
            if ($expense) {
                $expense->delete();
            }
            $payment->delete();
        }

        $loan->delete();

        return response()->json(['status' => true]);
    }
```

## 3. Estado Actual
- **Local:** La base de datos local fue completamente auditada, depurada y parcheada.
- **Servidor:** El código ha sido preparado localmente. Solo se requiere subir los cambios mediante `git push`, descargar en el servidor con `git pull` y correr la instrucción Artisan para sanear completamente la data de producción y estabilizar el Dashboard.
