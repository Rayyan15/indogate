<?php

namespace App\Domain\Fleet\Services;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Fleet\Exceptions\ScheduleConflictException;
use App\Domain\Fleet\Models\Driver;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Fleet\Models\Vehicle;
use App\Enums\BookingStatus;
use App\Support\Branch\BranchScope;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignmentService
{
    /**
     * Saring driver berdasarkan cabang booking, status aktif, preferensi gender,
     * dan ketersediaan jadwal (tidak bentrok dengan penugasan lain).
     */
    public function suggestDrivers(
        PackageBooking $booking,
        CarbonInterface|string|null $dateFrom = null,
        CarbonInterface|string|null $dateTo = null
    ): Collection {
        $startDate = $this->normalizeDate($dateFrom ?? $booking->departure_date);
        $endDate = $this->normalizeDate($dateTo ?? ($booking->return_date ?? $booking->departure_date));

        $query = Driver::withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $booking->branch_id)
            ->active();

        // Filter preferensi gender secara ketat (tidak ada fallback diam-diam)
        $pref = $booking->driver_gender_preference;
        if ($pref === 'female' || $pref === 'male') {
            $query->where('gender', $pref);
        }

        // Saring driver yang tidak memiliki penugasan beririsan
        $query->whereDoesntHave('assignments', function ($q) use ($startDate, $endDate) {
            $q->where('status', '!=', DriverAssignment::STATUS_CANCELLED)
                ->whereDate('date_from', '<=', $endDate->toDateString())
                ->whereDate('date_to', '>=', $startDate->toDateString());
        });

        return $query->orderBy('name')->get();
    }

    /**
     * Saring kendaraan berdasarkan cabang booking, status aktif,
     * dan ketersediaan jadwal (tidak bentrok dengan penugasan lain).
     */
    public function suggestVehicles(
        PackageBooking $booking,
        CarbonInterface|string|null $dateFrom = null,
        CarbonInterface|string|null $dateTo = null
    ): Collection {
        $startDate = $this->normalizeDate($dateFrom ?? $booking->departure_date);
        $endDate = $this->normalizeDate($dateTo ?? ($booking->return_date ?? $booking->departure_date));

        $query = Vehicle::withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $booking->branch_id)
            ->active();

        $query->whereDoesntHave('assignments', function ($q) use ($startDate, $endDate) {
            $q->where('status', '!=', DriverAssignment::STATUS_CANCELLED)
                ->whereDate('date_from', '<=', $endDate->toDateString())
                ->whereDate('date_to', '>=', $startDate->toDateString());
        });

        return $query->orderBy('plate')->get();
    }

    /**
     * Berikan pesan peringatan jika saran driver kosong atau preferensi gender tidak terpenuhi.
     */
    public function getSuggestionWarning(PackageBooking $booking, Collection $suggestedDrivers): ?string
    {
        if ($suggestedDrivers->isNotEmpty()) {
            return null;
        }

        $pref = $booking->driver_gender_preference;
        if ($pref === 'female') {
            return __('fleet.warning_no_female');
        }

        if ($pref === 'male') {
            return __('fleet.warning_no_male');
        }

        return __('fleet.warning_no_driver');
    }

    /**
     * Tugaskan driver dan kendaraan ke booking dengan validasi cabang, gender, dan deteksi bentrok.
     */
    public function assign(
        PackageBooking $booking,
        Driver|int $driver,
        Vehicle|int|null $vehicle,
        CarbonInterface|string $dateFrom,
        CarbonInterface|string $dateTo,
        ?string $notes = null
    ): DriverAssignment {
        $driverModel = $driver instanceof Driver
            ? $driver
            : Driver::withoutGlobalScope(BranchScope::class)->findOrFail($driver);

        $vehicleModel = null;
        if ($vehicle !== null) {
            $vehicleModel = $vehicle instanceof Vehicle
                ? $vehicle
                : Vehicle::withoutGlobalScope(BranchScope::class)->findOrFail($vehicle);
        }

        $startDate = $this->normalizeDate($dateFrom);
        $endDate = $this->normalizeDate($dateTo);

        if ($endDate->lt($startDate)) {
            throw new InvalidArgumentException('Tanggal akhir penugasan tidak boleh sebelum tanggal mulai.');
        }

        if (in_array($booking->status, [BookingStatus::CANCELLED, BookingStatus::COMPLETED], true)) {
            throw new InvalidArgumentException('Booking sudah dibatalkan atau selesai; penugasan tidak dapat dibuat.');
        }

        // 1. Validasi cabang (Branch Isolation)
        if ((int) $driverModel->branch_id !== (int) $booking->branch_id) {
            throw new InvalidArgumentException('Driver tidak berasal dari cabang yang sama dengan pemesanan.');
        }

        if ($vehicleModel && (int) $vehicleModel->branch_id !== (int) $booking->branch_id) {
            throw new InvalidArgumentException('Kendaraan tidak berasal dari cabang yang sama dengan pemesanan.');
        }

        // 2. Validasi status aktif
        if (! $driverModel->is_active) {
            throw new InvalidArgumentException('Driver berstatus nonaktif.');
        }

        if ($vehicleModel && ! $vehicleModel->is_active) {
            throw new InvalidArgumentException('Kendaraan berstatus nonaktif.');
        }

        // 3. Validasi preferensi gender ketat (DoD: tidak pernah dilanggar diam-diam)
        $pref = $booking->driver_gender_preference;
        if ($pref === 'female' && $driverModel->gender !== 'female') {
            throw new InvalidArgumentException('Driver tidak sesuai preferensi gender pemesanan (perempuan).');
        }
        if ($pref === 'male' && $driverModel->gender !== 'male') {
            throw new InvalidArgumentException('Driver tidak sesuai preferensi gender pemesanan (laki-laki).');
        }

        return DB::transaction(function () use ($booking, $driverModel, $vehicleModel, $startDate, $endDate, $notes) {
            // Kunci baris driver/kendaraan agar cek bentrok + create atomik
            Driver::withoutGlobalScope(BranchScope::class)->whereKey($driverModel->id)->lockForUpdate()->first();
            if ($vehicleModel) {
                Vehicle::withoutGlobalScope(BranchScope::class)->whereKey($vehicleModel->id)->lockForUpdate()->first();
            }

            // 4. Deteksi bentrok jadwal driver
            $driverConflict = DriverAssignment::withoutGlobalScope(BranchScope::class)
                ->where('driver_id', $driverModel->id)
                ->overlapping($startDate, $endDate)
                ->first();

            if ($driverConflict) {
                throw new ScheduleConflictException(
                    "Driver {$driverModel->name} sudah ditugaskan pada periode {$driverConflict->date_from->format('d/m/Y')} - {$driverConflict->date_to->format('d/m/Y')}."
                );
            }

            // 5. Deteksi bentrok jadwal kendaraan (bila ada)
            if ($vehicleModel) {
                $vehicleConflict = DriverAssignment::withoutGlobalScope(BranchScope::class)
                    ->where('vehicle_id', $vehicleModel->id)
                    ->overlapping($startDate, $endDate)
                    ->first();

                if ($vehicleConflict) {
                    throw new ScheduleConflictException(
                        "Kendaraan {$vehicleModel->plate} sudah ditugaskan pada periode {$vehicleConflict->date_from->format('d/m/Y')} - {$vehicleConflict->date_to->format('d/m/Y')}."
                    );
                }
            }

            // 6. Buat assignment
            $assignment = DriverAssignment::create([
                'branch_id' => $booking->branch_id,
                'booking_id' => $booking->id,
                'driver_id' => $driverModel->id,
                'vehicle_id' => $vehicleModel?->id,
                'date_from' => $startDate->toDateString(),
                'date_to' => $endDate->toDateString(),
                'status' => DriverAssignment::STATUS_ASSIGNED,
                'notes' => $notes,
            ]);

            if (function_exists('activity')) {
                activity('fleet')
                    ->performedOn($assignment)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'booking_code' => $booking->code,
                        'driver_name' => $driverModel->name,
                        'vehicle_plate' => $vehicleModel?->plate,
                        'date_from' => $startDate->toDateString(),
                        'date_to' => $endDate->toDateString(),
                    ])
                    ->log("Penugasan driver {$driverModel->name} untuk booking {$booking->code}");
            }

            return $assignment;
        });
    }

    /**
     * Batalkan penugasan dengan alasan wajib.
     */
    public function cancel(DriverAssignment $assignment, string $reason): DriverAssignment
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan pembatalan penugasan wajib diisi.');
        }

        $assignment->status = DriverAssignment::STATUS_CANCELLED;
        $assignment->notes = $assignment->notes
            ? $assignment->notes."\n(Dibatalkan: ".trim($reason).')'
            : 'Dibatalkan: '.trim($reason);
        $assignment->save();

        if (function_exists('activity')) {
            activity('fleet')
                ->performedOn($assignment)
                ->causedBy(auth()->user())
                ->withProperties(['reason' => $reason])
                ->log("Penugasan driver {$assignment->driver?->name} dibatalkan: {$reason}");
        }

        return $assignment;
    }

    private function normalizeDate(CarbonInterface|string $date): Carbon
    {
        return $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse($date);
    }
}
